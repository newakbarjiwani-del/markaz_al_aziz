<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UlasanBuku extends BaseModel
{
    protected $table = 'ulasan_buku';

    protected $fillable = ['buku_id', 'siswa_id', 'rating', 'review'];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
