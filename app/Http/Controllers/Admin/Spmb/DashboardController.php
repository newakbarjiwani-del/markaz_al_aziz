<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Models\SpmbBerita;
use App\Models\SpmbGaleriItem;
use App\Models\SpmbPendaftar;
use App\Models\SpmbPengumuman;
use App\Models\SpmbPeriode;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('spmb.view');

        $byStatus = SpmbPendaftar::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.spmb.dashboard', [
            'title' => 'Dashboard SPMB',
            'stats' => [
                ['label' => 'Periode Aktif', 'value' => SpmbPeriode::query()->where('is_active', true)->count(), 'accent' => 'primary'],
                ['label' => 'Total Pendaftar', 'value' => SpmbPendaftar::query()->count(), 'accent' => 'info'],
                ['label' => 'Diajukan', 'value' => (int) ($byStatus[SpmbPendaftar::STATUS_SUBMITTED] ?? 0), 'accent' => 'accent'],
                ['label' => 'Diverifikasi', 'value' => (int) ($byStatus[SpmbPendaftar::STATUS_VERIFIED] ?? 0), 'accent' => 'purple'],
                ['label' => 'Diterima', 'value' => (int) ($byStatus[SpmbPendaftar::STATUS_ACCEPTED] ?? 0), 'accent' => 'green'],
                ['label' => 'Ditolak', 'value' => (int) ($byStatus[SpmbPendaftar::STATUS_REJECTED] ?? 0), 'accent' => 'red'],
                ['label' => 'Pengumuman', 'value' => SpmbPengumuman::query()->count(), 'accent' => 'neutral'],
                ['label' => 'Berita', 'value' => SpmbBerita::query()->count(), 'accent' => 'info'],
                ['label' => 'Galeri', 'value' => SpmbGaleriItem::query()->count(), 'accent' => 'warning'],
            ],
            'recentPendaftar' => SpmbPendaftar::query()
                ->with('periode')
                ->latest()
                ->limit(8)
                ->get(),
            'periodeOpen' => SpmbPeriode::currentOpen(),
        ]);
    }
}
