<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiGuru;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiGuruController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        return view('admin.absensi.absensi-guru', ['title' => 'Absensi Guru']);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiGuru::query()->with('guru');
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status', 'status_pulang'],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiGuru $row) {
            return [
                $row->guru?->nip ?? '-',
                $row->guru?->name ?? '-',
                $row->date,
                AttendanceStatus::label($row->status),
                DisplayDate::time($row->jam_masuk),
                DisplayDate::time($row->jam_keluar),
                $row->status_pulang ? ucfirst(str_replace('_', ' ', $row->status_pulang)) : '-',
            ];
        });
    }
}
