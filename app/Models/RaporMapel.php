<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaporMapel extends BaseModel
{
    protected $table = 'rapor_mapel';

    protected $fillable = [
        'rapor_id',
        'mata_pelajaran_id',
        'nilai_akhir',
        'predikat',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return ['nilai_akhir' => 'decimal:2'];
    }

    public function rapor(): BelongsTo
    {
        return $this->belongsTo(Rapor::class, 'rapor_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }
}
