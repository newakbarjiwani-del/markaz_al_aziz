<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpmbPendaftar extends BaseModel
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'spmb_pendaftar';

    protected $fillable = [
        'spmb_periode_id',
        'sekolah_id',
        'nomor_pendaftaran',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'parent_name',
        'parent_phone',
        'status',
        'siswa_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(SpmbPeriode::class, 'spmb_periode_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_VERIFIED => 'Diverifikasi',
            self::STATUS_ACCEPTED => 'Diterima',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
