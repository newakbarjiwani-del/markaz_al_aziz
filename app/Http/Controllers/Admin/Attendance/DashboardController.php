<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use App\Support\AttendanceDashboard;
use App\Support\DashboardChart;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        $todayByStatus = AttendanceDashboard::distinctStudentsByStatus($today);

        $dayLabels = DashboardChart::dayLabels();
        $hadirPerHari = collect(range(6, 0))->map(function (int $daysAgo) use ($today) {
            return AttendanceDashboard::distinctStudentsOnDate(
                $today->copy()->subDays($daysAgo),
                ['hadir']
            );
        })->all();

        $alphaPerHari = collect(range(6, 0))->map(function (int $daysAgo) use ($today) {
            return AttendanceDashboard::distinctStudentsOnDate(
                $today->copy()->subDays($daysAgo),
                ['alpha']
            );
        })->all();

        $charts = [];

        if ($todayByStatus !== []) {
            $charts[] = DashboardChart::doughnutFromMap(
                'attendance-today-chart',
                'Absensi Siswa Hari Ini',
                $todayByStatus
            );
        }

        $charts[] = DashboardChart::make(
            'attendance-week-chart',
            'Tren Absensi (7 Hari)',
            'line',
            $dayLabels,
            [
                ['label' => 'Hadir', 'data' => $hadirPerHari],
                ['label' => 'Alpha', 'data' => $alphaPerHari],
            ]
        );

        return view('admin.absensi.dashboard', [
            'title' => 'Dashboard Absensi',
            'stats' => [
                ['label' => 'Hadir Siswa Hari Ini', 'value' => (int) ($todayByStatus['hadir'] ?? 0), 'accent' => 'primary'],
                ['label' => 'Alpha Hari Ini', 'value' => (int) ($todayByStatus['alpha'] ?? 0), 'accent' => 'red'],
                ['label' => 'Hadir Guru Hari Ini', 'value' => AbsensiGuru::whereDate('date', $today)->where('status', 'hadir')->count(), 'accent' => 'green'],
                ['label' => 'Total Siswa Aktif', 'value' => Siswa::where('status', Siswa::STATUS_ACTIVE)->count(), 'accent' => 'accent'],
            ],
            'charts' => $charts,
            'recentAbsensi' => AbsensiSiswa::with('siswa')->whereDate('date', $today)->latest()->limit(5)->get(),
        ]);
    }
}
