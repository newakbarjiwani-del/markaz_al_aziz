<?php

namespace App\Http\Controllers\Portal\Siswa;

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
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.dompet', [
            'title' => 'Saldo & Transaksi Cashless',
            'siswa' => $siswa,
            'saldoCashless' => $this->cashlessService->balanceForSiswa($siswa->id),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();
        $query = SccttranCashless::query()->where('CUSTID', $siswa->id);
        $this->applyDateRange($query, $request, 'TRXDATE');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'description'],
            'orderable' => ['TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'NOREFF', 'id'],
        ], function (SccttranCashless $row) {
            return [
                $row->TRXDATE,
                $row->METODE ?? '-',
                $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
                $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
                $row->NOREFF ?? '-',
                $this->cell(
                    '<button type="button" class="btn-action btn-action-view"'
                    .' data-portal-cashless-detail'
                    .' data-detail-url="'.e(route('portal.siswa.dompet.show', $row)).'"'
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
        $siswa = $this->linkedSiswa();

        abort_unless(
            (int) $sccttranCashless->CUSTID === (int) $siswa->id,
            403,
            'Transaksi bukan milik akun siswa ini.'
        );

        return $this->jsonSuccess('OK', $this->cashlessService->transactionDetailPayload($sccttranCashless));
    }
}
