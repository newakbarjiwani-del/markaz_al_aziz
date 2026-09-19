<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengunjungPerpustakaan extends BaseModel
{
    public const TYPE_SISWA = 'siswa';

    public const TYPE_GURU = 'guru';

    public const TYPE_KARYAWAN = 'karyawan';

    public const TYPE_NON_SISWA = 'non_siswa';

    public const METHOD_MANUAL = 'manual';

    public const METHOD_FACE = 'face';

    public const METHOD_RFID = 'rfid';

    /** @return list<string> */
    public static function visitorTypes(): array
    {
        return [
            self::TYPE_SISWA,
            self::TYPE_GURU,
            self::TYPE_KARYAWAN,
            self::TYPE_NON_SISWA,
        ];
    }

    /** @return list<string> */
    public static function methods(): array
    {
        return [
            self::METHOD_MANUAL,
            self::METHOD_FACE,
            self::METHOD_RFID,
        ];
    }

    protected $table = 'pengunjung_perpustakaan';

    protected $fillable = [
        'sekolah_id',
        'visitor_type',
        'siswa_id',
        'guru_id',
        'nama',
        'nis',
        'asal',
        'telepon',
        'has_foto_wajah',
        'method',
        'rfid_uid',
        'visited_at',
        'catatan',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'has_foto_wajah' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function wajah(): HasOne
    {
        return $this->hasOne(PengunjungPerpustakaanWajah::class, 'pengunjung_perpustakaan_id');
    }

    public function hasFotoWajah(): bool
    {
        return (bool) $this->has_foto_wajah;
    }

    public function fotoWajahDataUrl(): string
    {
        return (string) ($this->wajah?->foto_wajah ?? '');
    }

    public function visitorTypeLabel(): string
    {
        return match ($this->visitor_type) {
            self::TYPE_SISWA => 'Siswa',
            self::TYPE_GURU => 'Guru',
            self::TYPE_KARYAWAN => 'Karyawan',
            self::TYPE_NON_SISWA => 'Pengunjung Luar',
            default => ucfirst(str_replace('_', ' ', (string) $this->visitor_type)),
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            self::METHOD_FACE => 'Wajah',
            self::METHOD_MANUAL => 'Manual',
            self::METHOD_RFID => 'RFID',
            default => ucfirst((string) $this->method),
        };
    }

    public function identitasLabel(): string
    {
        return $this->nis ?? '-';
    }

    public function asalLabel(): string
    {
        if ($this->visitor_type === self::TYPE_SISWA) {
            return $this->siswa?->kelas?->name ?? $this->asal ?? '-';
        }

        return $this->asal ?? '-';
    }
}
