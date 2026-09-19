<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataPelajaran extends BaseModel
{
    protected $table = 'mata_pelajaran';

    protected $fillable = [
        'sekolah_id',
        'code',
        'name',
        'kelompok',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function kurikulumMapel(): HasMany
    {
        return $this->hasMany(KurikulumMapel::class, 'mata_pelajaran_id');
    }
}
