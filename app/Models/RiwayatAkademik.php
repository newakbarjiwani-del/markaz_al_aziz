<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatAkademik extends BaseModel
{
    protected $table = 'riwayat_akademik';

    protected $fillable = ['siswa_id', 'tahun_akademik_id', 'class_name', 'gpa'];

    protected function casts(): array
    {
        return ['gpa' => 'decimal:2'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }
}
