<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PelanggaranGuru extends BaseModel
{
    protected $table = 'pelanggaran_guru';

    protected $fillable = [
        'guru_id',
        'sekolah_id',
        'jenis_pelanggaran_id',
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

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function jenisPelanggaran(): BelongsTo
    {
        return $this->belongsTo(JenisPelanggaran::class, 'jenis_pelanggaran_id');
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
