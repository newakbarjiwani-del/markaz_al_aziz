<?php

namespace App\Http\Controllers\Admin\Perizinan;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use App\Support\AdminSchoolScope;
use App\Support\DashboardChart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $query = Perizinan::query()->with(['siswa.kelas', 'sekolah']);
        AdminSchoolScope::apply($query);

        $totalCount = (clone $query)->count();
        $aktifCount = (clone $query)->where('status', Perizinan::STATUS_DISETUJUI)
            ->whereNull('tgl_kembali_aktual')
            ->where('tgl_sampai', '>=', Carbon::now())
            ->count();

        $kembaliCount = (clone $query)->where('status', Perizinan::STATUS_KEMBALI)->count();

        $terlambatCount = (clone $query)->where(function ($q) {
            $q->where('status', Perizinan::STATUS_TERLAMBAT)
                ->orWhere(function ($sq) {
                    $sq->where('status', Perizinan::STATUS_DISETUJUI)
                        ->whereNull('tgl_kembali_aktual')
                        ->where('tgl_sampai', '<', Carbon::now());
                });
        })->count();

        $pendingCount = (clone $query)->where('status', Perizinan::STATUS_PENDING)->count();

        // Breakdown per Jenis
        $breakdownJenis = [
            'keluar_masuk' => (clone $query)->where('jenis_perizinan', Perizinan::JENIS_KELUAR_MASUK)->count(),
            'keluar_masuk_pondok' => (clone $query)->where('jenis_perizinan', Perizinan::JENIS_KELUAR_MASUK_PONDOK)->count(),
            'pulang_libur' => (clone $query)->where('jenis_perizinan', Perizinan::JENIS_PULANG_LIBUR)->count(),
        ];

        // Chart 1: Breakdown per Jenis Perizinan (Doughnut Chart)
        $chartJenis = DashboardChart::doughnutFromMap(
            'jenis_perizinan_chart',
            'Distribusi Jenis Perizinan',
            [
                'Izin Keluar Masuk Harian' => $breakdownJenis['keluar_masuk'],
                'Izin Keluar Masuk Pondok' => $breakdownJenis['keluar_masuk_pondok'],
                'Izin Pulang Libur' => $breakdownJenis['pulang_libur'],
            ]
        );

        // Chart 2: Tren Perizinan 6 Bulan Terakhir
        $months = [];
        $trendData = [];
        for ($i = 5; $i >= 0; $i--) {
            $dt = Carbon::now()->subMonths($i);
            $months[] = $dt->isoFormat('MMM YYYY');
            $trendData[] = (clone $query)
                ->whereYear('tgl_mulai', $dt->year)
                ->whereMonth('tgl_mulai', $dt->month)
                ->count();
        }

        $chartTrend = DashboardChart::single(
            'tren_perizinan_chart',
            'Tren Perizinan',
            'bar',
            $months,
            $trendData,
            'Jumlah Perizinan'
        );

        // Recent Activity (10 perizinan terbaru)
        $recentPerizinan = (clone $query)
            ->latest()
            ->take(10)
            ->get();

        return view('admin.perizinan.dashboard', [
            'title' => 'Dashboard Perizinan',
            'schools' => AdminSchoolScope::schools(),
            'totalCount' => $totalCount,
            'aktifCount' => $aktifCount,
            'kembaliCount' => $kembaliCount,
            'terlambatCount' => $terlambatCount,
            'pendingCount' => $pendingCount,
            'breakdownJenis' => $breakdownJenis,
            'charts' => [$chartJenis, $chartTrend],
            'recentPerizinan' => $recentPerizinan,
        ]);
    }
}
