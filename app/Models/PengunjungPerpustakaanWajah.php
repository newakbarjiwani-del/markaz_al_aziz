<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengunjungPerpustakaanWajah extends Model
{
    protected $table = 'pengunjung_perpustakaan_wajah';

    protected $fillable = [
        'pengunjung_perpustakaan_id',
        'foto_wajah',
    ];

    protected $hidden = [
        'foto_wajah',
    ];

    public function pengunjungPerpustakaan(): BelongsTo
    {
        return $this->belongsTo(PengunjungPerpustakaan::class);
    }
}
