<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalPelajaran extends BaseModel
{
    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'sekolah_id',
        'tahun_akademik_id',
        'kelas_id',
        'name',
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

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(JadwalPelajaranSlot::class, 'jadwal_pelajaran_id')
            ->orderBy('day_of_week')
            ->orderBy('time_start')
            ->orderBy('sort_order');
    }
}
