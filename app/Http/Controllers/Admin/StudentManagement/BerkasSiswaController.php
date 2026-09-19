<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\DokumenSiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerkasSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $this->authorize('students.view');

        return view('admin.manajemen-siswa.berkas-siswa', [
            'title' => 'Berkas Siswa',
            'classes' => $this->classesList(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('students.view');

        $query = DokumenSiswa::query()->with('siswa.kelas');
        $this->applyKelasFilter($query, $request);
        $this->applyDateRange($query, $request, 'created_at');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title', 'file_type'],
            'orderable' => ['title', 'created_at'],
        ], function (DokumenSiswa $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->title,
                strtoupper($row->file_type ?? '-'),
                $row->created_at,
            ];
        });
    }
}
