<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalAbsenHari extends BaseModel
{
    protected $table = 'jadwal_absen_hari';

    protected $fillable = [
        'jadwal_absen_id', 'day_of_week', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function jadwalAbsen(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsen::class, 'jadwal_absen_id')->withoutGlobalScopes()->withTrashed();
    }

    public function slots(): HasMany
    {
        return $this->hasMany(JadwalAbsenSlot::class, 'jadwal_absen_hari_id')->orderBy('sort_order');
    }
}
