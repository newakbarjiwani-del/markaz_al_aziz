<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class JadwalAbsen extends BaseModel
{
    protected $table = 'jadwal_absen';

    protected $fillable = [
        'sekolah_id', 'name', 'assignment_type', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'jadwal_absen_kelas', 'jadwal_absen_id', 'kelas_id')
            ->withTimestamps();
    }

    public function hari(): HasMany
    {
        return $this->hasMany(JadwalAbsenHari::class, 'jadwal_absen_id');
    }

    public function activeHari(): HasMany
    {
        return $this->hari()->where('is_active', true);
    }

    public function isKelasAssignment(): bool
    {
        return $this->assignment_type === 'kelas';
    }

    public function isSekolahAssignment(): bool
    {
        return $this->assignment_type === 'sekolah';
    }

    public function isAllSchoolsAssignment(): bool
    {
        return $this->isSekolahAssignment() && $this->coversAllSchools();
    }

    public function coversAllSchools(): bool
    {
        return $this->sekolah_id === null;
    }

    /**
     * Kelas IDs attached to this jadwal.
     * Prefer pivot rows when the model is persisted so soft-deleted / scoped
     * Kelas models cannot wipe the list after eager load.
     *
     * @return list<int>
     */
    public function assignedKelasIds(): array
    {
        if ($this->exists) {
            return DB::table('jadwal_absen_kelas')
                ->where('jadwal_absen_id', $this->getKey())
                ->orderBy('kelas_id')
                ->pluck('kelas_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ($this->relationLoaded('kelas')) {
            return array_values(array_map('intval', $this->kelas->modelKeys()));
        }

        return [];
    }

    public function assignmentLabel(): string
    {
        if ($this->isKelasAssignment()) {
            $kelasIds = $this->assignedKelasIds();
            if ($kelasIds === []) {
                return 'Kelas: (belum dipilih)';
            }

            $names = Kelas::withTrashed()
                ->whereIn('id', $kelasIds)
                ->orderBy('name')
                ->pluck('name')
                ->filter()
                ->values();

            return 'Kelas: '.$names->implode(', ');
        }

        if ($this->coversAllSchools()) {
            return 'Semua siswa (semua sekolah)';
        }

        return 'Semua siswa sekolah';
    }

    public function resolvedStudentCount(): int
    {
        return $this->resolvedStudentQuery()->count();
    }

    public function resolvedStudentQuery()
    {
        $query = Siswa::query()->whereIn('status', [Siswa::STATUS_ACTIVE, Siswa::STATUS_PENDING]);

        if ($this->isSekolahAssignment()) {
            if ($this->sekolah_id !== null) {
                $query->where('sekolah_id', $this->sekolah_id);
            }

            return $query;
        }

        $kelasIds = $this->assignedKelasIds();
        if ($kelasIds === []) {
            return $query->whereRaw('0 = 1');
        }

        // Kelas list is authoritative — do not also require jadwal.sekolah_id
        // (avoids empty results when siswa.sekolah_id and jadwal.sekolah_id diverge).
        return $query->whereIn('kelas_id', $kelasIds);
    }
}
