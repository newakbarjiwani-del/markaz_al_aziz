<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrangTua extends BaseModel
{
    protected $table = 'orang_tua';

    protected $fillable = [
        'sekolah_id',
        'nama_ayah',
        'telepon_ayah',
        'email_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'telepon_ibu',
        'email_ibu',
        'pekerjaan_ibu',
        'nama_wali',
        'telepon_wali',
        'email_wali',
        'pekerjaan_wali',
        'alamat',
        'status',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'orang_tua_siswa', 'orang_tua_id', 'siswa_id')
            ->withTimestamps();
    }

    public function displayName(): string
    {
        $names = array_values(array_filter([
            $this->nama_ayah,
            $this->nama_ibu,
            $this->nama_wali,
        ]));

        return $names !== [] ? implode(' · ', $names) : 'Orang Tua';
    }

    public function primaryPhone(): ?string
    {
        return $this->telepon_ayah ?? $this->telepon_ibu ?? $this->telepon_wali;
    }

    public function primaryEmail(): ?string
    {
        return $this->email_ayah ?? $this->email_ibu ?? $this->email_wali;
    }
}
