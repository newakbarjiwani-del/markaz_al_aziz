<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiSiswa;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanAbsensiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.absensi.laporan-absensi', [
            'title' => 'Laporan Absensi',
            'classes' => $this->classesList(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSiswa::query()
            ->with(['siswa.kelas'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $this->applyKelasFilter($query, $request);
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['date', 'status', 'time_in', 'created_at'],
        ], function (AbsensiSiswa $row) {
            return [
                $row->date,
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                ucfirst($row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }
}
