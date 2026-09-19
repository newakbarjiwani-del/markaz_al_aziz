<?php

namespace App\Models;

class SpmbGaleriItem extends BaseModel
{
    protected $table = 'spmb_galeri';

    protected $fillable = [
        'title',
        'caption',
        'image_path',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
