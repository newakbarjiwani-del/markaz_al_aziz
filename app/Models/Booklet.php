<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booklet extends BaseModel
{
    protected $table = 'booklet';

    protected $fillable = [
        'sekolah_id',
        'title',
        'slug',
        'summary',
        'cover_path',
        'published_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BookletPage::class, 'booklet_id')->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeVisibleForSekolah(Builder $query, ?int $sekolahId): Builder
    {
        if ($sekolahId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($sekolahId): void {
            $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
        });
    }
}
