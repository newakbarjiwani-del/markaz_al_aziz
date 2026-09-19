<?php

namespace App\Models;

class SpmbPengumuman extends BaseModel
{
    protected $table = 'spmb_pengumuman';

    protected $fillable = [
        'title',
        'body',
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

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
