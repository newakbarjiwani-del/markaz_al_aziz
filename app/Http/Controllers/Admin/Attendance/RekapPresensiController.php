<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use App\Services\HariLiburService;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapPresensiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function __construct(
        private HariLiburService $hariLiburService
    ) {}

    public function index(): View
    {
        $classes = \App\Support\AdminSchoolScope::classLabels();
        $pelajarans = \App\Models\Pelajaran::orderBy('name')->get();

        return view('admin.absensi.rekap-presensi', [
            'title' => 'Rekap Presensi Siswa',
            'classes' => $classes,
            'pelajarans' => $pelajarans,
            'matrixUrl' => route('admin.absensi.rekap-presensi.matrix'),
            'dailySubjectMatrixUrl' => route('admin.absensi.rekap-presensi.daily-subject-matrix'),
            'subjectPeriodSummaryUrl' => route('admin.absensi.rekap-presensi.subject-period-summary'),
        ]);
    }

    public function matrixData(Request $request, \App\Services\Attendance\MonthlyAttendanceMatrixService $matrixService): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $sekolahId = \App\Support\AdminSchoolScope::operatorSekolahId();

        $data = $matrixService->getMatrixData($month, $kelasId, null, $sekolahId);

        return response()->json($data);
    }

    public function dailySubjectMatrixData(Request $request, \App\Services\Attendance\DailySubjectAttendanceMatrixService $service): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $kelasId = $request->integer('kelas_id');
        $sekolahId = \App\Support\AdminSchoolScope::operatorSekolahId();

        if ($kelasId <= 0) {
            return response()->json([
                'message' => 'Kelas wajib dipilih.',
            ], 422);
        }

        try {
            $data = $service->getDailyMatrixData($date, $kelasId, null, $sekolahId);
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

    public function subjectPeriodSummaryData(
        Request $request,
        \App\Services\Attendance\SubjectPeriodAttendanceSummaryService $service
    ): JsonResponse {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $pelajaranId = $request->filled('pelajaran_id') ? $request->integer('pelajaran_id') : null;
        $sekolahId = \App\Support\AdminSchoolScope::operatorSekolahId();

        $data = $service->getSummaryData($startDate, $endDate, $kelasId, $pelajaranId, null, $sekolahId);

        return response()->json($data);
    }

    public function data(Request $request): JsonResponse
    {
        return $this->rekapPresensiDataTable($request);
    }

    protected function rekapPresensiDataTable(Request $request, ?Closure $queryModifier = null): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        $kelasId = $request->filled('kelas_id') ? $request->integer('kelas_id') : null;
        $sekolahId = \App\Support\AdminSchoolScope::operatorSekolahId();

        $query = Siswa::query()->with('kelas')->where('status', Siswa::STATUS_ACTIVE);
        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }
        if ($kelasId) {
            $query->where('kelas_id', $kelasId);
        }

        if ($queryModifier) {
            $queryModifier($query);
        }

        $liburCount = $this->hariLiburService->countInMonth($month, $sekolahId, 'siswa');
        $matrixService = app(\App\Services\Attendance\MonthlyAttendanceMatrixService::class);
        $matrixCache = [];

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nis', 'name'],
            'orderable' => ['nis', 'name', 'created_at'],
        ], function (Siswa $row) use ($month, $kelasId, $sekolahId, $liburCount, $matrixService, &$matrixCache) {
            if (! isset($matrixCache[$month])) {
                $matrixCache[$month] = $matrixService->getMatrixData($month, $kelasId, null, $sekolahId);
                $matrixCache['map'] = collect($matrixCache[$month]['students'] ?? [])->keyBy('id');
            }

            $m = $matrixCache['map']->get($row->id);
            $sum = $m['summary'] ?? [
                'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'alpha' => 0,
            ];

            return [
                $row->nis,
                $row->name,
                $row->kelas?->name ?? '-',
                (int) ($sum['hadir'] ?? 0),
                (int) ($sum['terlambat'] ?? 0),
                (int) ($sum['izin'] ?? 0),
                (int) ($sum['sakit'] ?? 0),
                (int) ($sum['cuti'] ?? 0),
                (int) ($sum['alpha'] ?? 0),
                $liburCount,
            ];
        });
    }
}
