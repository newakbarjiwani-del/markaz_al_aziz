<?php

namespace App\Models;

use App\Support\KelasLabel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends BaseModel
{
    protected $table = 'kelas';

    protected $fillable = [
        'sekolah_id',
        'name',
        'kelas',
        'kelompok',
        'unit',
        'jenjang',
        'wali_kelas',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Kelas $model) {
            $kelas = trim((string) ($model->kelas ?? ''));

            if ($kelas === '') {
                return;
            }

            $display = KelasLabel::displayName($kelas, $model->kelompok);

            if ($display !== '') {
                $model->name = $display;
            }
        });
    }

    public function displayLabel(): string
    {
        return KelasLabel::long($this->kelas, $this->kelompok) ?: $this->name;
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }
}
