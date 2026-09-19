<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KurikulumMapel extends BaseModel
{
    protected $table = 'kurikulum_mapel';

    protected $fillable = [
        'kurikulum_id',
        'mata_pelajaran_id',
        'tingkat',
        'jam_mingguan',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'jam_mingguan' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function kompetensiDasar(): HasMany
    {
        return $this->hasMany(KompetensiDasar::class, 'kurikulum_mapel_id')->orderBy('sort_order');
    }
}
