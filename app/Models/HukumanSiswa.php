<?php

namespace App\Models;

use App\Support\HukumanStatus;
use App\Support\PelanggaranSanction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HukumanSiswa extends BaseModel
{
    protected $table = 'hukuman_siswa';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'total_point',
        'recommended_sanction',
        'sanction',
        'status',
        'keterangan',
        'tanggal',
        'processed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_point' => 'integer',
            'status' => 'integer',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function pelanggaranSiswa(): BelongsToMany
    {
        return $this->belongsToMany(
            PelanggaranSiswa::class,
            'hukuman_pelanggaran',
            'hukuman_siswa_id',
            'pelanggaran_siswa_id'
        )->withTimestamps();
    }

    public function buktiCatatan(): MorphMany
    {
        return $this->morphMany(BuktiCatatan::class, 'buktiable');
    }

    public function statusLabel(): string
    {
        return HukumanStatus::label($this->status);
    }

    public function sanctionLabel(): string
    {
        if ($this->sanction === null || trim($this->sanction) === '') {
            return '-';
        }

        $normalized = PelanggaranSanction::normalize($this->sanction);

        if ($normalized !== null) {
            return PelanggaranSanction::labels()[$normalized] ?? $this->sanction;
        }

        return $this->sanction;
    }

    public function recommendedSanctionLabel(): string
    {
        return PelanggaranSanction::label($this->recommended_sanction);
    }
}
