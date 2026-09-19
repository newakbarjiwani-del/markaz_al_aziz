<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapor extends BaseModel
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    protected $table = 'rapor';

    protected $fillable = [
        'siswa_id',
        'tahun_akademik_id',
        'semester',
        'status',
        'catatan_wali',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function mapel(): HasMany
    {
        return $this->hasMany(RaporMapel::class, 'rapor_id');
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }
}
