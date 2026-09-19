<?php

namespace App\Models;

use App\Support\RfidUid;
use App\Support\SiswaStatus;
use App\Support\VirtualAccountNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Siswa extends BaseModel
{
    public const STATUS_INACTIVE = SiswaStatus::INACTIVE;

    public const STATUS_ACTIVE = SiswaStatus::ACTIVE;

    public const STATUS_PENDING = SiswaStatus::PENDING;

    protected $table = 'siswa';

    protected $fillable = [
        'sekolah_id', 'kelas_id', 'kamar_id', 'status_santri_id', 'nis', 'nis_key', 'nomor_pendaftaran', 'name', 'gender',
        'birth_date', 'birth_place', 'address', 'status', 'has_foto_wajah',
        'daily_transaction_limit',
    ];

    protected $hidden = [
        'cashless_pin',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'status' => 'integer',
            'has_foto_wajah' => 'boolean',
            'daily_transaction_limit' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Siswa $siswa): void {
            $siswa->nis_key = $siswa->deleted_at !== null
                ? null
                : $siswa->nis;
        });

        static::deleted(function (Siswa $siswa): void {
            if (! $siswa->trashed()) {
                return;
            }

            DB::table('siswa')
                ->where('id', $siswa->id)
                ->update(['nis_key' => null]);

            $siswa->rfid()->delete();
        });

        static::restoring(function (Siswa $siswa): void {
            $siswa->nis_key = $siswa->nis;
        });
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class, 'kamar_id');
    }

    public function statusSantri(): BelongsTo
    {
        return $this->belongsTo(StatusSantri::class, 'status_santri_id');
    }

    public function orangTua(): BelongsToMany
    {
        return $this->belongsToMany(OrangTua::class, 'orang_tua_siswa', 'siswa_id', 'orang_tua_id')
            ->withTimestamps();
    }

    public function prestasiSiswa(): HasMany
    {
        return $this->hasMany(PrestasiSiswa::class, 'siswa_id');
    }

    public function pelanggaranSiswa(): HasMany
    {
        return $this->hasMany(PelanggaranSiswa::class, 'siswa_id');
    }

    public function hukumanSiswa(): HasMany
    {
        return $this->hasMany(HukumanSiswa::class, 'siswa_id');
    }

    public function profil(): HasOne
    {
        return $this->hasOne(ProfilSiswa::class, 'siswa_id');
    }

    public function dompet(): HasOne
    {
        return $this->hasOne(Dompet::class, 'siswa_id');
    }

    public function wajah(): HasOne
    {
        return $this->hasOne(SiswaWajah::class, 'siswa_id');
    }

    public function rfid(): HasOne
    {
        return $this->hasOne(Rfid::class, 'siswa_id');
    }

    public function saldoKeuangan(): HasOne
    {
        return $this->hasOne(SaldoKeuangan::class, 'siswa_id');
    }

    public function sccttran(): HasMany
    {
        return $this->hasMany(Sccttran::class, 'CUSTID');
    }

    public function kartu(): HasMany
    {
        return $this->hasMany(KartuSiswa::class, 'siswa_id');
    }

    public function kartuAktif(): HasOne
    {
        return $this->hasOne(KartuSiswa::class, 'siswa_id')->where('status', 'aktif');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'siswa_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiSiswa::class, 'siswa_id');
    }

    /** Transactions for this student via the peminjaman parent. */
    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'siswa_id');
    }

    /** Book items borrowed by this student (through peminjaman parent). */
    public function peminjamanBuku(): HasManyThrough
    {
        return $this->hasManyThrough(
            PeminjamanBuku::class,
            Peminjaman::class,
            'siswa_id',        // FK on peminjaman pointing to siswa
            'peminjaman_id',   // FK on peminjaman_buku pointing to peminjaman
            'id',              // local key on siswa
            'id'               // local key on peminjaman
        );
    }

    public function transaksiCashless(): HasMany
    {
        return $this->hasMany(TransaksiCashless::class, 'siswa_id');
    }

    public function sccttranCashless(): HasMany
    {
        return $this->hasMany(SccttranCashless::class, 'CUSTID');
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function canTransact(): bool
    {
        return SiswaStatus::canTransact($this->status);
    }

    public function statusLabel(): string
    {
        return SiswaStatus::label($this->status);
    }

    /**
     * Block finance/cashless mutations when student is not active.
     *
     * @throws ValidationException
     */
    public function assertCanTransact(string $field = 'siswa_id'): void
    {
        if ($this->canTransact()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'Siswa tidak aktif. Transaksi tidak diizinkan.',
        ]);
    }

    public function canUseCashlessRfid(): bool
    {
        return $this->canTransact() && $this->hasRfid() && ! $this->isRfidBlocked();
    }

    public function rfidUid(): ?string
    {
        return $this->rfid?->uid;
    }

    public function isRfidBlocked(): bool
    {
        return (bool) $this->rfid?->blocked;
    }

    public function hasRfid(): bool
    {
        return $this->rfid !== null;
    }

    public function assignRfid(?string $uid, bool $blocked = false): void
    {
        DB::transaction(function () use ($uid, $blocked): void {
            $uid = RfidUid::sanitize($uid);

            if ($uid === null) {
                $this->rfid()->delete();
            } else {
                $this->rfid()->updateOrCreate(
                    [],
                    ['uid' => $uid, 'guru_id' => null, 'blocked' => $blocked]
                );
            }

            $this->unsetRelation('rfid');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable().'.status', self::STATUS_ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable().'.status', self::STATUS_INACTIVE);
    }

    public function virtualAccountNumber(): ?string
    {
        return VirtualAccountNumber::fromNis($this->nis);
    }

    public function hasFotoWajah(): bool
    {
        return (bool) $this->has_foto_wajah;
    }

    public function fotoWajahDataUrl(): string
    {
        $foto = trim((string) ($this->wajah?->foto_wajah ?? ''));
        if ($foto === '') {
            return '';
        }

        if (str_starts_with($foto, 'data:')) {
            return $foto;
        }

        if (str_starts_with($foto, '/9j/')) {
            return 'data:image/jpeg;base64,'.$foto;
        }

        if (str_starts_with($foto, 'iVBOR')) {
            return 'data:image/png;base64,'.$foto;
        }

        return $foto;
    }
}
