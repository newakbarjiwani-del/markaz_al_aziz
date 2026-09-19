<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Admin\Attendance\RekapPresensiController as BaseController;
use App\Http\Traits\PortalAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapPresensiController extends BaseController
{
    use PortalAccess;

    public function index(): View
    {
        return view('admin.absensi.rekap-presensi', [
            'title' => 'Rekap Presensi Anak',
            'ajaxUrl' => route('portal.ortu.rekap-presensi.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $childIds = $this->ortuChildIds();
        abort_if($childIds === [], 403, 'Tidak ada data anak yang terhubung.');

        return $this->rekapPresensiDataTable($request, function ($query) use ($childIds) {
            $query->whereIn('siswa.id', $childIds);
        });
    }
}
