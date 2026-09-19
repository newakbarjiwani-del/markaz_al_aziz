<?php

namespace App\Services\Attendance;

use App\Models\AbsensiSiswa;
use App\Models\JadwalAbsenSlot;
use App\Models\Siswa;
use App\Support\AttendanceStatus;
use App\Support\DisplayDate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DailySubjectAttendanceMatrixService
{
    public function __construct(
        private AbsensiSesiPengecualianService $sesiPengecualianService
    ) {}

    public function getDailyMatrixData(
        string $date,
        int $kelasId,
        ?array $siswaIds = null,
        ?int $sekolahId = null
    ): array {
        $carbonDate = Carbon::parse($date);
        $dateStr = $carbonDate->toDateString();
        $dayOfWeek = $carbonDate->dayOfWeekIso; // 1 (Mon) .. 7 (Sun)
        $isWeekend = in_array($dayOfWeek, [6, 7], true);

        // Fetch students in class
        $studentQuery = Siswa::query()
            ->with('kelas')
            ->where('kelas_id', $kelasId)
            ->where('status', Siswa::STATUS_ACTIVE);

        if ($siswaIds !== null) {
            if ($siswaIds === []) {
                return $this->emptyResponse($carbonDate, $kelasId);
            }
            $studentQuery->whereIn('id', $siswaIds);
        }

        if ($sekolahId) {
            $studentQuery->where('sekolah_id', $sekolahId);
        }

        $students = $studentQuery->orderBy('name')->get();

        if ($students->isEmpty()) {
            return $this->emptyResponse($carbonDate, $kelasId);
        }

        $effectiveSekolahId = $sekolahId ?? $students->first()?->sekolah_id ?? $students->first()?->kelas?->sekolah_id;
        $holiday = app(\App\Services\HariLiburService::class)->findForDate($dateStr, $effectiveSekolahId, 'siswa');
        $isHoliday = ($holiday !== null);
        $holidayName = $holiday?->name;

        $studentIds = $students->pluck('id')->all();

        // Fetch slots for this day of week and class
        $slots = JadwalAbsenSlot::query()
            ->with(['pelajaran', 'guru', 'hari.jadwalAbsen'])
            ->whereHas('hari', fn (Builder $q) => $q->where('is_active', true)->where('day_of_week', $dayOfWeek))
            ->whereHas('hari.jadwalAbsen', function (Builder $q) use ($kelasId, $sekolahId) {
                $q->withoutGlobalScope(\App\Models\Scopes\OperatorSekolahScope::class)
                    ->where('is_active', true)
                    ->where(function (Builder $sq) use ($kelasId, $sekolahId) {
                        $sq->whereHas('kelas', fn (Builder $kq) => $kq->where('kelas.id', $kelasId))
                            ->orWhere('assignment_type', 'sekolah');
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('time_start')
            ->get();

        if ($slots->isNotEmpty()) {
            $isWeekend = false;
        }

        // If no slot matching day_of_week, also fallback to slots that have attendance records on dateStr
        if ($slots->isEmpty()) {
            $slotIdsFromAttendance = AbsensiSiswa::query()
                ->whereIn('siswa_id', $studentIds)
                ->whereDate('date', $dateStr)
                ->whereNotNull('jadwal_absen_slot_id')
                ->pluck('jadwal_absen_slot_id')
                ->unique()
                ->all();

            if (! empty($slotIdsFromAttendance)) {
                $slots = JadwalAbsenSlot::query()
                    ->with(['pelajaran', 'guru', 'hari.jadwalAbsen'])
                    ->whereIn('id', $slotIdsFromAttendance)
                    ->orderBy('sort_order')
                    ->orderBy('time_start')
                    ->get();
            }
        }

        $slotIds = $slots->pluck('id')->all();
        $exemptionMap = $this->sesiPengecualianService->mapForDate($slotIds, $dateStr);

        // Build subject headers metadata
        $subjectHeaders = [];
        foreach ($slots as $idx => $slot) {
            $exemption = $exemptionMap[$slot->id] ?? null;
            $subjectHeaders[] = [
                'id' => $slot->id,
                'jam' => 'Jam ' . ($idx + 1),
                'pelajaran_name' => $slot->pelajaran?->name ?? 'Absensi',
                'guru_name' => $slot->guru?->name ?? '-',
                'time_start' => $slot->time_start ? DisplayDate::time($slot->time_start) : '',
                'time_end' => $slot->time_end ? DisplayDate::time($slot->time_end) : '',
                'is_exempt' => $exemption !== null,
                'exempt_reason' => $exemption?->reason,
            ];
        }

        // Fetch attendance records for this date
        $attendances = AbsensiSiswa::query()
            ->with(['jadwalSlot' => fn ($q) => $q->withTrashed()])
            ->whereIn('siswa_id', $studentIds)
            ->whereDate('date', $dateStr)
            ->get();

        $attendanceMap = [];
        $hasAnyAttendanceOnDate = false;

        foreach ($attendances as $row) {
            $hasAnyAttendanceOnDate = true;
            $slotId = $row->jadwal_absen_slot_id;
            $status = strtolower((string) $row->status);
            $recData = [
                'status' => $status,
                'time_in' => $row->time_in ? Carbon::parse($row->time_in)->format('H:i') : null,
                'method' => $row->method,
            ];

            if ($slotId) {
                $attendanceMap[$row->siswa_id][$slotId] = $recData;
                $slotModel = $row->jadwalSlot;
                if ($slotModel && $slotModel->pelajaran_id) {
                    $attendanceMap[$row->siswa_id]['pelajaran_' . $slotModel->pelajaran_id] = $recData;
                }
            } else {
                // General daily attendance (e.g. QR scan check-in, perizinan without slot_id)
                $attendanceMap[$row->siswa_id]['general'] = $recData;
            }
        }

        // Build student matrix per subject slot
        $studentMatrix = [];
        foreach ($students as $siswa) {
            $subjectsData = [];
            $summary = [
                'hadir' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'alpha' => 0,
            ];

            foreach ($slots as $slot) {
                $slotId = $slot->id;
                $pelajaranId = $slot->pelajaran_id;
                $isExempt = isset($exemptionMap[$slotId]);

                $rec = $attendanceMap[$siswa->id][$slotId]
                    ?? ($pelajaranId ? ($attendanceMap[$siswa->id]['pelajaran_' . $pelajaranId] ?? null) : null)
                    ?? $attendanceMap[$siswa->id]['general']
                    ?? null;
                $status = $rec['status'] ?? null;

                if ($isExempt && ($status === null || $status === AttendanceStatus::ALPHA)) {
                    $status = null;
                }

                // Infer alpha if attendance was conducted on this non-weekend, non-holiday date and no record exists
                if (! $status && ! $isExempt && ! $isWeekend && ! $isHoliday && $dateStr <= now()->toDateString() && $hasAnyAttendanceOnDate) {
                    $status = AttendanceStatus::ALPHA;
                }

                if ($status && isset($summary[$status])) {
                    $summary[$status]++;
                }

                $subjectsData[] = [
                    'slot_id' => $slotId,
                    'status' => $status,
                    'is_exempt' => $isExempt,
                    'exempt_reason' => $isExempt ? ($exemptionMap[$slotId]->reason ?? null) : null,
                    'time_in' => $rec['time_in'] ?? null,
                    'method' => $rec['method'] ?? null,
                ];
            }

            $studentMatrix[] = [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas_name' => $siswa->kelas?->nama_kelas ?? $siswa->kelas?->name ?? '-',
                'subjects' => $subjectsData,
                'summary' => $summary,
            ];
        }

        $dayNamesShort = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ];

        return [
            'date' => $dateStr,
            'date_label' => DisplayDate::date($carbonDate),
            'day_name' => $dayNamesShort[$dayOfWeek] ?? '',
            'is_weekend' => $isWeekend,
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName,
            'subject_headers' => $subjectHeaders,
            'students' => $studentMatrix,
        ];
    }

    private function emptyResponse(Carbon $carbonDate, int $kelasId): array
    {
        $dayNamesShort = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ];

        return [
            'date' => $carbonDate->toDateString(),
            'date_label' => DisplayDate::date($carbonDate),
            'day_name' => $dayNamesShort[$carbonDate->dayOfWeekIso] ?? '',
            'is_weekend' => in_array($carbonDate->dayOfWeekIso, [6, 7], true),
            'subject_headers' => [],
            'students' => [],
        ];
    }
}
