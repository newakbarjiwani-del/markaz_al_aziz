<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Support\DashboardChart;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $byStatus = Guru::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $dayLabels = DashboardChart::dayLabels();
        $hadirPerHari = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = today()->subDays($daysAgo);

            return AbsensiGuru::whereDate('date', $date)->where('status', 'hadir')->count();
        })->all();

        $charts = [];

        if ($byStatus->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'teacher-status-chart',
                'Status Guru',
                $byStatus->all()
            );
        }

        $charts[] = DashboardChart::single(
                'teacher-hadir-chart',
                'Kehadiran Guru (7 Hari)',
                'bar',
                $dayLabels,
                $hadirPerHari,
                'Hadir'
        );

        return view('admin.manajemen-guru.dashboard', [
            'title' => 'Dashboard Manajemen Guru',
            'stats' => [
                ['label' => 'Total Guru', 'value' => Guru::count(), 'accent' => 'primary'],
                ['label' => 'Guru Aktif', 'value' => Guru::where('status', 'aktif')->count(), 'accent' => 'green'],
                ['label' => 'Hadir Hari Ini', 'value' => AbsensiGuru::whereDate('date', today())->where('status', 'hadir')->count(), 'accent' => 'accent'],
            ],
            'charts' => $charts,
            'recentGuru' => Guru::latest()->limit(5)->get(),
        ]);
    }
}
