<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KompetensiDasar extends BaseModel
{
    protected $table = 'kompetensi_dasar';

    protected $fillable = [
        'kurikulum_mapel_id',
        'kode',
        'deskripsi',
        'semester',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function kurikulumMapel(): BelongsTo
    {
        return $this->belongsTo(KurikulumMapel::class, 'kurikulum_mapel_id');
    }
}
