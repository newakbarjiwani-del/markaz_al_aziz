<?php

namespace App\Models;

use Database\Factories\TahfidzProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahfidzProgram extends BaseModel
{
    /** @use HasFactory<TahfidzProgramFactory> */
    use HasFactory;

    protected $table = 'tahfidz_program';

    protected $fillable = [
        'sekolah_id',
        'tahun_akademik_id',
        'name',
        'angkatan',
        'peserta_label',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'angkatan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * @return BelongsTo<TahunAkademik, $this>
     */
    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    /**
     * @return HasMany<TahfidzHalaqoh, $this>
     */
    public function halaqoh(): HasMany
    {
        return $this->hasMany(TahfidzHalaqoh::class, 'program_id')->orderBy('id');
    }

    /**
     * @return HasMany<TahfidzRekap, $this>
     */
    public function rekap(): HasMany
    {
        return $this->hasMany(TahfidzRekap::class, 'program_id')->orderByDesc('starts_on');
    }

    public function title(): string
    {
        return trim(sprintf(
            'REKAP PENCAPAIAN %s %s ANGKATAN %d',
            $this->peserta_label,
            $this->name,
            $this->angkatan,
        ));
    }

    public function label(): string
    {
        return trim($this->name.' Angkatan '.$this->angkatan);
    }
}
