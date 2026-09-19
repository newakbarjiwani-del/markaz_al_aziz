<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Support\DashboardChart;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $kelasList = Kelas::withCount('siswa')->orderBy('name')->get();

        $byStatus = Siswa::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $charts = [];

        if ($byStatus->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'student-status-chart',
                'Status Siswa',
                $byStatus->all()
            );
        }

        if ($kelasList->isNotEmpty()) {
            $charts[] = DashboardChart::single(
                'student-kelas-chart',
                'Siswa per Kelas',
                'bar',
                $kelasList->pluck('name')->all(),
                $kelasList->pluck('siswa_count')->all(),
                'Jumlah Siswa'
            );
        }

        return view('admin.manajemen-siswa.dashboard', [
            'title' => 'Dashboard Manajemen Siswa',
            'stats' => [
                ['label' => 'Total Siswa', 'value' => Siswa::count(), 'accent' => 'primary'],
                ['label' => 'Siswa Aktif', 'value' => Siswa::where('status', \App\Models\Siswa::STATUS_ACTIVE)->count(), 'accent' => 'green'],
                ['label' => 'Pending', 'value' => Siswa::where('status', \App\Models\Siswa::STATUS_PENDING)->count(), 'accent' => 'accent'],
                ['label' => 'Nonaktif', 'value' => Siswa::where('status', \App\Models\Siswa::STATUS_INACTIVE)->count(), 'accent' => 'red'],
            ],
            'orangTuaStats' => [
                ['label' => 'Total Orang Tua', 'value' => OrangTua::count(), 'accent' => 'purple'],
                ['label' => 'Orang Tua Aktif', 'value' => OrangTua::where('status', 'aktif')->count(), 'accent' => 'green'],
            ],
            'charts' => $charts,
            'kelasList' => $kelasList,
            'recentSiswa' => Siswa::with('kelas')->latest()->limit(5)->get(),
        ]);
    }
}
