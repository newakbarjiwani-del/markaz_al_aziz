<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Http\Traits\PortalAccess;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Finance\PembayaranRecordingService;
use App\Services\Finance\SaldoKeuanganService;
use App\Services\Finance\SccttranLogger;
use App\Support\ActionMessage;
use App\Support\DisplayDate;
use App\Support\PaymentMethod;
use App\Support\SoftDeleteRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TagihanController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsPaymentReceipts;
    use PortalAccess;

    private const FIDBANK_SALDO = '1140002';

    public function index(): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id')->all();

        return view('portal.ortu.tagihan', [
            'title' => 'Tagihan Anak',
            'children' => $children,
            'jenisOptions' => $this->jenisOptionsForChildren($childIds),
            'stats' => [
                'total' => Tagihan::rootBill()->whereIn('siswa_id', $childIds)->count(),
                'lunas' => Tagihan::rootBill()->whereIn('siswa_id', $childIds)->paid()->count(),
                'belum_lunas' => Tagihan::rootBill()->whereIn('siswa_id', $childIds)->unpaid()->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Tagihan::query()->rootBill()->with('siswa.kelas');
        $this->applyOrtuSiswaScope($query, $request);
        $this->applyDateRange($query, $request, 'due_date');
        $this->applyTagihanFilters($query, $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['jenis', 'periode', 'fidbank', 'siswa.name', 'siswa.nis'],
            // Keep indexes aligned with 13 displayed columns.
            // Relation/computed columns use safe fallback ordering.
            'orderable' => [
                'created_at', 'created_at', 'created_at', 'created_at',
                'jenis', 'periode', 'amount', 'paid', 'amount', 'status', 'fidbank', 'paid_dt', 'due_date',
            ],
        ], function (Tagihan $tagihan) {
            $sisa = $tagihan->remaining();
            $vano = $tagihan->siswa?->virtualAccountNumber();
            $vanoHtml = $vano
                ? view('components.portal.vano', [
                    'value' => $vano,
                    'label' => '',
                    'compact' => true,
                ])->render()
                : '-';
            $methodLabel = PaymentMethod::label($tagihan->fidbank);

            return [
                $tagihan->siswa?->nis ?? '-',
                $this->cell($vanoHtml, $vano, 'html'),
                $tagihan->siswa?->name ?? '-',
                $tagihan->siswa?->kelas?->name ?? '-',
                $tagihan->jenis,
                $tagihan->displayPeriode(),
                $this->cell('Rp '.number_format($tagihan->amount, 0, ',', '.'), (int) $tagihan->amount, 'number'),
                $this->cell('Rp '.number_format($tagihan->paid, 0, ',', '.'), (int) $tagihan->paid, 'number'),
                $this->cell('Rp '.number_format($sisa, 0, ',', '.'), (int) $sisa, 'number'),
                $this->badgeCell($tagihan->statusLabel(), 'badge '.$tagihan->statusBadgeClass()),
                $methodLabel,
                $this->dateCell($tagihan->paid_dt),
                $this->dateCell($tagihan->due_date),
            ];
        });
    }

    public function unpaid(Request $request, SaldoKeuanganService $saldoService): JsonResponse
    {
        $childIds = $this->ortuChildIds();
        abort_if($childIds === [], 403, 'Tidak ada data anak yang terhubung.');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
        ]);

        $siswaId = (int) $data['siswa_id'];
        abort_unless(in_array($siswaId, $childIds, true), 403, 'Anak tidak terhubung ke akun ini.');

        $siswa = Siswa::query()->with('kelas')->findOrFail($siswaId);
        $saldo = $saldoService->forSiswa($siswa);
        $tagihan = Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->unpaid()
            ->orderBy('due_date')
            ->orderBy('periode')
            ->orderBy('id')
            ->get();

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
                'virtual_account' => $siswa->virtualAccountNumber(),
            ],
            'saldo' => (int) $saldo->balance,
            'saldo_label' => 'Rp '.number_format((int) $saldo->balance, 0, ',', '.'),
            'tagihan' => $tagihan->map(fn (Tagihan $row) => [
                'id' => $row->id,
                'jenis' => $row->jenis,
                'periode' => $row->displayPeriode(),
                'amount' => (int) $row->amount,
                'paid' => (int) $row->paid,
                'remaining' => (int) $row->remaining(),
                'remaining_label' => 'Rp '.number_format((int) $row->remaining(), 0, ',', '.'),
                'due_date' => DisplayDate::date($row->due_date),
                'status_label' => $row->statusLabel(),
            ])->values(),
        ]);
    }

    public function pay(
        Request $request,
        PembayaranRecordingService $recordingService,
        SaldoKeuanganService $saldoService,
        SccttranLogger $sccttranLogger,
    ): JsonResponse {
        $childIds = $this->ortuChildIds();
        abort_if($childIds === [], 403, 'Tidak ada data anak yang terhubung.');

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.tagihan_id' => ['required', SoftDeleteRules::exists('tagihan')],
        ]);

        $siswaId = (int) $data['siswa_id'];
        abort_unless(in_array($siswaId, $childIds, true), 403, 'Anak tidak terhubung ke akun ini.');

        $paidAt = now();
        $reference = 'ORTU-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $paymentItems = [];

        try {
            $payment = DB::transaction(function () use (
                $data,
                $siswaId,
                $paidAt,
                $reference,
                $recordingService,
                $saldoService,
                $sccttranLogger,
                &$paymentItems,
            ) {
                $siswa = Siswa::query()->lockForUpdate()->findOrFail($siswaId);
                $siswa->assertCanTransact('siswa_id');
                $saldo = $saldoService->forSiswa($siswa, lock: true);
                $saldoBalance = (int) $saldo->balance;
                $totalNeeded = 0;

                foreach ($data['items'] as $index => $item) {
                    $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($item['tagihan_id']);

                    if ((int) $tagihan->siswa_id !== $siswaId) {
                        throw ValidationException::withMessages([
                            "items.{$index}.tagihan_id" => 'Tagihan tidak milik anak yang dipilih.',
                        ]);
                    }

                    $remaining = (int) $tagihan->remaining();
                    if ($remaining <= 0) {
                        throw ValidationException::withMessages([
                            "items.{$index}.tagihan_id" => "Tagihan {$tagihan->jenis} sudah lunas.",
                        ]);
                    }

                    $totalNeeded += $remaining;
                    $paymentItems[] = [
                        'tagihan' => $tagihan,
                        'amount' => $remaining,
                        'sccttran' => null,
                    ];
                }

                if ($saldoBalance < $totalNeeded) {
                    throw ValidationException::withMessages([
                        'siswa_id' => 'Saldo keuangan tidak mencukupi. Saldo: Rp '.number_format($saldoBalance, 0, ',', '.')
                            .', dibutuhkan: Rp '.number_format($totalNeeded, 0, ',', '.'),
                    ]);
                }

                foreach ($paymentItems as $index => $paymentItem) {
                    $amount = (int) $paymentItem['amount'];
                    $saldoService->debit($saldo, $amount);
                    $saldo->refresh();

                    $paymentItems[$index]['sccttran'] = $sccttranLogger->fromSaldo($siswa->id, $amount, [
                        'trxdate' => $paidAt,
                        'refno' => $reference,
                    ]);
                }

                return $recordingService->record(
                    $siswa,
                    self::FIDBANK_SALDO,
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
            ? ActionMessage::withSubject("Pembayaran {$amountText} dari saldo berhasil", $subject)
            : ActionMessage::withSubject("{$processed} tagihan ({$amountText}) berhasil dibayar dari saldo", $subject);

        return $this->jsonSuccess($message, [
            'processed' => $processed,
            'total_amount' => (float) $payment->total_amount,
            'siswa_id' => $siswaId,
            'payment_id' => $payment->id,
            'receipt' => $this->formatPaymentReceipt($payment),
        ], 201);
    }

    /**
     * @param  list<int>  $childIds
     * @return list<string>
     */
    private function jenisOptionsForChildren(array $childIds): array
    {
        if ($childIds === []) {
            return [];
        }

        return Tagihan::query()
            ->rootBill()
            ->whereIn('siswa_id', $childIds)
            ->distinct()
            ->orderBy('jenis')
            ->pluck('jenis')
            ->filter()
            ->values()
            ->all();
    }

    private function applyTagihanFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status') && in_array((string) $request->input('status'), ['0', '1', '2'], true)) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->string('jenis')->toString());
        }
    }
}
