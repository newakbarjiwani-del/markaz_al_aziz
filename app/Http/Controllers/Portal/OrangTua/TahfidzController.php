<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRekapSiswa;
use App\Models\TahfidzTarget;
use App\Support\TahfidzProgressStatus;
use Illuminate\View\View;

class TahfidzController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id');

        $progress = TahfidzProgress::query()
            ->with(['siswa', 'surah'])
            ->whereIn('siswa_id', $childIds)
            ->orderByDesc('last_reviewed_at')
            ->get();

        $targets = TahfidzTarget::query()
            ->with(['siswa', 'surah'])
            ->whereIn('siswa_id', $childIds)
            ->orderByDesc('due_date')
            ->get();

        $rekaps = TahfidzRekapSiswa::query()
            ->with(['rekap.program', 'halaqoh.guru'])
            ->whereIn('siswa_id', $childIds)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('portal.ortu.tahfidz.index', [
            'title' => 'Tahfidz Anak',
            'children' => $children,
            'progress' => $progress,
            'targets' => $targets,
            'rekaps' => $rekaps,
            'statuses' => TahfidzProgressStatus::labels(),
        ]);
    }
}
