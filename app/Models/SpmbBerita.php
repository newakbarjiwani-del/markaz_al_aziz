<?php

namespace App\Models;

class SpmbBerita extends BaseModel
{
    protected $table = 'spmb_berita';

    protected $fillable = [
        'title',
        'slug',
        'body',
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

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
