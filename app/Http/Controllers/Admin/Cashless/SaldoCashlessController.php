<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Services\Finance\SccttranCashlessService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\InfaqTiers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaldoCashlessController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private readonly SccttranCashlessService $sccttranCashless,
    ) {}

    public function index(): View
    {
        return view('admin.dompet-digital.saldo-cashless', [
            'title' => 'Saldo Cashless',
            'classes' => $this->classesList(),
            'stats' => $this->sccttranCashless->globalStats(AdminSchoolScope::operatorSekolahId()),
            'manualSaldoEnabled' => (bool) config('school.manual_saldo_cashless_adjustment_enabled', false),
            'topupStoreUrl' => route('admin.dompet-digital.topup-saldo.store'),
            'withdrawStoreUrl' => route('admin.dompet-digital.withdraw-saldo.store'),
            'infaqMode' => InfaqTiers::mode(),
            'infaqEnabled' => InfaqTiers::isEnabled(),
            'infaqTiers' => InfaqTiers::allTiers(),
            'infaqMax' => InfaqTiers::max(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->sccttranCashless->siswaWithBalanceQuery(onlyPositiveBalance: true);
        $query->leftJoin('rfid', 'rfid.siswa_id', '=', 'siswa.id')->with('rfid');

        $this->applyKelasFilter($query, $request, '_self');

        if ($request->filled('q')) {
            $term = trim($request->string('q')->toString());
            if ($term !== '') {
                $like = '%'.$term.'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('nis', 'like', $like)
                        ->orWhere('rfid.uid', 'like', $like);
                });
            }
        }

        return $this->datatableResponse($request, $query, [
            'searchable' => ['siswa.nis', 'siswa.name', 'rfid.uid', 'kelas.name'],
            'orderable' => ['siswa.nis', 'siswa.name', 'siswa.kelas_id', 'rfid.uid', 'cashless_balance', 'last_trx_at'],
        ], function (Siswa $row) {
            $balance = (int) ($row->cashless_balance ?? 0);
            $lastTrx = $row->last_trx_at
                ? $row->last_trx_at
                : '-';

            return [
                $row->nis,
                $row->name,
                $row->kelas?->name ?? '-',
                $row->rfidUid() ?? '-',
                $this->cell('Rp '.number_format($balance, 0, ',', '.'), $balance, 'number'),
                $lastTrx,
                $this->cell(
                    '<button type="button" class="btn-secondary btn-sm" data-cashless-detail data-siswa-id="'.$row->id.'" data-siswa-label="'.e(ActionMessage::siswa($row)).'">Detail</button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function transactions(Request $request, Siswa $siswa): JsonResponse
    {
        $query = $this->sccttranCashless->transactionsQuery($siswa->id, $request)
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'wallet', 'description'],
            'orderable' => ['TRXDATE', 'METODE', 'wallet', 'KREDIT', 'DEBET', 'description'],
        ], function (SccttranCashless $row) {
            return [
                $row->TRXDATE,
                $row->METODE ?? '-',
                strtoupper($row->wallet ?? '-'),
                $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
                $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
                $row->description ?: ($row->NOREFF ?? '-'),
            ];
        });
    }

    public function show(Siswa $siswa): JsonResponse
    {
        $siswa->load(['kelas', 'rfid']);

        return $this->jsonSuccess('OK', [
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name,
                'rfid_uid' => $siswa->rfidUid(),
            ],
            'balance' => $this->sccttranCashless->balanceForSiswa($siswa->id),
        ]);
    }
}
