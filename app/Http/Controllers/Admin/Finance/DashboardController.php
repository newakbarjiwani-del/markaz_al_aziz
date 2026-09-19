<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\SaldoKeuangan;
use App\Models\Tagihan;
use App\Support\DashboardChart;
use App\Support\TagihanDashboard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth();
        $totalTagihan = TagihanDashboard::billedTotal();
        $tunggakan = TagihanDashboard::outstandingTotal();
        $penerimaanBulanIni = Pembayaran::where('paid_dt', '>=', $monthStart)->sum('total_amount');

        $byStatus = TagihanDashboard::countsByStatusLabel();
        $byJenis = TagihanDashboard::billedByJenis();

        $monthLabels = DashboardChart::monthLabels();
        $monthlyPenerimaan = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return (float) Pembayaran::query()
                ->whereYear('paid_dt', $date->year)
                ->whereMonth('paid_dt', $date->month)
                ->sum('total_amount');
        })->all();

        $charts = [];

        if ($byStatus->isNotEmpty()) {
            $charts[] = DashboardChart::doughnutFromMap(
                'finance-status-chart',
                'Status Tagihan',
                $byStatus->all()
            );
        }

        if ($byJenis->isNotEmpty()) {
            $charts[] = DashboardChart::single(
                'finance-jenis-chart',
                'Tagihan per Jenis',
                'bar',
                $byJenis->keys()->all(),
                $byJenis->values()->all(),
                'Nominal Tagihan'
            );
        }

        $charts[] = DashboardChart::single(
            'finance-penerimaan-chart',
            'Penerimaan 6 Bulan Terakhir',
            'line',
            $monthLabels,
            $monthlyPenerimaan,
            'Penerimaan (Rp)'
        );

        return view('admin.keuangan.dashboard', [
            'title' => 'Dashboard Keuangan',
            'stats' => [
                ['label' => 'Total Tagihan', 'value' => 'Rp '.number_format($totalTagihan, 0, ',', '.'), 'accent' => 'primary', 'currency' => true],
                ['label' => 'Tunggakan', 'value' => 'Rp '.number_format($tunggakan, 0, ',', '.'), 'accent' => 'accent', 'currency' => true],
                ['label' => 'Penerimaan Bulan Ini', 'value' => 'Rp '.number_format($penerimaanBulanIni, 0, ',', '.'), 'accent' => 'green', 'currency' => true, 'hint' => 'Tidak termasuk pembayaran yang dibatalkan.'],
                ['label' => 'Total Saldo', 'value' => 'Rp '.number_format(SaldoKeuangan::sum('balance'), 0, ',', '.'), 'accent' => 'purple', 'currency' => true],
            ],
            'charts' => $charts,
            'recentTagihan' => Tagihan::rootBill()->with('siswa')->latest()->limit(5)->get(),
            'recentPembayaran' => Pembayaran::with(['siswa'])->latest('paid_dt')->limit(5)->get(),
        ]);
    }
}
