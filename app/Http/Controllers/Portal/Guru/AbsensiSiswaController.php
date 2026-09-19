<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAbsensiSesiPengecualianRequest;
use App\Http\Traits\HandlesFaceCapture;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiGuru;
use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\JadwalAbsenSlot;
use App\Models\Scopes\OperatorSekolahScope;
use App\Models\Siswa;
use App\Services\Attendance\AbsensiSesiPengecualianService;
use App\Services\GuruJadwalAbsenService;
use App\Services\GuruJadwalAttendanceService;
use App\Services\HariLiburService;
use App\Services\PerizinanAbsensiSyncService;
use App\Support\ActionMessage;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AbsensiSiswaController extends Controller
{
    use HandlesFaceCapture;
    use PortalAccess;

    public function __construct(
        private HariLiburService $hariLiburService,
        private GuruJadwalAttendanceService $guruAttendanceService,
        private AbsensiSesiPengecualianService $sesiPengecualianService
    ) {}

    public function index(): View
    {
        $guru = $this->linkedGuru();
        $schedules = GuruJadwalAbsenService::todayScheduleCards($guru);
        $slotIds = collect($schedules)->pluck('id');

        $todayCount = $slotIds->isEmpty()
            ? 0
            : AbsensiSiswa::query()
                ->whereDate('date', now()->toDateString())
                ->whereIn('jadwal_absen_slot_id', $slotIds)
                ->count();

        return view('portal.guru.absensi-siswa', [
            'title' => 'Absensi Siswa',
            'guru' => $guru,
            'schedules' => $schedules,
            'todayCount' => $todayCount,
            'showGuruAttendancePanel' => (bool) $guru->jadwal_absensi_guru_id,
        ]);
    }

    public function references(Request $request): JsonResponse
    {
        $slot = $this->resolveSlot((int) $request->query('slot_id'));

        // Never select foto_wajah here — base64 blobs for large classes exhaust memory.
        $students = $this->studentsForSlot($slot)
            ->select(['id', 'nis', 'name', 'kelas_id', 'updated_at'])
            ->where('has_foto_wajah', 1)
            ->with(['kelas:id,name'])
            ->orderBy('name')
            ->get();

        return $this->jsonSuccess('OK', [
            'students' => $students->map(fn (Siswa $siswa) => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas' => $siswa->kelas?->name ?? '-',
                'photo_url' => route('portal.guru.absensi-siswa.photo', $siswa).'?slot_id='.$slot->id.'&v='.($siswa->updated_at?->timestamp ?? $siswa->id),
            ])->values(),
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $slot = $this->resolveSlot((int) $request->query('slot_id'));
        $today = now()->toDateString();

        // Exclude heavy columns (foto_wajah, address, …) — 300+ siswa would OOM at 128MB.
        $students = $this->studentsForSlot($slot)
            ->select(['id', 'nis', 'name', 'kelas_id', 'sekolah_id'])
            ->with([
                'kelas:id,name',
                'sekolah:id,name',
                'rfid',
            ])
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $attendanceMap = $studentIds->isEmpty()
            ? collect()
            : AbsensiSiswa::query()
                ->select(['id', 'siswa_id', 'status', 'method', 'time_in'])
                ->whereDate('date', $today)
                ->where('jadwal_absen_slot_id', $slot->id)
                ->whereIn('siswa_id', $studentIds)
                ->get()
                ->keyBy('siswa_id');

        $perizinanMap = $studentIds->isEmpty()
            ? collect()
            : PerizinanAbsensiSyncService::activePerizinanMap($studentIds, $today, $slot);

        $hadir = 0;
        $rows = $students->map(function (Siswa $siswa) use ($attendanceMap, $perizinanMap, &$hadir) {
            $attendance = $attendanceMap->get($siswa->id);
            $perizinan = $perizinanMap->get($siswa->id);
            $hasAttendance = (bool) $attendance;
            if ($hasAttendance) {
                $hadir++;
            }

            return [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas_id' => $siswa->kelas_id,
                'kelas' => $siswa->kelas?->name ?? '-',
                'sekolah_id' => $siswa->sekolah_id,
                'sekolah' => $siswa->sekolah?->name ?? '-',
                'rfid_uid' => $siswa->rfidUid(),
                'has_attendance' => $hasAttendance,
                'suggested_status' => (! $hasAttendance && $perizinan && ! empty($perizinan['is_active'])) ? $perizinan['suggested_status'] : null,
                'perizinan' => $perizinan,
                'attendance' => $attendance ? [
                    'status' => ucfirst((string) $attendance->status),
                    'method' => strtoupper((string) ($attendance->method ?? '-')),
                    'time_in' => DisplayDate::time($attendance->time_in),
                ] : null,
            ];
        })->values();

        $total = $rows->count();
        $exemption = $this->sesiPengecualianService->findForSlotDate($slot->id, $today);

        return $this->jsonSuccess('OK', [
            'students' => $rows,
            'summary' => [
                'total' => $total,
                'hadir' => $hadir,
                'belum' => $total - $hadir,
            ],
            'session_exemption' => [
                'active' => $exemption !== null,
                'id' => $exemption?->id,
                'reason' => $exemption?->reason,
                'date' => $today,
            ],
            'assignment' => [
                'type' => $slot->hari->jadwalAbsen->assignment_type,
                'label' => $slot->hari->jadwalAbsen->assignmentLabel(),
                'kelas_ids' => $slot->hari->jadwalAbsen->assignedKelasIds(),
                'student_count' => $total,
            ],
        ]);
    }

    public function photo(Request $request, Siswa $siswa): Response
    {
        $slotId = (int) $request->query('slot_id');
        abort_if($slotId && ! $this->isStudentInSlot($siswa, $this->resolveSlot($slotId)), 404);
        abort_if(! $siswa->hasFotoWajah(), 404);

        return $this->respondFacePhoto($siswa);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jadwal_absen_slot_id' => ['required', 'integer', SoftDeleteRules::exists('jadwal_absen_slot')],
            'method' => ['required', Rule::in(['rfid', 'manual', 'face'])],
            'siswa_id' => ['nullable', 'integer', SoftDeleteRules::exists('siswa')],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
            'status' => AttendanceStatus::validationRule(),
            'force_update' => ['nullable', 'boolean'],
        ]);

        $slot = $this->resolveSlot((int) $validated['jadwal_absen_slot_id']);
        $siswa = $this->resolveStudentForAttendance($slot, $validated);

        if (! $siswa) {
            return $this->jsonError('Siswa tidak ditemukan dalam jadwal absen ini.', null, 404);
        }

        if ($validated['method'] === 'face' && ! $siswa->hasFotoWajah()) {
            return $this->jsonError('Siswa belum memiliki foto wajah referensi.', null, 422);
        }

        try {
            return $this->persistAttendance(
                $siswa,
                $slot,
                $validated['method'],
                $validated['status'] ?? 'hadir',
                (bool) ($validated['force_update'] ?? false),
            );
        } catch (\RuntimeException $exception) {
            return $this->jsonError($exception->getMessage());
        }
    }

    public function sessionExemptionStatus(Request $request): JsonResponse
    {
        $slot = $this->resolveSlot((int) $request->query('slot_id'));
        $date = $request->query('date', now()->toDateString());
        $exemption = $this->sesiPengecualianService->findForSlotDate($slot->id, $date);

        return $this->jsonSuccess('OK', [
            'session_exemption' => [
                'active' => $exemption !== null,
                'id' => $exemption?->id,
                'reason' => $exemption?->reason,
                'date' => $date,
            ],
        ]);
    }

    public function sessionExemptionStore(StoreAbsensiSesiPengecualianRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slot = $this->resolveSlot((int) $validated['jadwal_absen_slot_id']);
        $date = $validated['date'] ?? now()->toDateString();

        if ($date !== now()->toDateString()) {
            return $this->jsonError('Pengecualian sesi hanya dapat dicatat untuk hari ini.', null, 422);
        }

        $exemption = $this->sesiPengecualianService->markExempt(
            $slot,
            $date,
            $validated['reason'],
            $request->user()
        );

        $slot->loadMissing('pelajaran');

        return $this->jsonSuccess(
            'Sesi absensi ditandai tidak wajib absen: '.$slot->pelajaran?->name.'.',
            [
                'session_exemption' => [
                    'active' => true,
                    'id' => $exemption->id,
                    'reason' => $exemption->reason,
                    'date' => $date,
                ],
            ],
            201
        );
    }

    public function sessionExemptionDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jadwal_absen_slot_id' => ['required', 'integer'],
            'date' => ['nullable', 'date'],
        ]);

        $slot = $this->resolveSlot((int) $validated['jadwal_absen_slot_id']);
        $date = $validated['date'] ?? now()->toDateString();

        if (! $this->sesiPengecualianService->revoke($slot->id, $date)) {
            return $this->jsonError('Pengecualian sesi tidak ditemukan.', null, 404);
        }

        return $this->jsonSuccess('Pengecualian sesi absensi dibatalkan. Sesi kembali dihitung normal.');
    }

    public function guruAttendanceStatus(): JsonResponse
    {
        $guru = $this->linkedGuru();
        $attendance = AbsensiGuru::query()
            ->where('guru_id', $guru->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return $this->jsonSuccess('OK', [
            'attendance' => $this->guruAttendancePayload($attendance),
        ]);
    }

    public function guruAttendanceStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in([GuruJadwalAttendanceService::METHOD_MANUAL, GuruJadwalAttendanceService::METHOD_RFID])],
            'rfid_uid' => ['nullable', 'string', 'max:64'],
        ]);

        $guru = $this->linkedGuru();
        $jadwal = $guru->jadwalAbsensiGuru;
        if (! $jadwal) {
            return $this->jsonError('Jadwal absensi guru belum diatur untuk akun ini.');
        }

        if ($validated['method'] === GuruJadwalAttendanceService::METHOD_RFID) {
            $expected = trim((string) ($guru->rfidUid() ?? ''));
            $provided = trim((string) ($validated['rfid_uid'] ?? ''));
            if ($expected === '' || $provided === '' || $expected !== $provided) {
                return $this->jsonError('UID RFID guru tidak valid.', null, 422);
            }
        }

        try {
            $result = $this->guruAttendanceService->record(
                $jadwal,
                $guru,
                $validated['method'],
                null,
                null
            );
        } catch (\RuntimeException $exception) {
            return $this->jsonError($exception->getMessage());
        }

        $attendance = $result['attendance'];
        $action = $result['action'];
        $message = match ($action) {
            GuruJadwalAttendanceService::ACTION_PULANG => 'Absensi pulang guru berhasil dicatat.',
            GuruJadwalAttendanceService::ACTION_MASUK_PULANG => 'Absensi guru (masuk + pulang) berhasil dicatat.',
            default => 'Absensi masuk guru berhasil dicatat.',
        };

        return $this->jsonSuccess($message, [
            'action' => $action,
            'attendance' => $this->guruAttendancePayload($attendance),
        ], 201);
    }

    private function guruAttendancePayload(?AbsensiGuru $attendance): ?array
    {
        if (! $attendance) {
            return null;
        }

        return [
            'status' => AttendanceStatus::label((string) $attendance->status),
            'status_pulang' => $attendance->status_pulang
                ? ucfirst(str_replace('_', ' ', (string) $attendance->status_pulang))
                : null,
            'jam_masuk' => DisplayDate::time($attendance->jam_masuk),
            'jam_keluar' => DisplayDate::time($attendance->jam_keluar),
            'method' => strtoupper((string) ($attendance->method ?? '-')),
            'method_keluar' => strtoupper((string) ($attendance->method_keluar ?? '-')),
            'has_record' => true,
            'has_masuk' => $attendance->hasCheckedIn(),
            'has_pulang' => $attendance->hasCheckedOut(),
            'is_absent_only' => $attendance->isAbsentOnly(),
            'is_complete' => $attendance->isDayComplete(),
        ];
    }

    private function resolveSlot(int $slotId): JadwalAbsenSlot
    {
        abort_if($slotId <= 0, 404);

        return JadwalAbsenSlot::query()
            ->with([
                'pelajaran',
                'hari.jadwalAbsen' => fn ($q) => $q->withoutGlobalScope(OperatorSekolahScope::class)->with('kelas'),
            ])
            ->where('guru_id', $this->linkedGuru()->id)
            ->whereHas('hari', fn ($q) => $q->where('is_active', true))
            ->whereHas('hari.jadwalAbsen', fn ($q) => $q->withoutGlobalScope(OperatorSekolahScope::class)->where('is_active', true))
            ->findOrFail($slotId);
    }

    private function studentsForSlot(JadwalAbsenSlot $slot)
    {
        return $slot->hari->jadwalAbsen->resolvedStudentQuery();
    }

    private function isStudentInSlot(Siswa $siswa, JadwalAbsenSlot $slot): bool
    {
        return $this->studentsForSlot($slot)->whereKey($siswa->id)->exists();
    }

    /** @param array<string, mixed> $validated */
    private function resolveStudentForAttendance(JadwalAbsenSlot $slot, array $validated): ?Siswa
    {
        if (! empty($validated['siswa_id'])) {
            return $this->studentsForSlot($slot)
                ->whereKey($validated['siswa_id'])
                ->first();
        }

        if (! empty($validated['rfid_uid'])) {
            return $this->studentsForSlot($slot)
                ->whereHas('rfid', fn ($query) => $query->where('uid', $validated['rfid_uid']))
                ->first();
        }

        return null;
    }

    private function persistAttendance(
        Siswa $siswa,
        JadwalAbsenSlot $slot,
        string $method,
        string $status,
        bool $forceUpdate = false
    ): JsonResponse {
        $today = now()->toDateString();
        $this->hariLiburService->assertNotLibur($today, $siswa->sekolah_id, HariLibur::APPLIES_SISWA);

        $existing = AbsensiSiswa::query()
            ->where('siswa_id', $siswa->id)
            ->where('jadwal_absen_slot_id', $slot->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            if ($forceUpdate && $method === 'manual') {
                $resolvedStatus = $this->resolveStatus($status, $slot, $method);

                $existing->update([
                    'status' => $resolvedStatus,
                    'method' => $method,
                    'jadwal_absen_slot_id' => $slot->id,
                    'time_in' => AttendanceStatus::isAbsent($resolvedStatus)
                        ? null
                        : ($existing->time_in ?: now()->format('H:i')),
                ]);

                $siswa->loadMissing('kelas');

                return $this->jsonSuccess(
                    ActionMessage::withSubject('Absensi siswa berhasil diperbarui', $this->attendanceSubject($siswa, $slot)),
                    [
                        'already_recorded' => false,
                        'updated_existing' => true,
                        'attendance' => $this->attendancePayload($siswa, $existing->fresh()),
                    ]
                );
            }

            $siswa->loadMissing('kelas');

            return $this->jsonSuccess(
                ActionMessage::withSubject('Siswa sudah tercatat absen hari ini', $this->attendanceSubject($siswa, $slot)),
                [
                    'already_recorded' => true,
                    'attendance' => $this->attendancePayload($siswa, $existing),
                ]);
        }

        $resolvedStatus = $this->resolveStatus($status, $slot, $method);

        $attendance = AbsensiSiswa::create([
            'sekolah_id' => $siswa->sekolah_id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $slot->id,
            'date' => $today,
            'status' => $resolvedStatus,
            'method' => $method,
            'time_in' => AttendanceStatus::isAbsent($resolvedStatus) ? null : now()->format('H:i'),
        ]);

        $siswa->loadMissing('kelas');

        return $this->jsonSuccess(
            ActionMessage::withSubject('Absensi siswa berhasil disimpan', $this->attendanceSubject($siswa, $slot)),
            [
                'already_recorded' => false,
                'attendance' => $this->attendancePayload($siswa, $attendance),
            ], 201);
    }

    private function attendanceSubject(Siswa $siswa, JadwalAbsenSlot $slot): string
    {
        $slot->loadMissing(['pelajaran', 'hari.jadwalAbsen.kelas']);
        $jadwalKelas = $slot->hari?->jadwalAbsen?->kelas;
        $kelasLabel = $jadwalKelas
            ? $jadwalKelas->pluck('name')->filter()->join(', ')
            : null;

        $parts = array_filter([
            ActionMessage::siswa($siswa),
            $slot->pelajaran?->name,
            $kelasLabel,
        ], fn (?string $value) => filled($value));

        return implode(' · ', $parts);
    }

    private function resolveStatus(string $status, JadwalAbsenSlot $slot, string $method): string
    {
        // Manual input by guru should respect the selected status.
        if ($method === 'manual') {
            return $status;
        }

        if ($status !== 'hadir') {
            return $status;
        }

        $date = now()->toDateString();
        $start = Carbon::parse($date.' '.$slot->time_start);
        $lateUntil = $start->copy()->addMinutes((int) $slot->tolerance_minutes);

        return now()->greaterThan($lateUntil) ? 'terlambat' : 'hadir';
    }

    /** @return array<string, mixed> */
    private function attendancePayload(Siswa $siswa, AbsensiSiswa $attendance): array
    {
        $displayTime = $attendance->time_in
            ? DisplayDate::time($attendance->time_in)
            : DisplayDate::time($attendance->created_at);

        return [
            'nis' => $siswa->nis,
            'name' => $siswa->name,
            'kelas' => $siswa->kelas?->name ?? '-',
            'status' => ucfirst($attendance->status),
            'method' => strtoupper($attendance->method ?? '-'),
            'time_in' => $displayTime,
            'date' => $attendance->date,
        ];
    }

    protected function jsonSuccess(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function jsonError(string $message, mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
