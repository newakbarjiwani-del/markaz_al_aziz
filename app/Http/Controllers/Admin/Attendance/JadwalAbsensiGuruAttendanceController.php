<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreJadwalGuruAttendanceRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\JadwalAbsensiGuru;
use App\Services\GuruJadwalAttendanceService;
use App\Services\HariLiburService;
use App\Support\ActionMessage;
use App\Support\DisplayDate;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;

class JadwalAbsensiGuruAttendanceController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private GuruJadwalAttendanceService $attendanceService,
        private HariLiburService $hariLiburService,
    ) {}

    public function show(JadwalAbsensiGuru $jadwalAbsensiGuru): View
    {
        $jadwalAbsensiGuru->load(['sekolah', 'gurus']);
        $today = now()->toDateString();
        $libur = $this->hariLiburService->findForDate($today, $jadwalAbsensiGuru->sekolah_id, HariLibur::APPLIES_GURU);

        return view('admin.absensi.jadwal-absensi-guru.absensi', [
            'title' => 'Absensi Guru — '.$jadwalAbsensiGuru->name,
            'jadwal' => $jadwalAbsensiGuru,
            'board' => $this->attendanceService->todayBoard($jadwalAbsensiGuru),
            'liburToday' => $libur,
            'backUrl' => route('admin.absensi.jadwal-absensi-guru.index'),
            'referencesUrl' => route('admin.absensi.jadwal-absensi-guru.absensi.references', $jadwalAbsensiGuru),
            'storeUrl' => route('admin.absensi.jadwal-absensi-guru.absensi.store', $jadwalAbsensiGuru),
        ]);
    }

    public function references(JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'board' => $this->attendanceService->todayBoard($jadwalAbsensiGuru),
                'summary' => $this->summary($jadwalAbsensiGuru),
            ],
        ]);
    }

    public function store(StoreJadwalGuruAttendanceRequest $request, JadwalAbsensiGuru $jadwalAbsensiGuru): JsonResponse
    {
        $this->authorize('attendance.create');

        try {
            $guru = $this->resolveGuru($request, $jadwalAbsensiGuru);
            $result = $this->attendanceService->record(
                $jadwalAbsensiGuru,
                $guru,
                $request->string('method')->toString(),
                $request->input('status'),
                $request->input('notes'),
            );
        } catch (RuntimeException $exception) {
            return $this->jsonError($exception->getMessage());
        }

        $attendance = $result['attendance'];
        $action = $result['action'];

        $message = match ($action) {
            GuruJadwalAttendanceService::ACTION_MASUK_PULANG => ActionMessage::withSubject('Absensi masuk dan pulang berhasil dicatat', $guru->name),
            GuruJadwalAttendanceService::ACTION_PULANG => ActionMessage::withSubject('Absensi pulang berhasil dicatat', $guru->name),
            GuruJadwalAttendanceService::ACTION_ABSEN => ActionMessage::withSubject('Status absensi berhasil dicatat', $guru->name),
            default => ActionMessage::withSubject('Absensi masuk berhasil dicatat', $guru->name),
        };

        return $this->jsonSuccess(
            $message,
            [
                'action' => $action,
                'attendance' => [
                    'guru_id' => $guru->id,
                    'name' => $guru->name,
                    'nip' => $guru->nip,
                    'status' => $attendance->status,
                    'status_pulang' => $attendance->status_pulang,
                    'jam_masuk' => DisplayDate::time($attendance->jam_masuk),
                    'jam_keluar' => DisplayDate::time($attendance->jam_keluar),
                    'method' => $attendance->method,
                    'method_keluar' => $attendance->method_keluar,
                ],
                'board' => $this->attendanceService->todayBoard($jadwalAbsensiGuru),
                'summary' => $this->summary($jadwalAbsensiGuru),
            ],
            201,
        );
    }

    private function resolveGuru(StoreJadwalGuruAttendanceRequest $request, JadwalAbsensiGuru $jadwal): Guru
    {
        if ($request->string('method')->toString() === GuruJadwalAttendanceService::METHOD_RFID) {
            $guru = $this->attendanceService->findGuruByRfid(
                $jadwal,
                $request->string('rfid_uid')->toString()
            );

            if (! $guru) {
                throw new RuntimeException('Kartu RFID tidak dikenali atau guru tidak terdaftar pada jadwal ini.');
            }

            return $guru;
        }

        $guru = Guru::query()->find($request->integer('guru_id'));

        if (! $guru) {
            throw new RuntimeException('Guru tidak ditemukan.');
        }

        return $guru;
    }

    /**
     * @return array{total: int, masuk: int, pulang: int, hadir: int, belum: int}
     */
    private function summary(JadwalAbsensiGuru $jadwal): array
    {
        $board = $this->attendanceService->todayBoard($jadwal);
        $total = count($board);
        $masuk = collect($board)->where('has_masuk', true)->count();
        $pulang = collect($board)->where('has_pulang', true)->count();

        return [
            'total' => $total,
            'masuk' => $masuk,
            'pulang' => $pulang,
            'hadir' => $masuk,
            'belum' => max(0, $total - $masuk),
        ];
    }
}
