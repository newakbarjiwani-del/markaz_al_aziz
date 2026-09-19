<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsSccttranRows;
use App\Http\Traits\PortalAccess;
use App\Models\Sccttran;
use App\Services\Finance\SccttranSaldoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsSccttranRows;
    use PortalAccess;

    public function __construct(
        private readonly SccttranSaldoService $sccttranSaldo,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id')->all();
        $balanceMap = $this->sccttranSaldo->balancesForSiswaIds($childIds);

        $balanceCards = $children->map(fn ($siswa) => [
            'siswa' => $siswa,
            'saldo' => $balanceMap[$siswa->id] ?? 0,
        ]);

        return view('portal.ortu.pembayaran', [
            'title' => 'Saldo Keuangan',
            'children' => $children,
            'balanceCards' => $balanceCards,
            'stats' => $this->sccttranSaldo->statsForSiswaIds($childIds),
            'metodeOptions' => $this->metodeOptions(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $childIds = $this->ortuChildIds();
        abort_if($childIds === [], 403, 'Tidak ada data anak yang terhubung.');

        $selected = $this->selectedOrtuChildId($request);
        $scopedIds = $selected ? [$selected] : $childIds;

        $query = $this->sccttranSaldo->transactionsQueryForSiswaIds($scopedIds, $request)
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'FIDBANK', 'KDCHANNEL', 'siswa.nis', 'siswa.name'],
            // Keep indexes aligned with 9 displayed columns.
            // Student identity columns use TRXDATE fallback ordering.
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'NOREFF', 'KDCHANNEL'],
        ], function (Sccttran $row) {
            return $this->formatSccttranRow($row, true);
        });
    }

    /**
     * @return list<string>
     */
    private function metodeOptions(): array
    {
        return ['TOP UP', 'FROM INVOICE', 'FROM SALDO', 'JURNAL SALDO'];
    }
}
