<?php

namespace App\Services;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves teaching scope for portal guru from jadwal absen slots
 * (supports multiple kelas per schedule), not legacy riwayat_mengajar names.
 */
class GuruTeachingScope
{
    public function __construct(private Guru $guru) {}

    public static function for(Guru $guru): self
    {
        return new self($guru);
    }

    /** @return Collection<int, JadwalAbsen> */
    public function activeJadwal(): Collection
    {
        return JadwalAbsen::query()
            ->where('is_active', true)
            ->whereHas('hari', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('hari.slots', fn (Builder $q) => $q->where('guru_id', $this->guru->id))
            ->with(['kelas', 'sekolah'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Distinct class / assignment labels keyed by ID or assignment key.
     *
     * @return array<int|string, string>
     */
    public function classLabels(): array
    {
        $labels = [];

        foreach ($this->activeJadwal() as $jadwal) {
            if ($jadwal->isKelasAssignment()) {
                foreach ($jadwal->kelas as $kelas) {
                    $labels[(int) $kelas->id] = $kelas->name;
                }

                continue;
            }

            if ($jadwal->coversAllSchools()) {
                $labels['all-schools'] = 'Semua sekolah';

                continue;
            }

            $key = 'sekolah-'.($jadwal->sekolah_id ?? '0');
            $labels[$key] = 'Semua siswa · '.($jadwal->sekolah?->name ?? 'Sekolah');
        }

        return $labels;
    }

    public function classIds(): array
    {
        $ids = [];
        foreach ($this->activeJadwal() as $jadwal) {
            if ($jadwal->isKelasAssignment()) {
                foreach ($jadwal->kelas as $kelas) {
                    $ids[] = (int) $kelas->id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function classCount(): int
    {
        return count($this->classLabels());
    }

    public function hasTeachingAssignment(): bool
    {
        return JadwalAbsenSlot::query()
            ->where('guru_id', $this->guru->id)
            ->whereHas('hari', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('hari.jadwalAbsen', fn (Builder $q) => $q->where('is_active', true))
            ->exists();
    }

    public function applyAttendanceScope(Builder $query): void
    {
        if (! $this->hasTeachingAssignment()) {
            $query->whereRaw('0 = 1');

            return;
        }

        $classIds = $this->classIds();

        $query->where(function (Builder $q) use ($classIds) {
            $q->whereHas('jadwalSlot', fn (Builder $sq) => $sq->where('guru_id', $this->guru->id));
            if (! empty($classIds)) {
                $q->orWhereHas('siswa', fn (Builder $sq) => $sq->whereIn('kelas_id', $classIds));
            }
        });
    }

    public function todayPresentStudentCount(): int
    {
        $query = AbsensiSiswa::query()
            ->whereDate('date', today())
            ->whereIn('status', ['hadir', 'terlambat']);

        $this->applyAttendanceScope($query);

        return (int) $query->distinct()->count('siswa_id');
    }
}
