<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Attendance\LaporanAbsensiController as BaseController;
use Illuminate\View\View;

class LaporanAbsensiController extends BaseController
{
    public function index(): View
    {
        return view('admin.absensi.laporan-absensi', [
            'title' => 'Laporan Absensi Siswa',
            'classes' => $this->classesList(),
            'ajaxUrl' => route('portal.pimpinan.laporan-absensi-siswa.data'),
        ]);
    }
}
