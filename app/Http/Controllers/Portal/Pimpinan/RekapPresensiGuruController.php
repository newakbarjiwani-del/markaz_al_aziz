<?php

namespace App\Http\Controllers\Portal\Pimpinan;

use App\Http\Controllers\Admin\Attendance\RekapPresensiGuruController as BaseController;
use Illuminate\View\View;

class RekapPresensiGuruController extends BaseController
{
    public function index(): View
    {
        return view('admin.absensi.rekap-presensi-guru', [
            'title' => 'Rekap Presensi Guru',
            'schools' => \App\Models\Sekolah::orderBy('name')->get(),
            'ajaxUrl' => route('portal.pimpinan.rekap-presensi-guru.data'),
        ]);
    }
}
