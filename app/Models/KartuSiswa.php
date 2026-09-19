<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuSiswa extends BaseModel
{
    protected $table = 'kartu_siswa';

    protected $fillable = ['siswa_id', 'qr_code', 'status'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public static function provisionFor(Siswa $siswa): self
    {
        return static::firstOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'qr_code' => 'QR-'.$siswa->nis,
                'status' => 'aktif',
            ]
        );
    }
}
