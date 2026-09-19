<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalAbsensiGuru extends BaseModel
{
    protected $table = 'jadwal_absensi_guru';

    protected $fillable = [
        'sekolah_id',
        'name',
        'jam_masuk',
        'jam_pulang',
        'toleransi_menit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'toleransi_menit' => 'integer',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function gurus(): HasMany
    {
        return $this->hasMany(Guru::class, 'jadwal_absensi_guru_id');
    }

    public function jamMasukInput(): string
    {
        return substr((string) $this->jam_masuk, 0, 5);
    }

    public function jamPulangInput(): string
    {
        return substr((string) $this->jam_pulang, 0, 5);
    }
}
