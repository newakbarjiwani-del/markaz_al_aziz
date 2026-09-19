<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rfid extends Model
{
    protected $table = 'rfid';

    protected $fillable = [
        'uid',
        'siswa_id',
        'guru_id',
        'blocked',
    ];

    protected static function booted(): void
    {
        static::saving(function (Rfid $rfid): void {
            if (($rfid->siswa_id === null) === ($rfid->guru_id === null)) {
                throw new \LogicException('RFID harus memiliki tepat satu pemilik.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function isSiswa(): bool
    {
        return $this->siswa_id !== null;
    }

    public function isGuru(): bool
    {
        return $this->guru_id !== null;
    }

    public function isBlocked(): bool
    {
        return (bool) $this->blocked;
    }
}
