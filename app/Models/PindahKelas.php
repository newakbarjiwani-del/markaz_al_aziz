<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PindahKelas extends BaseModel
{
    protected $table = 'pindah_kelas';

    protected $fillable = ['siswa_id', 'dari_kelas_id', 'ke_kelas_id', 'status', 'notes'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function dariKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'dari_kelas_id');
    }

    public function keKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'ke_kelas_id');
    }
}
