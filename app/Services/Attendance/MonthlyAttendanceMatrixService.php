<?php

namespace App\Services\Attendance;

use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlyAttendanceMatrixService
{
    public function getMatrixData(
        string $month,
        ?int $kelasId = null,
        ?array $siswaIds = null,
        ?int $sekolahId = null
    ): array {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $monthStart = $monthDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $monthDate->copy()->endOfMonth()->toDateString();
        $daysInMonth = $monthDate->daysInMonth;

        // Build days header
        $daysHeader = [];
        $dayNamesShort = [
            1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min',
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($monthDate->year, $monthDate->month, $day);
            $dayOfWeek = $currentDate->dayOfWeekIso; // 1 (Mon) .. 7 (Sun)
            $daysHeader[$day] = [
                'day' => $day,
                'date' => $currentDate->toDateString(),
                'day_name' => $dayNamesShort[$dayOfWeek] ?? '',
                'is_weekend' => in_array($dayOfWeek, [6, 7], true),
            ];
        }

        // Query students
        $studentQuery = Siswa::query()
            ->with('kelas')
            ->where('status', Siswa::STATUS_ACTIVE);

        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        }

        if ($siswaIds !== null) {
            if ($siswaIds === []) {
                return $this->emptyResponse($monthDate, $daysInMonth, $daysHeader);
            }
            $studentQuery->whereIn('id', $siswaIds);
        }

        if ($sekolahId) {
            $studentQuery->where('sekolah_id', $sekolahId);
        }

        $students = $studentQuery->orderBy('name')->get();

        if ($students->isEmpty()) {
            return $this->emptyResponse($monthDate, $daysInMonth, $daysHeader);
        }

        $studentIds = $students->pluck('id')->all();

        // Fetch attendance records in month
        $attendances = AbsensiSiswa::query()
            ->whereIn('siswa_id', $studentIds)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();

        // Map attendance by siswa_id -> day
        $attendanceMap = [];
        foreach ($attendances as $row) {
            $day = (int) Carbon::parse($row->date)->format('j');
            $status = strtolower((string) $row->status);
            
            // Priority ordering if multiple slots on same day:
            // hadir / terlambat > izin / sakit / cuti > alpha
            if (! isset($attendanceMap[$row->siswa_id][$day])) {
                $attendanceMap[$row->siswa_id][$day] = [
                    'status' => $status,
                    'time_in' => $row->time_in ? Carbon::parse($row->time_in)->format('H:i') : null,
                ];
            } else {
                $existingStatus = $attendanceMap[$row->siswa_id][$day]['status'];
                if ($status === AttendanceStatus::HADIR || ($status === AttendanceStatus::TERLAMBAT && $existingStatus !== AttendanceStatus::HADIR)) {
                    $attendanceMap[$row->siswa_id][$day] = [
                        'status' => $status,
                        'time_in' => $row->time_in ? Carbon::parse($row->time_in)->format('H:i') : null,
                    ];
                }
            }
        }

        // Track which days had attendance conducted per class
        $classDaysWithAttendance = [];
        foreach ($students as $siswa) {
            if (isset($attendanceMap[$siswa->id])) {
                foreach ($attendanceMap[$siswa->id] as $day => $rec) {
                    $classId = $siswa->kelas_id ?? 0;
                    $classDaysWithAttendance[$classId][$day] = true;
                    $classDaysWithAttendance['all'][$day] = true;
                }
            }
        }

        // Build matrix per student
        $studentMatrix = [];
        foreach ($students as $siswa) {
            $daysData = [];
            $summary = [
                'hadir' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'alpha' => 0,
            ];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $rec = $attendanceMap[$siswa->id][$day] ?? null;
                $status = $rec['status'] ?? null;
                $isWeekend = $daysHeader[$day]['is_weekend'];
                $dateStr = $daysHeader[$day]['date'];

                // If no explicit attendance record exists, but attendance was conducted on this non-weekend date
                if (! $status && ! $isWeekend && $dateStr <= now()->toDateString()) {
                    $classId = $siswa->kelas_id ?? 0;
                    if (! empty($classDaysWithAttendance[$classId][$day]) || ! empty($classDaysWithAttendance['all'][$day])) {
                        $status = AttendanceStatus::ALPHA;
                    }
                }

                if ($status && isset($summary[$status])) {
                    $summary[$status]++;
                }

                $daysData[$day] = [
                    'status' => $status,
                    'time_in' => $rec['time_in'] ?? null,
                    'is_weekend' => $isWeekend,
                ];
            }

            $studentMatrix[] = [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas_name' => $siswa->kelas?->nama_kelas ?? $siswa->kelas?->name ?? '-',
                'days' => $daysData,
                'summary' => $summary,
            ];
        }

        return [
            'month' => $monthDate->format('Y-m'),
            'month_label' => $monthDate->isoFormat('MMMM YYYY'),
            'days_in_month' => $daysInMonth,
            'days_header' => array_values($daysHeader),
            'students' => $studentMatrix,
        ];
    }

    private function emptyResponse(Carbon $monthDate, int $daysInMonth, array $daysHeader): array
    {
        return [
            'month' => $monthDate->format('Y-m'),
            'month_label' => $monthDate->isoFormat('MMMM YYYY'),
            'days_in_month' => $daysInMonth,
            'days_header' => array_values($daysHeader),
            'students' => [],
        ];
    }
}
