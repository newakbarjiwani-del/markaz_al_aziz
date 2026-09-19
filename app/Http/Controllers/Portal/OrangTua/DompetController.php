<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\SccttranCashless;
use App\Services\Finance\SccttranCashlessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DompetController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id')->all();
        $balanceMap = $this->cashlessService->balancesForSiswaIds($childIds);

        $balanceCards = $children->map(fn ($siswa) => [
            'siswa' => $siswa,
            'saldo' => $balanceMap[$siswa->id] ?? 0,
        ]);

        return view('portal.ortu.dompet', [
            'title' => 'Saldo & Transaksi Cashless',
            'children' => $children,
            'balanceCards' => $balanceCards,
            'stats' => $this->cashlessService->statsForSiswaIds($childIds),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = SccttranCashless::query()->with('siswa.kelas');
        $this->applyOrtuSiswaScope($query, $request, 'CUSTID');
        $this->applyDateRange($query, $request, 'TRXDATE');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'description'],
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'NOREFF', 'id'],
        ], function (SccttranCashless $row) {
            return [
                $row->TRXDATE,
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->METODE ?? '-',
                $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
                $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
                $row->NOREFF ?? '-',
                $this->cell(
                    '<button type="button" class="btn-action btn-action-view"'
                    .' data-portal-cashless-detail'
                    .' data-detail-url="'.e(route('portal.ortu.dompet.show', $row)).'"'
                    .' title="Detail transaksi"'
                    .' aria-label="Detail transaksi">'
                    .'<i class="ti ti-eye"></i>'
                    .'<span class="btn-action-label">Detail</span></button>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(SccttranCashless $sccttranCashless): JsonResponse
    {
        abort_unless(
            in_array((int) $sccttranCashless->CUSTID, $this->ortuChildIds(), true),
            403,
            'Transaksi tidak termasuk anak yang terhubung.'
        );

        $sccttranCashless->load(['siswa.kelas', 'user']);

        return $this->jsonSuccess('OK', $this->cashlessService->transactionDetailPayload($sccttranCashless));
    }
}
