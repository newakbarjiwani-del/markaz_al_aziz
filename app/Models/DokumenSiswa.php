<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenSiswa extends BaseModel
{
    protected $table = 'dokumen_siswa';

    protected $fillable = ['siswa_id', 'title', 'file_path', 'file_type'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
