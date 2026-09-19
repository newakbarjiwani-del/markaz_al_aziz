<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Attendance\RekapPresensiController as BaseController;
use Illuminate\View\View;

class RekapPresensiController extends BaseController
{
    public function index(): View
    {
        return view('admin.absensi.rekap-presensi', [
            'title' => 'Rekap Presensi Siswa',
            'ajaxUrl' => route('portal.pimpinan.rekap-presensi.data'),
        ]);
    }
}
