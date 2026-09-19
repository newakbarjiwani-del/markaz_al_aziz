<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\AbsensiSiswa;
use App\Models\HariLibur;
use App\Models\JadwalAbsenSlot;
use App\Models\Siswa;
use App\Services\HariLiburService;
use App\Services\RfidResolver;
use App\Support\AdminSchoolScope;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use App\Support\RfidUid;
use App\Support\Weekday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RfidCheckinController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly HariLiburService $hariLiburService,
        private readonly RfidResolver $rfidResolver,
    ) {}

    public function index(): View
    {
        return view('admin.absensi.absensi-rfid', [
            'title' => 'Absensi RFID Kiosk',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
        ]);

        $rfidUid = RfidUid::sanitize($validated['rfid_uid']);
        if (! $rfidUid) {
            return $this->jsonError('UID RFID tidak valid atau kosong.');
        }

        // Find active/pending student by RFID
        $siswa = $this->rfidResolver->findSiswa($rfidUid, activeOnly: false);
        $siswa?->loadMissing(['kelas', 'sekolah', 'rfid']);

        if (! $siswa) {
            return $this->jsonError('Kartu RFID tidak terdaftar pada siswa mana pun.', null, 404);
        }

        if ($siswa->isRfidBlocked()) {
            return $this->jsonError('Kartu RFID siswa telah diblokir. Silakan hubungi admin sekolah.', null, 403);
        }

        if (! $siswa->isActive() && $siswa->status !== Siswa::STATUS_PENDING) {
            return $this->jsonError('Siswa dengan kartu ini tidak aktif.', null, 422);
        }

        $now = now();
        $today = $now->toDateString();
        $dayOfWeek = Weekday::fromDate($now);

        // Check if today is school holiday
        try {
            $this->hariLiburService->assertNotLibur($today, $siswa->sekolah_id, HariLibur::APPLIES_SISWA);
        } catch (\RuntimeException $exception) {
            return $this->jsonError($exception->getMessage(), null, 422);
        }

        // Look up active schedule slots for the student's class / school
        $slots = JadwalAbsenSlot::query()
            ->with([
                'pelajaran',
                'hari.jadwalAbsen',
            ])
            ->whereHas('hari', function ($q) use ($dayOfWeek) {
                $q->where('is_active', true)
                    ->where('day_of_week', $dayOfWeek);
            })
            ->whereHas('hari.jadwalAbsen', function ($q) use ($siswa) {
                $q->where('is_active', true)
                    ->where(function ($sub) use ($siswa) {
                        $sub->where(function ($s) use ($siswa) {
                            $s->where('assignment_type', 'sekolah')
                                ->where(function ($sch) use ($siswa) {
                                    $sch->where('sekolah_id', $siswa->sekolah_id)
                                        ->orWhereNull('sekolah_id');
                                });
                        })
                            ->orWhere(function ($c) use ($siswa) {
                                $c->where('assignment_type', 'kelas')
                                    ->whereExists(function ($pivot) use ($siswa) {
                                        $pivot->select(DB::raw(1))
                                            ->from('jadwal_absen_kelas')
                                            ->whereColumn('jadwal_absen_id', 'jadwal_absen.id')
                                            ->where('kelas_id', $siswa->kelas_id);
                                    });
                            });
                    });
            })
            ->get();

        if ($slots->isEmpty()) {
            return $this->jsonError('Tidak ada jadwal absensi yang diatur untuk Anda hari ini.', null, 422);
        }

        // Filter to those that are within their time window right now
        $activeSlots = $slots->filter(fn ($slot) => $slot->isWithinWindow($now));

        if ($activeSlots->isEmpty()) {
            // No slots active right now, find the next upcoming slot or notify they have ended
            $upcomingSlot = $slots->filter(function ($slot) use ($today) {
                return Carbon::parse($today.' '.$slot->time_start)->isFuture();
            })->sortBy('time_start')->first();

            if ($upcomingSlot) {
                $upcomingName = $upcomingSlot->pelajaran?->name ?? $upcomingSlot->hari->jadwalAbsen->name;
                $upcomingTime = substr((string) $upcomingSlot->time_start, 0, 5).' - '.substr((string) $upcomingSlot->time_end, 0, 5);

                return $this->jsonError("Jadwal terdekat: {$upcomingName} ({$upcomingTime}). Silakan tap kartu pada waktu tersebut.", null, 422);
            }

            return $this->jsonError('Seluruh jadwal absensi hari ini telah berakhir.', null, 422);
        }

        // Look up today's existing attendances for the student in these active slots
        $existingAttendances = AbsensiSiswa::query()
            ->where('siswa_id', $siswa->id)
            ->whereDate('date', $today)
            ->whereIn('jadwal_absen_slot_id', $activeSlots->pluck('id'))
            ->get()
            ->keyBy('jadwal_absen_slot_id');

        $targetSlot = null;
        foreach ($activeSlots as $slot) {
            if (! $existingAttendances->has($slot->id)) {
                $targetSlot = $slot;
                break;
            }
        }

        // Student has already checked in for all active slots
        if (! $targetSlot) {
            $lastAttendance = $existingAttendances->first();

            return response()->json([
                'success' => true,
                'already_recorded' => true,
                'message' => 'Anda sudah melakukan absensi hari ini.',
                'data' => [
                    'siswa' => [
                        'nis' => $siswa->nis,
                        'name' => $siswa->name,
                        'kelas' => $siswa->kelas?->name ?? '-',
                        'sekolah_code' => $siswa->sekolah?->code ?? '',
                        'sekolah_name' => $siswa->sekolah?->name ?? '',
                        'foto_url' => $siswa->hasFotoWajah() ? route('admin.manajemen-siswa.data-siswa.foto-wajah', $siswa) : null,
                    ],
                    'attendance' => [
                        'status' => AttendanceStatus::label($lastAttendance->status),
                        'time_in' => DisplayDate::time($lastAttendance->time_in),
                    ],
                ],
            ]);
        }

        // Determine attendance status (hadir vs terlambat) based on tolerance
        $resolvedStatus = 'hadir';
        $start = Carbon::parse($today.' '.$targetSlot->time_start);
        $lateUntil = $start->copy()->addMinutes((int) $targetSlot->tolerance_minutes);

        if ($now->greaterThan($lateUntil)) {
            $resolvedStatus = 'terlambat';
        }

        // Record the attendance
        $attendance = AbsensiSiswa::create([
            'sekolah_id' => $siswa->sekolah_id,
            'siswa_id' => $siswa->id,
            'jadwal_absen_slot_id' => $targetSlot->id,
            'date' => $today,
            'status' => $resolvedStatus,
            'method' => 'rfid',
            'time_in' => $now->format('H:i'),
        ]);

        return response()->json([
            'success' => true,
            'already_recorded' => false,
            'message' => 'Absensi berhasil dicatat.',
            'data' => [
                'siswa' => [
                    'nis' => $siswa->nis,
                    'name' => $siswa->name,
                    'kelas' => $siswa->kelas?->name ?? '-',
                    'sekolah_code' => $siswa->sekolah?->code ?? '',
                    'sekolah_name' => $siswa->sekolah?->name ?? '',
                    'foto_url' => $siswa->hasFotoWajah() ? route('admin.manajemen-siswa.data-siswa.foto-wajah', $siswa) : null,
                ],
                'attendance' => [
                    'status' => AttendanceStatus::label($resolvedStatus),
                    'time_in' => DisplayDate::time($attendance->time_in),
                    'slot_name' => $targetSlot->hari->jadwalAbsen->name,
                    'pelajaran' => $targetSlot->pelajaran?->name ?? '-',
                ],
            ],
        ], 201);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->rfidTodayQuery()
            ->with($this->rfidFeedRelations());

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'siswa.nis',
                'siswa.name',
                'siswa.kelas.name',
                'status',
            ],
            'orderable' => ['time_in', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            $slotName = $row->jadwalSlot?->hari?->jadwalAbsen?->name ?? 'Absensi Mandiri';
            $pelajaranName = $row->jadwalSlot?->pelajaran?->name ?? '-';

            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $slotName,
                $pelajaranName,
                AttendanceStatus::label($row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }

    public function recent(): JsonResponse
    {
        $filtered = $this->rfidTodayQuery();
        $total = (clone $filtered)->count();
        $uniqueStudents = (clone $filtered)->distinct()->count('absensi_siswa.siswa_id');

        $records = $filtered
            ->with($this->rfidFeedRelations())
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'meta' => [
                'total' => $total,
                'unique_students' => $uniqueStudents,
            ],
            'data' => $records->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nis' => $row->siswa?->nis ?? '-',
                    'name' => $row->siswa?->name ?? '-',
                    'kelas' => $row->siswa?->kelas?->name ?? '-',
                    'sekolah_code' => $row->siswa?->sekolah?->code ?? '',
                    'sekolah_name' => $row->siswa?->sekolah?->name ?? '',
                    'slot' => $row->jadwalSlot?->hari?->jadwalAbsen?->name ?? 'Absensi Mandiri',
                    'pelajaran' => $row->jadwalSlot?->pelajaran?->name ?? '-',
                    'status' => $row->status,
                    'status_label' => AttendanceStatus::label($row->status),
                    'time_in' => DisplayDate::time($row->time_in),
                    'foto_url' => $row->siswa && $row->siswa->hasFotoWajah()
                        ? route('admin.manajemen-siswa.data-siswa.foto-wajah', $row->siswa)
                        : null,
                ];
            }),
        ]);
    }

    /**
     * RFID taps for the current school day, without hydrating foto_wajah blobs.
     */
    private function rfidTodayQuery()
    {
        $query = AbsensiSiswa::query()
            ->where('absensi_siswa.method', 'rfid')
            ->whereDate('absensi_siswa.date', now()->toDateString());

        $schoolId = AdminSchoolScope::operatorSekolahId();
        if ($schoolId) {
            $query->where('absensi_siswa.sekolah_id', $schoolId);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function rfidFeedRelations(): array
    {
        return [
            'siswa' => function ($q) {
                $q->select([
                    'siswa.id',
                    'siswa.nis',
                    'siswa.name',
                    'siswa.kelas_id',
                    'siswa.sekolah_id',
                    'siswa.has_foto_wajah',
                ]);
            },
            'siswa.kelas:id,name',
            'siswa.sekolah:id,code,name',
            'jadwalSlot:id,jadwal_absen_hari_id,pelajaran_id',
            'jadwalSlot.pelajaran:id,name',
            'jadwalSlot.hari:id,jadwal_absen_id',
            'jadwalSlot.hari.jadwalAbsen:id,name',
        ];
    }

    public function activeSlots(): JsonResponse
    {
        $dayOfWeek = now()->dayOfWeekIso; // 1 (Senin) - 7 (Minggu)
        $today = now()->toDateString();

        $query = JadwalAbsenSlot::query()
            ->with(['pelajaran', 'hari.jadwalAbsen.sekolah'])
            ->whereHas('hari', function ($q) use ($dayOfWeek) {
                $q->where('is_active', true)
                    ->where('day_of_week', $dayOfWeek);
            })
            ->whereHas('hari.jadwalAbsen', function ($q) {
                $q->where('is_active', true);
            });

        // Scope by operator school if bound
        $schoolId = AdminSchoolScope::operatorSekolahId();
        if ($schoolId) {
            $query->whereHas('hari.jadwalAbsen', function ($q) use ($schoolId) {
                $q->where('sekolah_id', $schoolId);
            });
        }

        $slots = $query->get();
        $now = now();

        $data = $slots->map(function ($slot) use ($now, $today) {
            // Calculate status
            $status = 'upcoming'; // default
            $start = Carbon::parse($today.' '.$slot->time_start);
            $lateStart = $start->copy()->addMinutes((int) $slot->tolerance_minutes);
            $end = Carbon::parse($today.' '.$slot->time_end);

            if ($now->lt($start)) {
                $status = 'upcoming';
            } elseif ($now->between($start, $lateStart)) {
                $status = 'active';
            } elseif ($now->between($lateStart, $end)) {
                $status = 'late';
            } else {
                $status = 'ended';
            }

            $sekolahName = $slot->hari->jadwalAbsen->sekolah?->name ?? 'Semua Unit';
            $sekolahCode = $slot->hari->jadwalAbsen->sekolah?->code ?? 'all';

            return [
                'id' => $slot->id,
                'name' => $slot->hari->jadwalAbsen->name,
                'pelajaran' => $slot->pelajaran?->name ?? 'Absensi Umum',
                'time_start' => substr((string) $slot->time_start, 0, 5),
                'time_end' => substr((string) $slot->time_end, 0, 5),
                'late_start' => $lateStart->format('H:i'),
                'sekolah_name' => $sekolahName,
                'sekolah_code' => $sekolahCode,
                'status' => $status,
                'assignment_type' => $slot->hari->jadwalAbsen->assignment_type,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function jsonError(string $message, mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
