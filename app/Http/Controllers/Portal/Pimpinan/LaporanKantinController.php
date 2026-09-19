<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Cashless\TransaksiController as BaseController;
use Illuminate\View\View;

class LaporanKantinController extends BaseController
{
    public function index(): View
    {
        return view('admin.dompet-digital.transaksi', [
            'title' => 'Laporan Kantin',
            'classes' => $this->classesList(),
            'metodeOptions' => $this->metodeOptions(),
            'ajaxUrl' => route('portal.pimpinan.laporan-kantin.data'),
        ]);
    }
}
