<?php

namespace App\Models;

use App\Support\RfidUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Guru extends BaseModel
{
    protected $table = 'guru';

    protected $fillable = [
        'sekolah_id', 'jadwal_absensi_guru_id', 'nip', 'name', 'jabatan', 'jenis_guru', 'golongan', 'phone', 'status',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Guru $guru): void {
            if ($guru->trashed()) {
                $guru->rfid()->delete();
            }
        });
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function jadwalAbsensiGuru(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsensiGuru::class, 'jadwal_absensi_guru_id');
    }

    public function profil(): HasOne
    {
        return $this->hasOne(ProfilGuru::class, 'guru_id');
    }

    public function riwayatMengajar(): HasMany
    {
        return $this->hasMany(RiwayatMengajar::class, 'guru_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiGuru::class, 'guru_id');
    }

    public function portalUser(): HasOne
    {
        return $this->hasOne(User::class, 'guru_id');
    }

    public function kartu(): HasMany
    {
        return $this->hasMany(KartuGuru::class, 'guru_id');
    }

    public function kartuAktif(): HasOne
    {
        return $this->hasOne(KartuGuru::class, 'guru_id')->where('status', 'aktif');
    }

    public function rfid(): HasOne
    {
        return $this->hasOne(Rfid::class, 'guru_id');
    }

    public function rfidUid(): ?string
    {
        return $this->rfid?->uid;
    }

    public function isRfidBlocked(): bool
    {
        return false;
    }

    public function hasRfid(): bool
    {
        return $this->rfid !== null;
    }

    public function assignRfid(?string $uid): void
    {
        DB::transaction(function () use ($uid): void {
            $uid = RfidUid::sanitize($uid);

            if ($uid === null) {
                $this->rfid()->delete();
            } else {
                $this->rfid()->updateOrCreate(
                    [],
                    ['uid' => $uid, 'siswa_id' => null, 'blocked' => false]
                );
            }

            $this->unsetRelation('rfid');
        });
    }
}
