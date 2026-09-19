<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisTagihan extends BaseModel
{
    protected $table = 'jenis_tagihan';

    protected $fillable = [
        'sekolah_id',
        'name',
        'code',
        'description',
        'default_amount',
        'is_spp',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_spp' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'jenis_tagihan_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
