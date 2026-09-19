<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use Illuminate\View\View;

class ProfilController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $guru = $this->linkedGuru()->load(['profil', 'riwayatMengajar']);

        return view('portal.guru.profil', [
            'title' => 'Profil Saya',
            'guru' => $guru,
            'profil' => $guru->profil,
        ]);
    }
}
