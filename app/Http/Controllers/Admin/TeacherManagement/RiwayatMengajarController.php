<?php

namespace App\Http\Controllers\Admin\TeacherManagement;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\RiwayatMengajar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatMengajarController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.manajemen-guru.riwayat-mengajar', ['title' => 'Riwayat Mengajar']);
    }

    public function data(Request $request): JsonResponse
    {
        $query = RiwayatMengajar::query()->with('guru');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['subject', 'class_name', 'year'],
            'orderable' => ['subject', 'class_name', 'year', 'created_at'],
        ], function (RiwayatMengajar $row) {
            return [
                $row->guru?->nip ?? '-',
                $row->guru?->name ?? '-',
                $row->subject,
                $row->class_name ?? '-',
                $row->year ?? '-',
            ];
        });
    }
}
