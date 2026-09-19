<?php

namespace App\Services\Attendance;

use App\Models\AbsensiSiswa;
use App\Models\JadwalAbsenSlot;
use App\Models\Pelajaran;
use App\Models\Siswa;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubjectPeriodAttendanceSummaryService
{
    public function __construct(
        private AbsensiSesiPengecualianService $sesiPengecualianService
    ) {}

    public function getSummaryData(
        string $startDate,
        string $endDate,
        ?int $kelasId = null,
        ?int $pelajaranId = null,
        ?array $siswaIds = null,
        ?int $sekolahId = null
    ): array {
        $startCarbon = Carbon::parse($startDate)->startOfDay();
        $endCarbon = Carbon::parse($endDate)->endOfDay();
        $startStr = $startCarbon->toDateString();
        $endStr = $endCarbon->toDateString();

        $studentQuery = Siswa::query()
            ->with('kelas')
            ->where('status', Siswa::STATUS_ACTIVE);

        if ($kelasId) {
            $studentQuery->where('kelas_id', $kelasId);
        }

        if ($siswaIds !== null) {
            if ($siswaIds === []) {
                return $this->emptyResponse($startStr, $endStr);
            }
            $studentQuery->whereIn('id', $siswaIds);
        }

        if ($sekolahId) {
            $studentQuery->where('sekolah_id', $sekolahId);
        }

        $students = $studentQuery->orderBy('name')->get();

        if ($students->isEmpty()) {
            return $this->emptyResponse($startStr, $endStr);
        }

        $studentIds = $students->pluck('id')->all();

        $pelajaran = $pelajaranId ? Pelajaran::withTrashed()->find($pelajaranId) : null;
        $pelajaranName = $pelajaran?->name ?? 'Semua Mata Pelajaran';

        $slotsQuery = JadwalAbsenSlot::query()
            ->with(['pelajaran' => fn ($q) => $q->withTrashed(), 'hari.jadwalAbsen'])
            ->whereHas('hari', fn (Builder $q) => $q->where('is_active', true));

        if ($pelajaranId) {
            $slotsQuery->where('pelajaran_id', $pelajaranId);
        }

        if ($kelasId) {
            $slotsQuery->whereHas('hari.jadwalAbsen', function (Builder $q) use ($kelasId) {
                $q->withoutGlobalScope(\App\Models\Scopes\OperatorSekolahScope::class)
                    ->where('is_active', true)
                    ->where(function (Builder $sq) use ($kelasId) {
                        $sq->whereHas('kelas', fn (Builder $kq) => $kq->where('kelas.id', $kelasId))
                            ->orWhere('assignment_type', 'sekolah');
                    });
            });
        }

        $activeSlots = $slotsQuery->get();
        $slotsPerDay = $activeSlots->groupBy(fn ($slot) => $slot->hari?->day_of_week ?? 0);

        $todayStr = now()->toDateString();
        $endDateLimit = $endStr < $todayStr ? $endStr : $todayStr;

        $attendanceQuery = AbsensiSiswa::query()
            ->with(['jadwalSlot' => fn ($q) => $q->withTrashed()])
            ->whereIn('siswa_id', $studentIds)
            ->where('date', '>=', $startStr)
            ->where('date', '<=', $endStr.' 23:59:59');

        if ($pelajaranId) {
            $attendanceQuery->where(function (Builder $q) use ($pelajaranId) {
                $q->whereHas('jadwalSlot', fn (Builder $sq) => $sq->withTrashed()->where('pelajaran_id', $pelajaranId))
                    ->orWhereNull('jadwal_absen_slot_id');
            });
        }

        $attendances = $attendanceQuery->get();

        /** @var array<string, array<int, true>> $slotsOnDate */
        $slotsOnDate = [];
        $attendanceSlotIds = [];
        /** @var array<string, true> $conductedDates dates with any attendance for this cohort */
        $conductedDates = [];

        foreach ($attendances as $row) {
            $dateKey = $row->date->toDateString();
            $conductedDates[$dateKey] = true;

            if ($row->jadwal_absen_slot_id) {
                $attendanceSlotIds[(int) $row->jadwal_absen_slot_id] = true;
                $slotsOnDate[$dateKey][(int) $row->jadwal_absen_slot_id] = true;
            }
        }

        /** @var Collection<int, JadwalAbsenSlot> $slotCatalog */
        $slotCatalog = $activeSlots->keyBy('id');
        $missingSlotIds = array_values(array_diff(array_keys($attendanceSlotIds), $slotCatalog->keys()->all()));

        if ($missingSlotIds !== []) {
            $extraSlots = JadwalAbsenSlot::withTrashed()
                ->with(['pelajaran' => fn ($q) => $q->withTrashed(), 'hari.jadwalAbsen'])
                ->whereIn('id', $missingSlotIds)
                ->when($pelajaranId, fn (Builder $q) => $q->where('pelajaran_id', $pelajaranId))
                ->get();

            foreach ($extraSlots as $slot) {
                $slotCatalog->put($slot->id, $slot);
            }
        }

        $exemptLookup = $this->sesiPengecualianService->lookupKeysForDateRange(
            $slotCatalog->keys()->all(),
            $startStr,
            $endDateLimit
        );

        $attendanceMap = [];

        foreach ($attendances as $row) {
            $slotId = $row->jadwal_absen_slot_id;
            $dateKey = $row->date->toDateString();
            $status = strtolower((string) $row->status);
            $recData = [
                'status' => $status,
                'time_in' => $row->time_in ? Carbon::parse($row->time_in)->format('H:i') : null,
                'method' => $row->method,
            ];

            if ($slotId) {
                $attendanceMap[$row->siswa_id][$dateKey][$slotId] = $recData;
                $slotModel = $row->jadwalSlot;
                if ($slotModel && $slotModel->pelajaran_id) {
                    $pelajaranKey = 'pelajaran_'.$slotModel->pelajaran_id;
                    // Keep first seen status for pelajaran fallback (avoid last-write overwrite).
                    if (! isset($attendanceMap[$row->siswa_id][$dateKey][$pelajaranKey])) {
                        $attendanceMap[$row->siswa_id][$dateKey][$pelajaranKey] = $recData;
                    }
                }
            } else {
                $attendanceMap[$row->siswa_id][$dateKey]['general'] = $recData;
            }
        }

        /** @var array<string, Collection<int, JadwalAbsenSlot>> $conductedDaySlots */
        $conductedDaySlots = [];
        $conductedSessionsCount = 0;
        $conductedDaysCount = 0;

        $cursor = $startCarbon->copy();
        while ($cursor->toDateString() <= $endDateLimit) {
            $dStr = $cursor->toDateString();

            // Match DailySubjectAttendanceMatrixService: a day counts only when attendance
            // was actually taken for this cohort. Holidays are not excluded if conducted.
            if (! isset($conductedDates[$dStr])) {
                $cursor->addDay();

                continue;
            }

            $daySlots = $this->resolveSlotsForDate($dStr, $slotsPerDay, $slotCatalog, $slotsOnDate, $pelajaranId)
                ->filter(fn (JadwalAbsenSlot $slot) => ! isset($exemptLookup[$dStr.':'.$slot->id]))
                ->values();

            if ($daySlots->isEmpty()) {
                $cursor->addDay();

                continue;
            }

            $conductedDaySlots[$dStr] = $daySlots;
            $conductedSessionsCount += $daySlots->count();
            $conductedDaysCount++;

            $cursor->addDay();
        }

        $studentSummaryList = [];
        $totalPercentageSum = 0;
        $studentsAtRiskCount = 0;

        foreach ($students as $siswa) {
            $sum = [
                'hadir' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'alpha' => 0,
            ];

            $siswaConductedSessions = 0;

            foreach ($conductedDaySlots as $dStr => $daySlots) {
                foreach ($daySlots as $slot) {
                    $slotId = $slot->id;
                    $pId = $slot->pelajaran_id;
                    $siswaConductedSessions++;

                    $rec = $attendanceMap[$siswa->id][$dStr][$slotId]
                        ?? ($pId ? ($attendanceMap[$siswa->id][$dStr]['pelajaran_'.$pId] ?? null) : null)
                        ?? $attendanceMap[$siswa->id][$dStr]['general']
                        ?? null;

                    // Conducted day + non-exempt slot: missing record counts as alpha.
                    $status = $rec['status'] ?? AttendanceStatus::ALPHA;

                    if (isset($sum[$status])) {
                        $sum[$status]++;
                    }
                }
            }

            $effectiveTotal = $siswaConductedSessions;
            $presentCount = $sum['hadir'] + $sum['terlambat'];
            $percentage = $effectiveTotal > 0 ? round(($presentCount / $effectiveTotal) * 100, 1) : 0;

            if ($effectiveTotal > 0 && $percentage < 75.0) {
                $studentsAtRiskCount++;
            }

            $totalPercentageSum += $percentage;

            $studentSummaryList[] = [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'name' => $siswa->name,
                'kelas_name' => $siswa->kelas?->name ?? '-',
                'pelajaran_name' => $pelajaranName,
                'total_sessions' => $effectiveTotal,
                'hadir' => $sum['hadir'],
                'terlambat' => $sum['terlambat'],
                'izin' => $sum['izin'],
                'sakit' => $sum['sakit'],
                'cuti' => $sum['cuti'],
                'alpha' => $sum['alpha'],
                'percentage' => $percentage,
                'has_conducted_sessions' => $effectiveTotal > 0,
            ];
        }

        $classAvgPercentage = (count($students) > 0 && $conductedSessionsCount > 0)
            ? round($totalPercentageSum / count($students), 1)
            : 0;

        return [
            'start_date' => $startStr,
            'end_date' => $endStr,
            'period_label' => $startCarbon->isoFormat('D MMMM YYYY').' - '.$endCarbon->isoFormat('D MMMM YYYY'),
            'pelajaran_id' => $pelajaranId,
            'pelajaran_name' => $pelajaranName,
            'total_students' => count($students),
            'total_sessions_count' => $conductedSessionsCount,
            'active_school_days' => $conductedDaysCount,
            'class_avg_percentage' => $classAvgPercentage,
            'students_at_risk_count' => $studentsAtRiskCount,
            'students' => $studentSummaryList,
        ];
    }

    /**
     * Merge scheduled slots for the weekday with slots that actually have attendance on the date
     * (mirrors DailySubjectAttendanceMatrixService fallback behaviour).
     *
     * @param  array<string, array<int, true>>  $slotsOnDate
     */
    private function resolveSlotsForDate(
        string $dateStr,
        Collection $slotsPerDay,
        Collection $slotCatalog,
        array $slotsOnDate,
        ?int $pelajaranId
    ): Collection {
        $dow = Carbon::parse($dateStr)->dayOfWeekIso;
        /** @var Collection<int, JadwalAbsenSlot> $scheduled */
        $scheduled = ($slotsPerDay->get($dow) ?? collect())->values();

        $fromAttendance = collect(array_keys($slotsOnDate[$dateStr] ?? []))
            ->map(fn ($id) => $slotCatalog->get((int) $id))
            ->filter()
            ->values();

        if ($pelajaranId) {
            $fromAttendance = $fromAttendance->filter(
                fn (JadwalAbsenSlot $slot) => (int) $slot->pelajaran_id === $pelajaranId
            );
        }

        // Same as DailySubjectAttendanceMatrixService: use weekday schedule when present,
        // otherwise fall back to slots that have attendance rows on this date.
        if ($scheduled->isNotEmpty()) {
            return $scheduled->values();
        }

        return $fromAttendance->values();
    }

    private function emptyResponse(string $startStr, string $endStr): array
    {
        return [
            'start_date' => $startStr,
            'end_date' => $endStr,
            'period_label' => $startStr.' - '.$endStr,
            'pelajaran_id' => null,
            'pelajaran_name' => 'Semua Mata Pelajaran',
            'total_students' => 0,
            'total_sessions_count' => 0,
            'active_school_days' => 0,
            'class_avg_percentage' => 0,
            'students_at_risk_count' => 0,
            'students' => [],
        ];
    }
}
