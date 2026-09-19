<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjianJawaban extends BaseModel
{
    protected $table = 'ujian_jawaban';

    protected $fillable = [
        'attempt_id',
        'ujian_soal_id',
        'jawaban',
        'is_benar',
        'poin_didapat',
    ];

    protected function casts(): array
    {
        return [
            'is_benar' => 'boolean',
            'poin_didapat' => 'decimal:2',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(UjianAttempt::class, 'attempt_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(UjianSoal::class, 'ujian_soal_id');
    }
}
