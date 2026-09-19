<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\Siswa;
use App\Services\Finance\SccttranLogger;
use App\Services\Finance\SccttranCashlessService;
use App\Services\Finance\SccttranSaldoService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaldoSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private readonly SccttranSaldoService $sccttranSaldo,
        private readonly SccttranCashlessService $sccttranCashless,
        private readonly SccttranLogger $sccttranLogger,
    ) {}

    public function index(): View
    {
        return view('admin.keuangan.saldo-siswa', [
            'title' => 'Saldo Siswa',
            'classes' => $this->classesList(),
            'manualSaldoEnabled' => (bool) config('school.manual_saldo_keuangan_adjustment_enabled', false),
            'metodeOptions' => $this->metodeOptions(),
            'stats' => $this->sccttranSaldo->globalStats(AdminSchoolScope::operatorSekolahId()),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->sccttranSaldo->siswaWithBalanceQuery(onlyPositiveBalance: true);

        $this->applyKelasFilter($query, $request, '_self');
        $this->applySiswaSearchFilter($query, $request, '_self');

        if ($request->boolean('punya_transaksi')) {
            $query->whereHas('sccttran');
        }

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nis', 'name', 'kelas.name'],
            // Keep indexes aligned with 6 displayed columns.
            'orderable' => ['nis', 'name', 'last_trx_at', 'finance_balance', 'last_trx_at', 'last_trx_at'],
        ], function (Siswa $row) {
            $balance = (int) ($row->finance_balance ?? 0);
            $lastTrx = $row->last_trx_at
                ? $row->last_trx_at
                : '-';

            return [
                $row->nis,
                $row->name,
                $row->kelas?->name ?? '-',
                $this->cell('Rp '.number_format($balance, 0, ',', '.'), $balance, 'number'),
                $lastTrx,
                $this->cell(
                    '<button type="button" class="btn-secondary btn-sm" data-saldo-detail data-siswa-id="'.$row->id.'" data-siswa-label="'.e(ActionMessage::siswa($row)).'">Detail</button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function transactions(Request $request, Siswa $siswa): JsonResponse
    {
        $query = $this->sccttranSaldo->transactionsQuery($siswa->id, $request)
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'FIDBANK', 'KDCHANNEL'],
            'orderable' => ['TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'NOREFF'],
        ], function (Sccttran $row) {
            return [
                $row->TRXDATE,
                $row->METODE ?? '-',
                $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
                $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
                $row->NOREFF ?? '-',
                $row->KDCHANNEL ?? '-',
                $row->FIDBANK ?? '-',
            ];
        });
    }

    public function show(Siswa $siswa): JsonResponse
    {
        $siswa->load('kelas');

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
                'virtual_account' => $siswa->virtualAccountNumber(),
            ],
            'balance' => $this->sccttranSaldo->balanceForSiswa($siswa->id),
            'cashless_balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
        ]);
    }

    public function adjust(Request $request): JsonResponse
    {
        if (! config('school.manual_saldo_keuangan_adjustment_enabled', false)) {
            return $this->jsonError('Penyesuaian saldo manual sementara dinonaktifkan.', null, 403);
        }

        $data = $request->validate([
            'siswa_id' => ['required', SoftDeleteRules::exists('siswa')],
            'type' => 'required|in:tambah,kurang',
            'amount' => 'required|numeric|min:1000',
        ]);

        $amount = (int) round($data['amount']);
        $siswa = Siswa::with('kelas')->findOrFail($data['siswa_id']);
        try {
            $siswa->assertCanTransact();
        } catch (ValidationException $e) {
            return $this->jsonError(
                collect($e->errors())->flatten()->first() ?? 'Siswa tidak aktif.',
                $e->errors(),
                422
            );
        }
        $balance = $this->sccttranSaldo->balanceForSiswa($siswa->id);

        if ($data['type'] === 'kurang' && $balance < $amount) {
            return $this->jsonError('Saldo tidak mencukupi.');
        }

        $refno = 'JRN-'.now()->format('YmdHis').'-'.random_int(1000, 9999);

        DB::transaction(function () use ($siswa, $amount, $data, $refno): void {
            $this->sccttranLogger->jurnalSaldo($siswa->id, $amount, $data['type'], [
                'refno' => $refno,
                'trxdate' => now(),
            ]);

            $saldo = SaldoKeuangan::query()
                ->where('siswa_id', $siswa->id)
                ->lockForUpdate()
                ->first();

            if ($saldo === null) {
                SaldoKeuangan::create(['siswa_id' => $siswa->id, 'balance' => 0]);
                $saldo = SaldoKeuangan::query()
                    ->where('siswa_id', $siswa->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($data['type'] === 'tambah') {
                $saldo->increment('balance', $amount);
            } else {
                $saldo->decrement('balance', $amount);
            }
        });

        $verb = $data['type'] === 'tambah' ? 'ditambahkan' : 'dikurangi';

        return $this->jsonSuccess(
            ActionMessage::withSubject(
                'Saldo '.ActionMessage::rupiah($amount).' berhasil '.$verb,
                ActionMessage::siswa($siswa)
            ),
            [
                'balance' => $this->sccttranSaldo->balanceForSiswa($siswa->id),
                'siswa' => $siswa,
            ]
        );
    }

    /**
     * @return list<string>
     */
    private function metodeOptions(): array
    {
        return ['TOP UP', 'FROM INVOICE', 'FROM SALDO', 'JURNAL SALDO'];
    }
}
