<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\KartuSiswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KartuPelajarController extends Controller
{
    use PortalAccess;

    public function index(Request $request): View
    {
        $children = $this->ortuChildren();
        $childIds = $children->pluck('id');

        $query = KartuSiswa::query()
            ->with(['siswa.kelas', 'siswa.sekolah', 'siswa.profil'])
            ->where('status', 'aktif')
            ->whereIn('siswa_id', $childIds);

        $selected = $this->selectedOrtuChildId($request);
        if ($selected) {
            $query->where('siswa_id', $selected);
        }

        $cards = $query->latest()->paginate(12)->withQueryString();

        return view('portal.ortu.kartu-pelajar', [
            'title' => 'Kartu Pelajar Anak',
            'cards' => $cards,
            'cardType' => 'student',
            'children' => $children,
        ]);
    }
}
