<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AbsensiGuru;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SccttranCashless;
use App\Services\Finance\SccttranCashlessService;
use App\Support\AttendanceDashboard;
use App\Support\DashboardChart;
use App\Support\TagihanDashboard;
use Illuminate\View\View;

class PimpinanDashboardController extends Controller
{
    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
    ) {}

    public function index(): View
    {
        $today = today();
        $monthStart = now()->startOfMonth();

        $todaySiswa = collect(AttendanceDashboard::distinctStudentsByStatus($today));

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

        $kantinVolume = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = today()->subDays($daysAgo);

            return SccttranCashless::where('wallet', 'kantin')->whereDate('TRXDATE', $date)->count();
        })->all();

        $charts = [];

        if ($todaySiswa->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'pimpinan-attendance-chart',
                'Absensi Siswa Hari Ini',
                $todaySiswa->all()
            );
        }

        $charts[] = DashboardChart::make(
            'pimpinan-attendance-week-chart',
            'Kehadiran Siswa (7 Hari)',
            'line',
            $dayLabels,
            [
                ['label' => 'Hadir', 'data' => $hadirPerHari],
                ['label' => 'Alpha', 'data' => $alphaPerHari],
            ]
        );

        $charts[] = DashboardChart::single(
            'pimpinan-finance-chart',
            'Penerimaan SPP (6 Bulan)',
            'bar',
            $monthLabels,
            $monthlyPenerimaan,
            'Penerimaan (Rp)'
        );

        $charts[] = DashboardChart::single(
            'pimpinan-kantin-chart',
            'Transaksi Kantin (7 Hari)',
            'line',
            $dayLabels,
            $kantinVolume,
            'Jumlah Transaksi'
        );

        $totalTagihan = TagihanDashboard::billedTotal();
        $totalTerbayar = TagihanDashboard::paidTotal();
        $tunggakan = TagihanDashboard::outstandingTotal();

        return view('portal.pimpinan.dashboard', [
            'title' => '',
            'attendanceStats' => [
                ['label' => 'Siswa Hadir', 'value' => $todaySiswa->get('hadir', 0), 'accent' => 'green'],
                ['label' => 'Siswa Alpha', 'value' => $todaySiswa->get('alpha', 0), 'accent' => 'red'],
                ['label' => 'Guru Hadir', 'value' => AbsensiGuru::whereDate('date', $today)->where('status', 'hadir')->count(), 'accent' => 'primary'],
                ['label' => 'Total Siswa Aktif', 'value' => Siswa::where('status', Siswa::STATUS_ACTIVE)->count(), 'accent' => 'accent'],
            ],
            'financeStats' => [
                ['label' => 'Penerimaan Bulan Ini', 'value' => 'Rp '.number_format(Pembayaran::where('paid_dt', '>=', $monthStart)->sum('total_amount'), 0, ',', '.'), 'accent' => 'green', 'currency' => true],
                ['label' => 'Tunggakan SPP', 'value' => 'Rp '.number_format($tunggakan, 0, ',', '.'), 'accent' => 'accent', 'currency' => true],
                ['label' => 'Tagihan Belum Lunas', 'value' => TagihanDashboard::unpaidCount(), 'accent' => 'red'],
                ['label' => 'Tingkat Pelunasan', 'value' => ($totalTagihan > 0 ? round(($totalTerbayar / $totalTagihan) * 100, 1) : 0).'%', 'accent' => 'primary'],
            ],
            'kantinStats' => [
                ['label' => 'Transaksi Kantin Bulan Ini', 'value' => SccttranCashless::where('wallet', 'kantin')->where('TRXDATE', '>=', $monthStart)->count(), 'accent' => 'primary'],
                ['label' => 'Volume Bulan Ini', 'value' => 'Rp '.number_format(SccttranCashless::where('wallet', 'kantin')->where('TRXDATE', '>=', $monthStart)->sum('DEBET'), 0, ',', '.'), 'accent' => 'green', 'currency' => true],
                ['label' => 'Total Saldo US', 'value' => 'Rp '.number_format($this->cashlessService->walletLedgerTotal('us'), 0, ',', '.'), 'accent' => 'accent', 'currency' => true],
                ['label' => 'Total Saldo Kantin', 'value' => 'Rp '.number_format($this->cashlessService->walletLedgerTotal('kantin'), 0, ',', '.'), 'accent' => 'purple', 'currency' => true],
            ],
            'charts' => $charts,
            'perizinanSummary' => \App\Support\PerizinanDashboard::summaryForAdmin(),
        ]);
    }
}
