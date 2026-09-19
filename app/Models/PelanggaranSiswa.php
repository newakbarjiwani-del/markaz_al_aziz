<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PelanggaranSiswa extends BaseModel
{
    protected $table = 'pelanggaran_siswa';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'jenis_pelanggaran_id',
        'judul',
        'keterangan',
        'tanggal',
        'point',
        'is_punished',
        'point_asli',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'point' => 'integer',
            'point_asli' => 'integer',
            'is_punished' => 'boolean',
        ];
    }

    public function scopeActivePoints(Builder $query): Builder
    {
        return $query->where('is_punished', false);
    }

    public function markAsPunished(): void
    {
        if ($this->is_punished) {
            return;
        }

        $this->update([
            'point_asli' => $this->point,
            'point' => 0,
            'is_punished' => true,
        ]);
    }

    public function restoreFromPunishment(): void
    {
        if (! $this->is_punished) {
            return;
        }

        $this->update([
            'point' => $this->point_asli ?? 0,
            'is_punished' => false,
            'point_asli' => null,
        ]);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
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
