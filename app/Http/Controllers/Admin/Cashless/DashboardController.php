<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Models\PenarikanPendapatanKantin;
use App\Models\SccttranCashless;
use App\Services\Finance\SccttranCashlessService;
use App\Support\AdminSchoolScope;
use App\Support\DashboardChart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
    ) {}

    public function index(): View
    {
        $this->authorize('cashless.view');

        $sekolahId = AdminSchoolScope::operatorSekolahId();
        $monthStart = now()->startOfMonth();

        $belanjaQuery = $this->belanjaQuery($sekolahId);
        $omzetHariIni = (int) (clone $belanjaQuery)->whereDate('TRXDATE', today())->sum('DEBET');
        $omzetBulanIni = (int) (clone $belanjaQuery)->where('TRXDATE', '>=', $monthStart)->sum('DEBET');
        $omzetAll = (int) (clone $belanjaQuery)->sum('DEBET');

        $penarikanQuery = PenarikanPendapatanKantin::query()
            ->when($sekolahId !== null, fn (Builder $q) => $q->where('sekolah_id', $sekolahId));
        $penarikanBulanIni = (int) (clone $penarikanQuery)->where('settled_at', '>=', $monthStart)->sum('amount');
        $totalPenarikan = (int) (clone $penarikanQuery)->sum('amount');
        $sisaBelumDitarik = max(0, $omzetAll - $totalPenarikan);

        $global = $this->cashlessService->globalStats($sekolahId);

        $byMetode = $this->scopedCashlessQuery($sekolahId)
            ->where('TRXDATE', '>=', $monthStart)
            ->select('METODE', DB::raw('count(*) as total'))
            ->groupBy('METODE')
            ->pluck('total', 'METODE');

        $dayLabels = DashboardChart::dayLabels();
        $omzetPerDay = collect(range(6, 0))->map(function (int $daysAgo) use ($sekolahId) {
            $date = today()->subDays($daysAgo);

            return (int) $this->belanjaQuery($sekolahId)->whereDate('TRXDATE', $date)->sum('DEBET');
        })->all();

        $charts = [];

        if ($byMetode->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'cashless-type-chart',
                'Transaksi Bulan Ini per Metode',
                $byMetode->all()
            );
        }

        $charts[] = DashboardChart::single(
            'cashless-omzet-chart',
            'Omzet BELANJA Kantin (7 Hari)',
            'bar',
            $dayLabels,
            $omzetPerDay,
            'Omzet (Rp)'
        );

        return view('admin.dompet-digital.dashboard', [
            'title' => 'Dashboard Cashless',
            'stats' => [
                [
                    'label' => 'Total Saldo Cashless',
                    'value' => 'Rp '.number_format($global['total_saldo'], 0, ',', '.'),
                    'accent' => 'primary',
                    'currency' => true,
                    'hint' => $global['siswa_bersaldo'].' siswa bersaldo',
                ],
                [
                    'label' => 'Omzet Kantin Hari Ini',
                    'value' => 'Rp '.number_format($omzetHariIni, 0, ',', '.'),
                    'accent' => 'green',
                    'currency' => true,
                ],
                [
                    'label' => 'Omzet Kantin Bulan Ini',
                    'value' => 'Rp '.number_format($omzetBulanIni, 0, ',', '.'),
                    'accent' => 'accent',
                    'currency' => true,
                ],
                [
                    'label' => 'Sisa Belum Ditarik',
                    'value' => 'Rp '.number_format($sisaBelumDitarik, 0, ',', '.'),
                    'accent' => 'purple',
                    'currency' => true,
                    'hint' => 'Penarikan bulan ini Rp '.number_format($penarikanBulanIni, 0, ',', '.'),
                ],
            ],
            'charts' => $charts,
            'recentTransaksi' => $this->scopedCashlessQuery($sekolahId)
                ->with('siswa')
                ->latest('TRXDATE')
                ->latest('id')
                ->limit(5)
                ->get(),
            'recentPenarikan' => (clone $penarikanQuery)
                ->with(['kantinUser', 'settledByUser'])
                ->orderByDesc('settled_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }

    private function scopedCashlessQuery(?int $sekolahId): Builder
    {
        return SccttranCashless::query()
            ->when($sekolahId !== null, function (Builder $q) use ($sekolahId) {
                $q->whereHas('siswa', fn (Builder $siswa) => $siswa->where('sekolah_id', $sekolahId));
            });
    }

    private function belanjaQuery(?int $sekolahId): Builder
    {
        return SccttranCashless::query()
            ->where('METODE', 'BELANJA')
            ->where('DEBET', '>', 0)
            ->when($sekolahId !== null, function (Builder $q) use ($sekolahId) {
                $q->whereHas('user', fn (Builder $user) => $user->where('sekolah_id', $sekolahId));
            });
    }
}
