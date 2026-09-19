<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiSiswa;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapSiswaController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(): View
    {
        $scope = $this->guruTeachingScope();

        return view('portal.guru.rekap-siswa', [
            'title' => 'Rekap Absensi Siswa',
            'classes' => $scope->classLabels(),
            'hasTeachingAssignment' => $scope->hasTeachingAssignment(),
            'matrixUrl' => route('portal.guru.rekap-siswa.matrix'),
            'dailySubjectMatrixUrl' => route('portal.guru.rekap-siswa.daily-subject-matrix'),
        ]);
    }

    public function dailySubjectMatrixData(Request $request, \App\Services\Attendance\DailySubjectAttendanceMatrixService $service): JsonResponse
    {
        $scope = $this->guruTeachingScope();
        $date = $request->input('date', now()->toDateString());
        $kelasId = $request->integer('kelas_id');
        $allowedKelasIds = $scope->classIds();

        if ($kelasId && ! empty($allowedKelasIds) && ! in_array($kelasId, $allowedKelasIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }

        if (! $kelasId) {
            $kelasId = $allowedKelasIds[0] ?? 0;
        }

        $studentQuery = \App\Models\Siswa::query()->where('status', \App\Models\Siswa::STATUS_ACTIVE);
        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        } elseif (! empty($allowedKelasIds)) {
            $studentQuery->whereIn('kelas_id', $allowedKelasIds);
        } else {
            if (! $scope->hasTeachingAssignment()) {
                return response()->json($service->getDailyMatrixData($date, $kelasId, []));
            }
        }

        $siswaIds = $studentQuery->pluck('id')->all();

        try {
            $data = $service->getDailyMatrixData($date, $kelasId, $siswaIds);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan saat memuat matriks mata pelajaran.',
            ], 500);
        }

        return response()->json($data);
    }

    public function matrixData(Request $request, \App\Services\Attendance\MonthlyAttendanceMatrixService $matrixService): JsonResponse
    {
        $scope = $this->guruTeachingScope();
        $month = $request->input('month', now()->format('Y-m'));
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $allowedKelasIds = $scope->classIds();

        if ($kelasId && ! empty($allowedKelasIds) && ! in_array($kelasId, $allowedKelasIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }

        $studentQuery = \App\Models\Siswa::query()->where('status', \App\Models\Siswa::STATUS_ACTIVE);
        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        } elseif (! empty($allowedKelasIds)) {
            $studentQuery->whereIn('kelas_id', $allowedKelasIds);
        } else {
            if (! $scope->hasTeachingAssignment()) {
                return response()->json($matrixService->getMatrixData($month, null, []));
            }
        }

        $siswaIds = $studentQuery->pluck('id')->all();
        $data = $matrixService->getMatrixData($month, $kelasId, $siswaIds);

        return response()->json($data);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSiswa::query()
            ->with([
                'siswa.kelas' => fn ($q) => $q->withTrashed(),
                'jadwalSlot' => fn ($q) => $q->withTrashed(),
                'jadwalSlot.pelajaran' => fn ($q) => $q->withTrashed(),
                'jadwalSlot.hari' => fn ($q) => $q->withTrashed(),
                'jadwalSlot.hari.jadwalAbsen' => fn ($q) => $q->withoutGlobalScopes()->withTrashed(),
                'jadwalSlot.hari.jadwalAbsen.kelas' => fn ($q) => $q->withTrashed(),
            ]);
        $this->applyGuruKelasScope($query);
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            $slot = $row->jadwalSlot;
            $jadwalAbsen = $slot?->hari?->jadwalAbsen;
            $jadwalKelasNames = $jadwalAbsen?->kelas?->pluck('name')->filter()->values();
            $jadwalKelasLabel = ($jadwalKelasNames && $jadwalKelasNames->isNotEmpty())
                ? $jadwalKelasNames->join(', ')
                : ($jadwalAbsen?->assignmentLabel() ?? null);

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $slot?->pelajaran?->name ?? ($row->method ? 'Absensi Harian (' . strtoupper($row->method) . ')' : 'Absensi Harian'),
                $jadwalKelasLabel ?: ($row->siswa?->kelas?->name ?? '-'),
                $row->date ? $row->date->format('Y-m-d') : '-',
                ucfirst((string) $row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }
}
