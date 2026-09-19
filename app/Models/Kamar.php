<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kamar extends BaseModel
{
    protected $table = 'kamar';

    protected $fillable = [
        'kode',
        'nama',
        'blok',
        'kapasitas',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kamar_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('nama');
    }

    public function displayLabel(): string
    {
        $parts = array_filter([$this->blok, $this->nama]);

        return implode(' · ', $parts) ?: $this->nama;
    }
}
