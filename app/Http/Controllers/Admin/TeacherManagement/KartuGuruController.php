<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Models\KartuGuru;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KartuGuruController extends Controller
{
    public function index(Request $request): View
    {
        $cards = KartuGuru::query()
            ->with(['guru.sekolah', 'guru.riwayatMengajar'])
            ->where('status', 'aktif')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.manajemen-guru.kartu-guru', [
            'title' => 'Kartu Guru',
            'cards' => $cards,
            'cardType' => 'teacher',
        ]);
    }
}
