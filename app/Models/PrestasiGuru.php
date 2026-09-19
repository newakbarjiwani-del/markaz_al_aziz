<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PrestasiGuru extends BaseModel
{
    protected $table = 'prestasi_guru';

    protected $fillable = [
        'guru_id',
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

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
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
