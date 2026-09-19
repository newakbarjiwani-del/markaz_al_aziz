<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Kurikulum extends BaseModel
{
    protected $table = 'kurikulum';

    protected $fillable = [
        'sekolah_id',
        'tahun_akademik_id',
        'name',
        'jenjang',
        'is_active',
        'description',
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

    public function mapel(): HasMany
    {
        return $this->hasMany(KurikulumMapel::class, 'kurikulum_id')->orderBy('sort_order');
    }

    public function kompetensiDasar(): HasManyThrough
    {
        return $this->hasManyThrough(KompetensiDasar::class, KurikulumMapel::class);
    }
}
