<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UjianSoal extends BaseModel
{
    public const JENIS_PILIHAN_GANDA = 'pilihan_ganda';

    public const JENIS_ESSAY = 'essay';

    protected $table = 'ujian_soal';

    protected $fillable = [
        'ujian_id',
        'sort_order',
        'jenis',
        'pertanyaan',
        'poin',
        'opsi',
        'kunci',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'poin' => 'decimal:2',
            'opsi' => 'array',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(UjianJawaban::class, 'ujian_soal_id');
    }

    public function isPilihanGanda(): bool
    {
        return $this->jenis === self::JENIS_PILIHAN_GANDA;
    }

    /**
     * @return array<string, string>
     */
    public static function jenisLabels(): array
    {
        return [
            self::JENIS_PILIHAN_GANDA => 'Pilihan ganda',
            self::JENIS_ESSAY => 'Essay',
        ];
    }
}
