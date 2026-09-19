<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\KartuGuru;
use Illuminate\View\View;

class KartuGuruController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $guru = $this->linkedGuru();

        $cards = KartuGuru::query()
            ->with(['guru.sekolah', 'guru.riwayatMengajar'])
            ->where('guru_id', $guru->id)
            ->where('status', 'aktif')
            ->latest()
            ->paginate(12);

        return view('portal.guru.kartu-guru', [
            'title' => 'Kartu Guru Saya',
            'cards' => $cards,
            'cardType' => 'teacher',
        ]);
    }
}
