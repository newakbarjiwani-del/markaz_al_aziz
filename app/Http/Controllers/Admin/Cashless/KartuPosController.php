<?php

namespace App\Http\Controllers\Admin\Cashless;

use App\Http\Controllers\Controller;
use App\Models\KartuSiswa;
use Illuminate\View\View;

class KartuPosController extends Controller
{
    public function index(): View
    {
        $cards = KartuSiswa::with('siswa.kelas')->where('status', 'aktif')->latest()->get();

        return view('admin.dompet-digital.kartu-pos', [
            'title' => 'Kartu / QR & POS',
            'cards' => $cards,
        ]);
    }
}
