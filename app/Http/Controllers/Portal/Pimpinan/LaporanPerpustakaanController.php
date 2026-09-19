<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Portal\Perpustakaan\RekapPengunjungController as BaseController;
use App\Models\PengunjungPerpustakaan;
use Illuminate\View\View;

class LaporanPerpustakaanController extends BaseController
{
    public function index(): View
    {
        $todayCount = PengunjungPerpustakaan::query()
            ->when($this->librarySekolahId(), fn ($q, int $sekolahId) => $q->where('sekolah_id', $sekolahId))
            ->whereDate('visited_at', now()->toDateString())
            ->count();

        return view('portal.pimpinan.laporan-perpustakaan', [
            'title' => 'Laporan Perpustakaan',
            'todayCount' => $todayCount,
            'ajaxUrl' => route('portal.pimpinan.laporan-perpustakaan.data'),
            'photoUrlTemplate' => url('portal/pimpinan/laporan-perpustakaan/photo/__ID__'),
        ]);
    }
}
