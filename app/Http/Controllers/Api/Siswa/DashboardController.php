<?php

namespace App\Http\Controllers\Api\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\Finance\SccttranCashlessService;
use App\Support\ApiPortalProfile;
use App\Support\AttendanceDashboard;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use DataTableTrait;
    use PortalAccess;

    public function __construct(
        private readonly SccttranCashlessService $cashlessService,
    ) {}

    public function index(): JsonResponse
    {
        $siswa = $this->linkedSiswa();

        return $this->jsonSuccess('OK', [
            'profile' => ApiPortalProfile::siswaDetail($siswa),
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
        ]);
    }

    public function profil(): JsonResponse
    {
        return $this->jsonSuccess('OK', [
            'profile' => ApiPortalProfile::siswaDetail($this->linkedSiswa()),
        ]);
    }
}
