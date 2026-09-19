<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Ujian extends BaseModel
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'ujian';

    protected $fillable = [
        'sekolah_id',
        'tahun_akademik_id',
        'semester',
        'mata_pelajaran_id',
        'kelas_id',
        'guru_id',
        'title',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'status',
        'max_attempts',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(UjianSoal::class, 'ujian_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UjianAttempt::class, 'ujian_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isWithinWindow(?\DateTimeInterface $at = null): bool
    {
        $moment = $at ? Carbon::parse($at) : now();

        if ($this->starts_at && $moment->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $moment->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function allowsSiswa(Siswa $siswa): bool
    {
        if ($this->kelas_id !== null && (int) $this->kelas_id !== (int) $siswa->kelas_id) {
            return false;
        }

        if ($this->sekolah_id !== null && (int) $this->sekolah_id !== (int) $siswa->sekolah_id) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PUBLISHED => 'Dipublikasikan',
            self::STATUS_CLOSED => 'Ditutup',
        ];
    }
}
