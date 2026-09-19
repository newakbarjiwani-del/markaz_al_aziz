<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class BuktiCatatan extends BaseModel
{
    protected $table = 'bukti_catatan';

    protected $fillable = [
        'buktiable_type',
        'buktiable_id',
        'file_path',
        'file_type',
        'original_name',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function buktiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function isImage(): bool
    {
        return in_array(strtolower($this->file_type), ['jpeg', 'jpg', 'png', 'webp'], true);
    }

    public function isPdf(): bool
    {
        return strtolower($this->file_type) === 'pdf';
    }
}
