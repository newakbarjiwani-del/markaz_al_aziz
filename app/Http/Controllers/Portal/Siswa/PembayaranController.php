<?php

namespace App\Http\Controllers\Portal\Siswa;

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
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.pembayaran', [
            'title' => 'Saldo Keuangan',
            'siswa' => $siswa,
            'stats' => $this->sccttranSaldo->statsForSiswaIds([$siswa->id]),
            'metodeOptions' => $this->metodeOptions(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();
        $query = $this->sccttranSaldo->transactionsQuery($siswa->id, $request)
            ->orderByDesc('TRXDATE')
            ->orderByDesc('id');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'FIDBANK', 'KDCHANNEL'],
            'orderable' => ['TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'NOREFF', 'KDCHANNEL'],
        ], function (Sccttran $row) {
            return $this->formatSccttranRow($row);
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
