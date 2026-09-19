<?php

namespace App\Services;

use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenHari;
use App\Support\Weekday;
use Illuminate\Support\Facades\DB;

class JadwalAbsenSyncService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function sync(JadwalAbsen $jadwal, array $payload): JadwalAbsen
    {
        return DB::transaction(function () use ($jadwal, $payload) {
            $jadwal->fill([
                'sekolah_id' => $payload['sekolah_id'],
                'name' => $payload['name'],
                'assignment_type' => $payload['assignment_type'],
                'is_active' => filter_var($payload['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notes' => $payload['notes'] ?? null,
            ])->save();

            if ($jadwal->assignment_type === 'kelas') {
                $jadwal->kelas()->sync($payload['kelas_ids'] ?? []);
            } else {
                $jadwal->kelas()->detach();
            }

            $days = $payload['days'] ?? [];
            $activeDayNumbers = [];

            foreach (Weekday::numbers() as $dayOfWeek) {
                $dayData = $days[$dayOfWeek] ?? $days[(string) $dayOfWeek] ?? null;
                $isActive = filter_var($dayData['active'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $isActive) {
                    $this->removeDay($jadwal, $dayOfWeek);

                    continue;
                }

                $activeDayNumbers[] = $dayOfWeek;
                $hari = $jadwal->hari()
                    ->withTrashed()
                    ->firstOrNew(['day_of_week' => $dayOfWeek]);

                if (! $hari->jadwal_absen_id) {
                    $hari->jadwal_absen_id = $jadwal->id;
                }

                if ($hari->trashed()) {
                    $hari->restore();
                }

                $hari->fill(['is_active' => true])->save();

                $this->syncSlots($hari, $dayData['slots'] ?? []);
            }

            $jadwal->hari()
                ->whereNotIn('day_of_week', $activeDayNumbers)
                ->each(fn (JadwalAbsenHari $hari) => $this->removeDayRecord($hari));

            return $jadwal->fresh(['sekolah', 'kelas', 'hari.slots.pelajaran', 'hari.slots.guru']);
        });
    }

    private function removeDay(JadwalAbsen $jadwal, int $dayOfWeek): void
    {
        $hari = $jadwal->hari()->where('day_of_week', $dayOfWeek)->first();
        if ($hari) {
            $this->removeDayRecord($hari);
        }
    }

    private function removeDayRecord(JadwalAbsenHari $hari): void
    {
        $hari->slots()->delete();
        $hari->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     */
    private function syncSlots(JadwalAbsenHari $hari, array $slots): void
    {
        $hari->slots()->delete();

        foreach (array_values($slots) as $index => $slot) {
            if (empty($slot['pelajaran_id']) || empty($slot['guru_id'])) {
                continue;
            }

            $hari->slots()->create([
                'pelajaran_id' => $slot['pelajaran_id'],
                'guru_id' => $slot['guru_id'],
                'time_start' => $slot['time_start'],
                'time_end' => $slot['time_end'],
                'tolerance_minutes' => (int) ($slot['tolerance_minutes'] ?? 15),
                'sort_order' => $index,
            ]);
        }
    }
}
