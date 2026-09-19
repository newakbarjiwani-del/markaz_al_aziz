<?php

namespace App\Models;

use App\Support\AttendanceStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiGuru extends BaseModel
{
    public const STATUS_HADIR = AttendanceStatus::HADIR;

    public const STATUS_TERLAMBAT = AttendanceStatus::TERLAMBAT;

    public const STATUS_IZIN = AttendanceStatus::IZIN;

    public const STATUS_SAKIT = AttendanceStatus::SAKIT;

    public const STATUS_CUTI = AttendanceStatus::CUTI;

    public const STATUS_ALPHA = AttendanceStatus::ALPHA;

    public const STATUS_PULANG_TEPAT = 'tepat';

    public const STATUS_PULANG_AWAL = 'pulang_awal';

    protected $table = 'absensi_guru';

    protected $fillable = [
        'sekolah_id',
        'guru_id',
        'jadwal_absensi_guru_id',
        'date',
        'status',
        'status_pulang',
        'method',
        'method_keluar',
        'jam_masuk',
        'jam_keluar',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function jadwalAbsensiGuru(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsensiGuru::class, 'jadwal_absensi_guru_id');
    }

    public function isAbsentOnly(): bool
    {
        return AttendanceStatus::isAbsent($this->status);
    }

    public function hasCheckedIn(): bool
    {
        return filled($this->jam_masuk);
    }

    public function hasCheckedOut(): bool
    {
        return filled($this->jam_keluar);
    }

    public function isDayComplete(): bool
    {
        return $this->isAbsentOnly() || $this->hasCheckedOut();
    }
}
