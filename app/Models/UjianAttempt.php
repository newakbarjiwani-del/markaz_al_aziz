<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UjianAttempt extends BaseModel
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    protected $table = 'ujian_attempt';

    protected $fillable = [
        'ujian_id',
        'siswa_id',
        'started_at',
        'submitted_at',
        'status',
        'skor_mcq',
        'skor_max_mcq',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'skor_mcq' => 'decimal:2',
            'skor_max_mcq' => 'decimal:2',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(UjianJawaban::class, 'attempt_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
