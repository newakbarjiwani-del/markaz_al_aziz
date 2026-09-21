<?php

namespace App\Models;

use Database\Factories\TahfidzHalaqohFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahfidzHalaqoh extends BaseModel
{
    /** @use HasFactory<TahfidzHalaqohFactory> */
    use HasFactory;

    protected $table = 'tahfidz_halaqoh';

    protected $fillable = [
        'program_id',
        'sekolah_id',
        'guru_id',
        'name',
    ];

    /**
     * @return BelongsTo<TahfidzProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(TahfidzProgram::class, 'program_id');
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * @return BelongsTo<Guru, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * @return HasMany<TahfidzHalaqohAnggota, $this>
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(TahfidzHalaqohAnggota::class, 'halaqoh_id');
    }

    /**
     * @return HasMany<TahfidzJadwal, $this>
     */
    public function jadwal(): HasMany
    {
        return $this->hasMany(TahfidzJadwal::class, 'halaqoh_id')
            ->orderBy('day_of_week')
            ->orderBy('time_start');
    }

    public function displayName(): string
    {
        if (filled($this->name)) {
            return (string) $this->name;
        }

        $guruName = $this->guru?->name ?? 'Ustadzah';

        return 'Halaqoh Ustadzah '.$guruName;
    }
}
