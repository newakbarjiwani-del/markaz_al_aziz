<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\FilterTrait;
use App\Models\KartuSiswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KartuPelajarController extends Controller
{
    use FilterTrait;

    public function index(Request $request): View
    {
        $query = KartuSiswa::query()
            ->with(['siswa.kelas', 'siswa.sekolah', 'siswa.profil'])
            ->where('status', 'aktif');

        $this->applyKelasFilter($query, $request);
        $this->applySiswaSearchFilter($query, $request);

        $cards = $query->latest()->paginate(12)->withQueryString();

        return view('admin.manajemen-siswa.kartu-pelajar', [
            'title' => 'Kartu Pelajar',
            'cards' => $cards,
            'cardType' => 'student',
            'classes' => $this->classesList(),
        ]);
    }
}
