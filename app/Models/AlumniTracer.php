<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniTracer extends BaseModel
{
    public const SOURCE_ADMIN = 'admin';

    public const SOURCE_PUBLIC = 'public';

    protected $table = 'alumni_tracer';

    protected $fillable = [
        'alumni_id',
        'tahun_tracer',
        'status_lulusan',
        'institusi',
        'jabatan',
        'bidang',
        'kota',
        'catatan',
        'submitted_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}
