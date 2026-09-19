<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\SccttranCashless;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.dompet-digital.transaksi', [
            'title' => 'Riwayat Transaksi Cashless',
            'classes' => $this->classesList(),
            'metodeOptions' => $this->metodeOptions(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->filteredQuery($request)->with('siswa.kelas');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['METODE', 'NOREFF', 'wallet'],
            'orderable' => ['TRXDATE', 'TRXDATE', 'TRXDATE', 'TRXDATE', 'METODE', 'KREDIT', 'DEBET', 'wallet', 'NOREFF'],
        ], function (SccttranCashless $row) {
            return [
                $row->TRXDATE,
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->METODE ?? '-',
                $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
                $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
                strtoupper($row->wallet ?? '-'),
                $row->NOREFF ?? '-',
            ];
        });
    }

    public function summaryData(Request $request): JsonResponse
    {
        $row = $this->filteredQuery($request)
            ->selectRaw('COALESCE(SUM(KREDIT), 0) as total_kredit')
            ->selectRaw('COALESCE(SUM(DEBET), 0) as total_debet')
            ->first();

        $totalKredit = (int) ($row->total_kredit ?? 0);
        $totalDebet = (int) ($row->total_debet ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'kredit' => $totalKredit,
                'debet' => $totalDebet,
                'net' => $totalKredit - $totalDebet,
            ],
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = SccttranCashless::query();
        $this->applyKelasFilter($query, $request);
        $this->applyDateRange($query, $request, 'TRXDATE');

        if ($request->filled('metode')) {
            $metode = $request->string('metode')->toString();
            if (in_array($metode, $this->metodeOptions(), true)) {
                $query->where('METODE', $metode);
            }
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function metodeOptions(): array
    {
        return [
            'TOP UP',
            'TARIK SALDO',
            'BELANJA',
            'PINDAH SALDO',
            'ADMIN FEE',
            'INFAQ',
        ];
    }
}
