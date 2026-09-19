<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Finance\SccttranCashlessService;
use App\Support\AttendanceDashboard;
use Illuminate\View\View;

class SiswaDashboardController extends Controller
{
    use PortalAccess;

    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
    ) {}

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        return view('portal.siswa.dashboard', [
            'title' => '',
            'siswa' => $siswa,
            'stats' => [
                'tagihan_belum_lunas' => Tagihan::query()
                    ->rootBill()
                    ->where('siswa_id', $siswa->id)
                    ->unpaid()
                    ->count(),
                'hadir_bulan_ini' => AttendanceDashboard::presentDayCountThisMonth($siswa->id),
                'saldo_cashless' => $this->cashlessService->balanceForSiswa($siswa->id),
                'pembayaran_bulan_ini' => Pembayaran::where('siswa_id', $siswa->id)
                    ->where('paid_dt', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
            ],
            'recentTagihan' => Tagihan::query()
                ->rootBill()
                ->where('siswa_id', $siswa->id)
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
