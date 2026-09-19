<?php

namespace App\Models;

use App\Support\TahfidzProgressStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahfidzProgress extends BaseModel
{
    protected $table = 'tahfidz_progress';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'surah_id',
        'ayah_from',
        'ayah_to',
        'status',
        'last_reviewed_at',
        'verified_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * @return BelongsTo<TahfidzSurah, $this>
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(TahfidzSurah::class, 'surah_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<TahfidzMurajaahLog, $this>
     */
    public function murajaahLogs(): HasMany
    {
        return $this->hasMany(TahfidzMurajaahLog::class, 'progress_id')->orderByDesc('reviewed_at');
    }

    public function rangeLabel(): string
    {
        return ($this->surah?->label() ?? 'Surah').' ayat '.$this->ayah_from.'–'.$this->ayah_to;
    }

    public function statusLabel(): string
    {
        return TahfidzProgressStatus::label($this->status);
    }
}
