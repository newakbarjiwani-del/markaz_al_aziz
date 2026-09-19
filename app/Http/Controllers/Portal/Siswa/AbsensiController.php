<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiSiswa;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        return view('portal.siswa.absensi', [
            'title' => 'Absensi Saya',
            'siswa' => $this->linkedSiswa(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $siswa = $this->linkedSiswa();
        $query = AbsensiSiswa::query()->where('siswa_id', $siswa->id);
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            return [
                $row->date,
                ucfirst($row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }
}
