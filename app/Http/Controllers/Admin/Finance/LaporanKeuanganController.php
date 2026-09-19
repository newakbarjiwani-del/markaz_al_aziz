<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\FormatsPaymentReceipts;
use App\Models\JenisTagihan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanKeuanganController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use FormatsPaymentReceipts;

    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
        $jenisTagihanId = $request->filled('jenis_tagihan_id')
            ? (int) $request->input('jenis_tagihan_id')
            : null;

        $tagihanQuery = Tagihan::query()
            ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->when($jenisTagihanId, fn (Builder $q) => $q->where('jenis_tagihan_id', $jenisTagihanId));

        $pembayaranQuery = Pembayaran::query()
            ->whereBetween('paid_dt', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
        $this->applyJenisTagihanFilter($pembayaranQuery, $request);

        $byJenis = Tagihan::query()
            ->select('jenis', DB::raw('SUM(amount) as total'), DB::raw('SUM(paid) as terbayar'))
            ->when($jenisTagihanId, fn (Builder $q) => $q->where('jenis_tagihan_id', $jenisTagihanId))
            ->groupBy('jenis')
            ->get();

        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', paid_dt)"
            : "DATE_FORMAT(paid_dt, '%Y-%m')";

        $monthlyTrendQuery = Pembayaran::query()
            ->select(DB::raw("{$monthExpression} as bulan"), DB::raw('SUM(total_amount) as total'))
            ->where('paid_dt', '>=', now()->subMonths(5)->startOfMonth());
        $this->applyJenisTagihanFilter($monthlyTrendQuery, $request);
        $monthlyTrend = $monthlyTrendQuery
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        $tunggakanQuery = Tagihan::query()
            ->when($jenisTagihanId, fn (Builder $q) => $q->where('jenis_tagihan_id', $jenisTagihanId));
        $totalAmount = (clone $tunggakanQuery)->sum('amount');
        $totalPaid = (clone $tunggakanQuery)->sum('paid');

        return view('admin.keuangan.laporan-keuangan', [
            'title' => 'Laporan Keuangan',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'classes' => $this->classesList(),
            'jenisTagihanList' => JenisTagihan::query()->active()->ordered()->get(['id', 'name']),
            'selectedJenisTagihanId' => $jenisTagihanId,
            'stats' => [
                'tagihan_bulan' => (clone $tagihanQuery)->sum('amount'),
                'penerimaan_bulan' => (clone $pembayaranQuery)->sum('total_amount'),
                'tunggakan' => max(0, $totalAmount - $totalPaid),
                'tingkat_pelunasan' => $totalAmount > 0
                    ? round(($totalPaid / $totalAmount) * 100, 1)
                    : 0,
            ],
            'byJenis' => $byJenis,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Pembayaran::query()
            ->with(['siswa.kelas', 'details.tagihan'])
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method));

        $this->applyDateRange($query, $request, 'paid_dt');
        $this->applyKelasFilter($query, $request, 'siswa');
        $this->applyJenisTagihanFilter($query, $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['method', 'reference'],
            'orderable' => ['total_amount', 'paid_dt', 'created_at'],
        ], function (Pembayaran $row) {
            $firstDetail = $row->details->first();

            return [
                $row->paid_dt,
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $firstDetail?->tagihan?->jenis ?? '-',
                $row->details->count() > 1
                    ? $row->details->count().' tagihan'
                    : ($firstDetail?->tagihan?->displayPeriode() ?? '-'),
                'Rp '.number_format((float) $row->total_amount, 0, ',', '.'),
                $this->paymentMethodLabel($row->method),
                $row->reference ?? '-',
            ];
        });
    }

    /**
     * Limit payments to those that include at least one bill of the selected jenis tagihan.
     */
    private function applyJenisTagihanFilter(Builder $query, Request $request): Builder
    {
        if (! $request->filled('jenis_tagihan_id')) {
            return $query;
        }

        $jenisTagihanId = (int) $request->input('jenis_tagihan_id');

        return $query->whereHas(
            'details.tagihan',
            fn (Builder $q) => $q->where('jenis_tagihan_id', $jenisTagihanId)
        );
    }
}
