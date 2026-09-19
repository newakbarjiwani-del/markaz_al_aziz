<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Finance\TagihanCicilanService;
use App\Services\Finance\PembayaranRecordingService;
use App\Services\Finance\SaldoKeuanganService;
use App\Services\Finance\SccttranLogger;
use App\Support\ActionMessage;
use App\Support\DisplayDate;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    use DataTableTrait;
    use FormatsPaymentReceipts;

    private const FIDBANK_MANUAL_CASH = '1140000';
    private const FIDBANK_MANUAL_BMI = '1140001';
    private const FIDBANK_MANUAL_SALDO = '1140002';
    private const FIDBANK_TRANSFER = '1140003';

    private const VALID_FIDBANKS = [
        self::FIDBANK_MANUAL_CASH,
        self::FIDBANK_MANUAL_BMI,
        self::FIDBANK_MANUAL_SALDO,
        self::FIDBANK_TRANSFER,
    ];

    public function index(): View
    {
        return view('admin.keuangan.pembayaran', [
            'title' => 'Pembayaran',
            'fidbanks' => [
                self::FIDBANK_MANUAL_CASH => 'Tunai',
                self::FIDBANK_MANUAL_BMI => 'Manual BMI',
                self::FIDBANK_MANUAL_SALDO => 'Saldo Keuangan',
                self::FIDBANK_TRANSFER => 'Transfer Bank Lain',
            ],
            'stats' => [
                'belum_lunas' => Tagihan::unpaid()->count(),
                'hari_ini' => Pembayaran::whereDate('paid_dt', today())->sum('total_amount'),
                'bulan_ini' => Pembayaran::where('paid_dt', '>=', now()->startOfMonth())->sum('total_amount'),
            ],
        ]);
    }

    public function studentTagihan(Siswa $siswa, TagihanCicilanService $cicilanService): JsonResponse
    {
        $siswa->load('kelas');

        $tagihan = Tagihan::query()
            ->rootBill()
            ->where('siswa_id', $siswa->id)
            ->orderBy('status')
            ->orderBy('due_date')
            ->get();

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
                'virtual_account' => $siswa->virtualAccountNumber(),
            ],
            'tagihan' => $tagihan->map(fn (Tagihan $row) => $this->formatTagihanRow($row, $cicilanService)),
        ]);
    }

    public function store(Request $request, PembayaranRecordingService $recordingService, TagihanCicilanService $cicilanService): JsonResponse
    {
        $data = $request->validate([
            'fidbank' => ['required', 'string', 'in:'.implode(',', self::VALID_FIDBANKS)],
            'reference' => 'nullable|string|max:100',
            'paid_dt' => 'nullable|date',
            'items' => 'required|array|min:1|max:30',
            'items.*.tagihan_id' => ['required', SoftDeleteRules::exists('tagihan')],
            'items.*.amount' => ['nullable', 'integer', 'min:1000'],
        ]);

        $paidAt = isset($data['paid_dt']) ? Carbon::parse($data['paid_dt']) : now();
        $fidbank = $data['fidbank'];
        $reference = filled($data['reference'] ?? null)
            ? $data['reference']
            : $this->generatePaymentReference($fidbank);
        $siswaId = null;
        $paymentItems = [];

        try {
            $payment = DB::transaction(function () use ($data, $fidbank, $paidAt, $reference, $recordingService, $cicilanService, &$siswaId, &$paymentItems) {
                $saldoService = app(SaldoKeuanganService::class);
                $sccttranLogger = app(SccttranLogger::class);

                foreach ($data['items'] as $index => $item) {
                    $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($item['tagihan_id']);

                    if ($siswaId === null) {
                        $siswaId = $tagihan->siswa_id;
                        Siswa::findOrFail($siswaId)->assertCanTransact('items');
                    } elseif ($tagihan->siswa_id !== $siswaId) {
                        throw ValidationException::withMessages([
                            'items' => 'Semua tagihan harus milik siswa yang sama.',
                        ]);
                    }

                    $amount = isset($item['amount'])
                        ? (int) $item['amount']
                        : $cicilanService->payableAmount($tagihan);

                    if (! $tagihan->is_cicilan) {
                        $amount = $cicilanService->payableAmount($tagihan);
                    } elseif ($amount > $cicilanService->payableAmount($tagihan)) {
                        throw ValidationException::withMessages([
                            "items.{$index}.amount" => 'Nominal melebihi sisa tagihan cicilan.',
                        ]);
                    }

                    if ($amount <= 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.tagihan_id" => "Tagihan {$tagihan->jenis} sudah lunas.",
                        ]);
                    }
                    $siswa = Siswa::findOrFail($siswaId);
                    $sccttran = null;

                    if ($fidbank === self::FIDBANK_MANUAL_SALDO) {
                        $saldo = $saldoService->forSiswa($siswa, lock: true);
                        $saldoBalance = (int) $saldo->balance;

                        if ($saldoBalance < $amount) {
                            throw ValidationException::withMessages([
                                'fidbank' => 'Saldo keuangan tidak mencukupi. Saldo: Rp '.number_format($saldoBalance, 0, ',', '.').', dibutuhkan: Rp '.number_format($amount, 0, ',', '.'),
                            ]);
                        }

                        $saldoService->debit($saldo, $amount);

                        $sccttran = $sccttranLogger->fromSaldo($siswa->id, $amount, [
                            'trxdate' => $paidAt,
                            'refno' => $reference,
                        ]);
                    }

                    $paymentItems[] = [
                        'tagihan' => $tagihan,
                        'amount' => $amount,
                        'sccttran' => $sccttran,
                    ];
                }

                return $recordingService->record(
                    Siswa::findOrFail($siswaId),
                    $fidbank,
                    $reference,
                    $paidAt,
                    auth()->id(),
                    $paymentItems,
                );
            });
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Validasi gagal.',
                $e->errors(),
                422
            );
        }

        $siswa = Siswa::with('kelas')->find($siswaId);
        $subject = $siswa ? ActionMessage::siswa($siswa) : '';
        $amountText = ActionMessage::rupiah((float) $payment->total_amount);
        $processed = $payment->details()->count();
        $message = $processed === 1
            ? ActionMessage::withSubject("Pembayaran {$amountText} berhasil dicatat", $subject)
            : ActionMessage::withSubject("{$processed} pembayaran ({$amountText}) berhasil dicatat", $subject);

        $payment->load(['user', 'details.tagihan', 'siswa.kelas.sekolah']);

        return $this->jsonSuccess($message, [
            'processed' => $processed,
            'total_amount' => (float) $payment->total_amount,
            'siswa_id' => $siswaId,
            'payment_id' => $payment->id,
            'receipt' => $this->formatPaymentReceipt($payment),
            'history_url' => route('admin.keuangan.riwayat-pembayaran.index'),
        ], 201);
    }

    private function formatTagihanRow(Tagihan $row, TagihanCicilanService $cicilanService): array
    {
        $remaining = $row->remaining();
        $payable = $cicilanService->payableAmount($row);
        $paidInstallments = $row->is_cicilan ? (int) ($row->cicilanChildren()->count()) : 0;
        $nextKe = $row->is_cicilan ? $paidInstallments + 1 : null;

        return [
            'id' => $row->id,
            'jenis' => $row->jenis,
            'periode' => $row->displayPeriode(),
            'amount' => $row->displayAmount(),
            'paid' => $row->paid,
            'remaining' => $remaining,
            'payable_amount' => $payable,
            'is_cicilan' => $row->is_cicilan ? 1 : 0,
            'cicilan_urutan' => $nextKe,
            'cicilan_count' => $paidInstallments,
            'status' => $row->status,
            'status_label' => $row->statusLabel(),
            'due_date' => DisplayDate::date($row->due_date),
            'can_pay' => $payable > 0,
            'amount_label' => 'Rp '.number_format($row->displayAmount(), 0, ',', '.'),
            'paid_label' => 'Rp '.number_format($row->paid, 0, ',', '.'),
            'remaining_label' => 'Rp '.number_format($remaining, 0, ',', '.'),
            'payable_label' => 'Rp '.number_format($payable, 0, ',', '.'),
        ];
    }

    private function generatePaymentReference(string $fidbank): string
    {
        $suffix = now()->format('Ymd').'-'.strtoupper(Str::random(6));

        return match ($fidbank) {
            self::FIDBANK_MANUAL_CASH => 'KW-'.$suffix,
            self::FIDBANK_MANUAL_SALDO => 'SK-'.$suffix,
            default => 'KW-'.$suffix,
        };
    }
}
