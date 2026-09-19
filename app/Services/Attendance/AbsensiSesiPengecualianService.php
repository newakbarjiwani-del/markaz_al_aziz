<?php

namespace App\Services\Attendance;

use App\Models\AbsensiSesiPengecualian;
use App\Models\JadwalAbsenSlot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AbsensiSesiPengecualianService
{
    private function tableReady(): bool
    {
        return Schema::hasTable('absensi_sesi_pengecualian');
    }

    public function findForSlotDate(int $slotId, string $date): ?AbsensiSesiPengecualian
    {
        if (! $this->tableReady()) {
            return null;
        }

        return AbsensiSesiPengecualian::query()
            ->where('jadwal_absen_slot_id', $slotId)
            ->whereDate('date', $date)
            ->first();
    }

    public function isExempt(int $slotId, string $date): bool
    {
        return $this->findForSlotDate($slotId, $date) !== null;
    }

    /**
     * @param  list<int>  $slotIds
     * @return array<int, AbsensiSesiPengecualian> keyed by slot id
     */
    public function mapForDate(array $slotIds, string $date): array
    {
        if ($slotIds === [] || ! $this->tableReady()) {
            return [];
        }

        return AbsensiSesiPengecualian::query()
            ->whereIn('jadwal_absen_slot_id', $slotIds)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('jadwal_absen_slot_id')
            ->all();
    }

    /**
     * @param  list<int>  $slotIds
     * @return Collection<int, AbsensiSesiPengecualian>
     */
    public function forDateRange(array $slotIds, string $startDate, string $endDate): Collection
    {
        if (! $this->tableReady()) {
            return collect();
        }

        $query = AbsensiSesiPengecualian::query()
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($slotIds !== []) {
            $query->whereIn('jadwal_absen_slot_id', $slotIds);
        }

        return $query->get();
    }

    /**
     * @return array<string, true> keys "{date}:{slotId}"
     */
    public function lookupKeysForDateRange(array $slotIds, string $startDate, string $endDate): array
    {
        $keys = [];

        foreach ($this->forDateRange($slotIds, $startDate, $endDate) as $row) {
            $keys[$row->date->toDateString().':'.$row->jadwal_absen_slot_id] = true;
        }

        return $keys;
    }

    public function markExempt(
        JadwalAbsenSlot $slot,
        string $date,
        string $reason,
        ?User $user = null
    ): AbsensiSesiPengecualian {
        return AbsensiSesiPengecualian::query()->updateOrCreate(
            [
                'jadwal_absen_slot_id' => $slot->id,
                'date' => $date,
            ],
            [
                'reason' => $reason,
                'created_by_user_id' => $user?->id,
            ]
        );
    }

    public function revoke(int $slotId, string $date): bool
    {
        $row = $this->findForSlotDate($slotId, $date);

        if (! $row) {
            return false;
        }

        $row->delete();

        return true;
    }
}
