<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\KartuSiswa;
use Illuminate\View\View;

class KartuPelajarController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        $cards = KartuSiswa::query()
            ->with(['siswa.kelas', 'siswa.sekolah', 'siswa.profil'])
            ->where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->latest()
            ->paginate(12);

        return view('portal.siswa.kartu-pelajar', [
            'title' => 'Kartu Pelajar Saya',
            'cards' => $cards,
            'cardType' => 'student',
        ]);
    }
}
