<?php

namespace App\Models;

use App\Support\PelanggaranLevel;
use App\Support\PelanggaranSanction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master catalog of pelanggaran (violation) types.
 *
 * Universal by default (`sekolah_id` null = shared across all schools); set
 * `sekolah_id` to scope the catalog to one school. Sub-data rows live on
 * `pelanggaran_siswa` / `pelanggaran_guru` via `jenis_pelanggaran_id`.
 */
class JenisPelanggaran extends BaseModel
{
    protected $table = 'jenis_pelanggaran';

    protected $fillable = [
        'sekolah_id',
        'kode',
        'level',
        'bidang',
        'nama',
        'point',
        'sanction',
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

    public function pelanggaranSiswa(): HasMany
    {
        return $this->hasMany(PelanggaranSiswa::class, 'jenis_pelanggaran_id');
    }

    public function pelanggaranGuru(): HasMany
    {
        return $this->hasMany(PelanggaranGuru::class, 'jenis_pelanggaran_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('level')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', PelanggaranLevel::normalize($level));
    }

    public function levelLabel(): string
    {
        return PelanggaranLevel::label($this->level);
    }

    public function sanctionLabel(): ?string
    {
        return $this->sanction === null
            ? null
            : PelanggaranSanction::label($this->sanction);
    }
}
