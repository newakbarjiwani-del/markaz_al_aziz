<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiGuru;
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
        return view('portal.guru.absensi', [
            'title' => 'Absensi Saya',
            'guru' => $this->linkedGuru(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $guru = $this->linkedGuru();
        $query = AbsensiGuru::query()->where('guru_id', $guru->id);
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status', 'status_pulang'],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiGuru $row) {
            return [
                $row->date,
                ucfirst($row->status),
                DisplayDate::time($row->jam_masuk),
                DisplayDate::time($row->jam_keluar),
                $row->status_pulang ? ucfirst(str_replace('_', ' ', $row->status_pulang)) : '-',
            ];
        });
    }
}
