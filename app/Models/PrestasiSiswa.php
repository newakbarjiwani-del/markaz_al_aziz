<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PrestasiSiswa extends BaseModel
{
    protected $table = 'prestasi_siswa';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'jenis_prestasi_id',
        'judul',
        'keterangan',
        'tanggal',
        'point',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'point' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function jenisPrestasi(): BelongsTo
    {
        return $this->belongsTo(JenisPrestasi::class, 'jenis_prestasi_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function buktiCatatan(): MorphMany
    {
        return $this->morphMany(BuktiCatatan::class, 'buktiable');
    }
}
