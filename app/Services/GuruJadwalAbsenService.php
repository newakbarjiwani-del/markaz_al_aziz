<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\JadwalAbsenSlot;
use App\Support\Weekday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GuruJadwalAbsenService
{
    /** @return Collection<int, JadwalAbsenSlot> */
    public static function todaySlotsForGuru(Guru $guru, ?Carbon $at = null): Collection
    {
        $at = $at ?? now();
        $dayOfWeek = Weekday::fromDate($at);

        return JadwalAbsenSlot::query()
            ->with([
                'pelajaran',
                'guru',
                'hari.jadwalAbsen.sekolah',
                'hari.jadwalAbsen.kelas',
            ])
            ->where('guru_id', $guru->id)
            ->whereHas('hari', fn ($q) => $q
                ->where('is_active', true)
                ->where('day_of_week', $dayOfWeek))
            ->whereHas('hari.jadwalAbsen', fn ($q) => $q->where('is_active', true))
            ->orderBy('time_start')
            ->get();
    }

    /** @return list<array<string, mixed>> */
    public static function todayScheduleCards(Guru $guru, ?Carbon $at = null): array
    {
        return self::todaySlotsForGuru($guru, $at)
            ->map(function (JadwalAbsenSlot $slot) use ($at) {
                $jadwal = $slot->hari->jadwalAbsen;
                $at = $at ?? now();

                return [
                    'id' => $slot->id,
                    'jadwal_name' => $jadwal->name,
                    'pelajaran' => $slot->pelajaran?->name ?? '-',
                    'day_label' => Weekday::label((int) $slot->hari->day_of_week),
                    'time_start' => substr((string) $slot->time_start, 0, 5),
                    'time_end' => substr((string) $slot->time_end, 0, 5),
                    'tolerance_minutes' => $slot->tolerance_minutes,
                    'is_active_window' => $slot->isWithinWindow($at),
                    'student_count' => $jadwal->resolvedStudentCount(),
                    'assignment_label' => $jadwal->assignmentLabel(),
                    'covers_all_schools' => $jadwal->coversAllSchools(),
                ];
            })
            ->values()
            ->all();
    }
}
