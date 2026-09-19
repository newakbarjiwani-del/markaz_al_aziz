<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master catalog of prestasi (achievement) types.
 *
 * Universal by default (`sekolah_id` null = shared across all schools).
 * Sub-data rows live on `prestasi_siswa` / `prestasi_guru` via `jenis_prestasi_id`.
 */
class JenisPrestasi extends BaseModel
{
    protected $table = 'jenis_prestasi';

    protected $fillable = [
        'sekolah_id',
        'kode',
        'bidang',
        'nama',
        'point',
        'keterangan',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'point' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function prestasiSiswa(): HasMany
    {
        return $this->hasMany(PrestasiSiswa::class, 'jenis_prestasi_id');
    }

    public function prestasiGuru(): HasMany
    {
        return $this->hasMany(PrestasiGuru::class, 'jenis_prestasi_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('nama')->orderBy('id');
    }
}
