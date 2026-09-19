<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master catalog of potongan (bill deduction) types.
 */
class JenisPotongan extends BaseModel
{
    protected $table = 'jenis_potongan';

    protected $fillable = [
        'sekolah_id',
        'kode',
        'nama',
        'tipe_default',
        'nilai_default',
        'keterangan',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nilai_default' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function potonganSiswa(): HasMany
    {
        return $this->hasMany(PotonganSiswa::class, 'jenis_potongan_id');
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
