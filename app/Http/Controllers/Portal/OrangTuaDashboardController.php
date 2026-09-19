<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Finance\SccttranCashlessService;
use App\Services\Finance\SccttranSaldoService;
use App\Support\AttendanceDashboard;
use Illuminate\View\View;

class OrangTuaDashboardController extends Controller
{
    use PortalAccess;

    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
        private readonly SccttranSaldoService $saldoService,
    ) {}

    public function index(): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id')->all();
        $financeMap = $this->saldoService->balancesForSiswaIds($childIds);
        $cashlessMap = $this->cashlessService->balancesForSiswaIds($childIds);
        $perizinanSummary = \App\Support\PerizinanDashboard::summaryForOrangTua($childIds);

        $children->each(function ($child) use ($financeMap, $cashlessMap): void {
            $child->setAttribute('saldo_cashless', $cashlessMap[$child->id] ?? 0);
            $child->setAttribute('saldo_keuangan', $financeMap[$child->id] ?? 0);
        });

        return view('portal.ortu.dashboard', [
            'title' => '',
            'greetingName' => auth()->user()->orangTua?->displayName() ?? auth()->user()->name,
            'children' => $children,
            'stats' => [
                'jumlah_anak' => $children->count(),
                'tagihan_belum_lunas' => Tagihan::query()
                    ->rootBill()
                    ->whereIn('siswa_id', $childIds)
                    ->unpaid()
                    ->count(),
                'hadir_bulan_ini' => AttendanceDashboard::presentDayCountThisMonth($childIds),
                'pembayaran_bulan_ini' => Pembayaran::whereIn('siswa_id', $childIds)
                    ->where('paid_dt', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
                'saldo_keuangan' => array_sum($financeMap),
            ],
            'recentTagihan' => Tagihan::query()
                ->rootBill()
                ->with('siswa')
                ->whereIn('siswa_id', $childIds)
                ->latest()
                ->limit(6)
                ->get(),
            'perizinanSummary' => $perizinanSummary,
        ]);
    }
}
