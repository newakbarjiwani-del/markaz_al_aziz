<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HariLibur extends BaseModel
{
    public const APPLIES_BOTH = 'both';

    public const APPLIES_SISWA = 'siswa';

    public const APPLIES_GURU = 'guru';

    protected $table = 'hari_libur';

    protected $fillable = [
        'sekolah_id',
        'date',
        'name',
        'applies_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function appliesToLabel(): string
    {
        return match ($this->applies_to) {
            self::APPLIES_SISWA => 'Siswa',
            self::APPLIES_GURU => 'Guru',
            default => 'Siswa & Guru',
        };
    }
}
