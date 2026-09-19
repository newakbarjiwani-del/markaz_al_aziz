<?php

namespace App\Http\Controllers\Api\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\PortalAccess;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Support\ApiPortalProfile;
use App\Support\AttendanceDashboard;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use DataTableTrait;
    use PortalAccess;

    public function index(): JsonResponse
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id');

        return $this->jsonSuccess('OK', [
            'children' => $children->map(fn ($siswa) => ApiPortalProfile::siswaSummary($siswa))->values(),
            'stats' => [
                'jumlah_anak' => $children->count(),
                'tagihan_belum_lunas' => Tagihan::query()
                    ->rootBill()
                    ->whereIn('siswa_id', $childIds)
                    ->unpaid()
                    ->count(),
                'hadir_bulan_ini' => AttendanceDashboard::presentDayCountThisMonth($childIds->all()),
                'pembayaran_bulan_ini' => Pembayaran::whereIn('siswa_id', $childIds)
                    ->where('paid_dt', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
            ],
        ]);
    }

    public function children(): JsonResponse
    {
        return $this->jsonSuccess('OK', [
            'children' => $this->ortuChildren()
                ->map(fn ($siswa) => ApiPortalProfile::siswaDetail($siswa))
                ->values(),
        ]);
    }
}
