<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Attendance\AbsensiGuruController as BaseController;
use Illuminate\View\View;

class AbsensiGuruController extends BaseController
{
    public function index(): View
    {
        return view('admin.absensi.absensi-guru', [
            'title' => 'Laporan Absensi Guru',
            'ajaxUrl' => route('portal.pimpinan.laporan-absensi-guru.data'),
        ]);
    }
}
