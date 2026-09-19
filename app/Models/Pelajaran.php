<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pelajaran extends BaseModel
{
    protected $table = 'pelajaran';

    protected $fillable = [
        'sekolah_id', 'code', 'name', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function coversAllSchools(): bool
    {
        return $this->sekolah_id === null;
    }
}
