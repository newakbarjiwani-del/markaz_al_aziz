<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\HistoryPeminjamanController as BaseController;
use Illuminate\View\View;

class HistoryPeminjamanController extends BaseController
{
    public function index(): View
    {
        return view('admin.perpustakaan.riwayat-peminjaman', [
            'title' => 'History Peminjaman',
            'ajaxUrl' => route('portal.perpustakaan.riwayat-peminjaman.data'),
        ]);
    }
}
