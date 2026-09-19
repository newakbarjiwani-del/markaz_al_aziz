<?php

namespace App\Http\Controllers\Admin\StudentManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\RiwayatAkademik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatAkademikController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.manajemen-siswa.riwayat-akademik', [
            'title' => 'Riwayat Akademik',
            'classes' => $this->classesList(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = RiwayatAkademik::query()->with(['siswa.kelas', 'tahunAkademik']);
        $this->applyKelasFilter($query, $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['class_name'],
            'orderable' => ['class_name', 'gpa', 'created_at'],
        ], function (RiwayatAkademik $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->tahunAkademik?->name ?? '-',
                $row->class_name ?? $row->siswa?->kelas?->name ?? '-',
                $row->gpa !== null ? number_format($row->gpa, 2) : '-',
            ];
        });
    }
}
