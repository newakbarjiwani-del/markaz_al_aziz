<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAkademik extends BaseModel
{
    protected $table = 'tahun_akademik';

    protected $fillable = ['sekolah_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function riwayatAkademik(): HasMany
    {
        return $this->hasMany(RiwayatAkademik::class, 'tahun_akademik_id');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'tahun_akademik_id');
    }
}
