<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Sekolah;
use App\Services\HariLiburService;
use App\Support\AdminSchoolScope;
use App\Support\AttendanceStatus;
use App\Support\GuruSekolahFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapPresensiGuruController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private HariLiburService $hariLiburService
    ) {}

    public function index(): View
    {
        return view('admin.absensi.rekap-presensi-guru', [
            'title' => 'Rekap Presensi Guru',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        $sekolahId = $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
        $liburCount = $this->hariLiburService->countInMonth($month, $sekolahId, 'guru');
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $query = Guru::query()
            ->select('guru.*')
            ->with('sekolah')
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::HADIR)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'hadir'
            )
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::TERLAMBAT)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'terlambat'
            )
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::ALPHA)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'alpha'
            )
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::IZIN)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'izin'
            )
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::SAKIT)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'sakit'
            )
            ->selectSub(
                AbsensiGuru::selectRaw('count(*)')
                    ->whereColumn('absensi_guru.guru_id', 'guru.id')
                    ->where('status', AttendanceStatus::CUTI)
                    ->whereBetween('date', [$monthStart, $monthEnd]),
                'cuti'
            );

        GuruSekolahFilter::applyListFilter($query, $sekolahId);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nip', 'name', 'jabatan'],
            'orderable' => ['nip', 'name', 'jabatan', 'created_at'],
        ], function (Guru $row) use ($liburCount) {
            return [
                $row->nip,
                $row->name,
                $row->sekolah?->name ?? 'Lintas sekolah',
                $row->jabatan ?? '-',
                (int) ($row->hadir ?? 0),
                (int) ($row->terlambat ?? 0),
                (int) ($row->izin ?? 0),
                (int) ($row->sakit ?? 0),
                (int) ($row->cuti ?? 0),
                (int) ($row->alpha ?? 0),
                $liburCount,
            ];
        });
    }
}
