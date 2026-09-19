<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Pembayaran;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Support\AttendanceDashboard;
use App\Support\DashboardChart;
use App\Support\PortalGreeting;
use App\Support\TagihanDashboard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();
        $monthStart = now()->startOfMonth();

        $totalSiswaAktif = Siswa::query()->where('status', Siswa::STATUS_ACTIVE)->count();
        $todayByStatus = AttendanceDashboard::distinctStudentsByStatus($today);
        $hadirHariIni = (int) ($todayByStatus['hadir'] ?? 0);

        $byStatus = TagihanDashboard::countsByStatusLabel();

        $penerimaanBulanIni = (float) Pembayaran::query()
            ->where('paid_dt', '>=', $monthStart)
            ->sum('total_amount');

        $tagihanBelumLunas = TagihanDashboard::unpaidCount();
        $tagihanTerlambat = TagihanDashboard::overdueCount($today);

        $attendanceRate = $totalSiswaAktif > 0
            ? (int) round(($hadirHariIni / $totalSiswaAktif) * 100)
            : 0;

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

        $monthLabels = DashboardChart::monthLabels();
        $monthlyPenerimaan = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return (float) Pembayaran::query()
                ->whereYear('paid_dt', $date->year)
                ->whereMonth('paid_dt', $date->month)
                ->sum('total_amount');
        })->all();

        $charts = [
            DashboardChart::make(
                'admin-attendance-trend-chart',
                'Tren Kehadiran (7 Hari)',
                'line',
                $dayLabels,
                [
                    ['label' => 'Hadir', 'data' => $hadirPerHari],
                    ['label' => 'Alpha', 'data' => $alphaPerHari],
                ],
            ),
            DashboardChart::single(
                'admin-finance-monthly-chart',
                'Penerimaan SPP (6 Bulan)',
                'bar',
                $monthLabels,
                $monthlyPenerimaan,
                'Penerimaan',
            ),
        ];

        if ($byStatus->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'admin-tagihan-status-chart',
                'Status Tagihan',
                $byStatus->all(),
            );
        }

        $kantinHariIni = SccttranCashless::query()
            ->where('METODE', 'BELANJA')
            ->whereDate('TRXDATE', $today)
            ->count();

        return view('admin.dashboard', [
            'title' => '',
            'greeting' => PortalGreeting::salutation(),
            'greetingIcon' => PortalGreeting::icon(),
            'userName' => auth()->user()->name,
            'stats' => [
                [
                    'label' => 'Siswa Aktif',
                    'value' => $totalSiswaAktif,
                    'accent' => 'primary',
                    'iconName' => 'school',
                    'hint' => Siswa::count().' total terdaftar',
                ],
                [
                    'label' => 'Guru',
                    'value' => Guru::query()->where('status', 'aktif')->count(),
                    'accent' => 'purple',
                    'iconName' => 'chalkboard',
                    'hint' => Guru::count().' total data',
                ],
                [
                    'label' => 'Tagihan Aktif',
                    'value' => $tagihanBelumLunas,
                    'accent' => 'accent',
                    'iconName' => 'receipt-2',
                    'hint' => $tagihanTerlambat.' terlambat',
                ],
                [
                    'label' => 'Hadir Hari Ini',
                    'value' => $hadirHariIni,
                    'accent' => 'green',
                    'iconName' => 'calendar-check',
                    'hint' => $attendanceRate.'% dari siswa aktif',
                ],
            ],
            'highlights' => [
                [
                    'label' => 'Tingkat Kehadiran',
                    'value' => $attendanceRate.'%',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Penerimaan Bulan Ini',
                    'value' => 'Rp '.number_format($penerimaanBulanIni, 0, ',', '.'),
                    'tone' => 'accent',
                    'hint' => 'Tidak termasuk pembayaran yang dibatalkan.',
                ],
                [
                    'label' => 'Transaksi Kantin Hari Ini',
                    'value' => $kantinHariIni,
                    'tone' => 'primary',
                ],
            ],
            'attendanceToday' => [
                'hadir' => (int) ($todayByStatus['hadir'] ?? 0),
                'alpha' => (int) ($todayByStatus['alpha'] ?? 0),
                'izin' => (int) ($todayByStatus['izin'] ?? 0),
                'sakit' => (int) ($todayByStatus['sakit'] ?? 0),
                'guru_hadir' => AbsensiGuru::query()->whereDate('date', $today)->where('status', 'hadir')->count(),
            ],
            'priorities' => array_values(array_filter([
                $tagihanTerlambat > 0 ? [
                    'icon' => 'alert-triangle',
                    'tone' => 'accent',
                    'title' => $tagihanTerlambat.' tagihan melewati jatuh tempo',
                    'description' => 'Segera tindaklanjuti pembayaran SPP yang terlambat.',
                    'route' => 'admin.keuangan.tagihan.index',
                ] : null,
                ($todayByStatus['alpha'] ?? 0) > 0 ? [
                    'icon' => 'user-x',
                    'tone' => 'red',
                    'title' => ($todayByStatus['alpha'] ?? 0).' siswa alpha hari ini',
                    'description' => 'Pantau rekap absensi untuk tindak lanjut wali kelas.',
                    'route' => 'admin.absensi.rekap-presensi',
                ] : null,
            ])),
            'modules' => [
                ['label' => 'Manajemen Siswa', 'description' => 'Data siswa, orang tua, kartu pelajar', 'route' => 'admin.manajemen-siswa.dashboard', 'icon' => 'school', 'tone' => 'primary'],
                ['label' => 'Manajemen Guru', 'description' => 'Data guru, profil, kartu identitas', 'route' => 'admin.manajemen-guru.dashboard', 'icon' => 'chalkboard', 'tone' => 'purple'],
                ['label' => 'Keuangan', 'description' => 'Tagihan SPP, pembayaran, laporan', 'route' => 'admin.keuangan.dashboard', 'icon' => 'coin', 'tone' => 'accent'],
                ['label' => 'Absensi', 'description' => 'Kehadiran siswa & guru, rekap presensi', 'route' => 'admin.absensi.dashboard', 'icon' => 'calendar-check', 'tone' => 'green'],
                ['label' => 'Dompet Digital', 'description' => 'Uang saku, kantin, transaksi cashless', 'route' => 'admin.dompet-digital.dashboard', 'icon' => 'wallet', 'tone' => 'blue'],
                ['label' => 'Perpustakaan', 'description' => 'Katalog, peminjaman, pengembalian buku', 'route' => 'admin.perpustakaan.dashboard', 'icon' => 'books', 'tone' => 'primary'],
            ],
            'charts' => $charts,
            'recentTagihan' => Tagihan::query()->rootBill()->with('siswa')->latest()->limit(6)->get(),
            'perizinanSummary' => \App\Support\PerizinanDashboard::summaryForAdmin(),
        ]);
    }
}
