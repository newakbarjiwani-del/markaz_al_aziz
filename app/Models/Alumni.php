<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumni extends BaseModel
{
    protected $table = 'alumni';

    protected $fillable = [
        'sekolah_id',
        'siswa_id',
        'name',
        'nis',
        'angkatan',
        'phone',
        'email',
        'address',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tracers(): HasMany
    {
        return $this->hasMany(AlumniTracer::class, 'alumni_id')->orderByDesc('tahun_tracer');
    }
}
