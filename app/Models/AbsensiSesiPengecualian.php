<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSesiPengecualian extends BaseModel
{
    protected $table = 'absensi_sesi_pengecualian';

    protected $fillable = [
        'jadwal_absen_slot_id',
        'date',
        'reason',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function jadwalSlot(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsenSlot::class, 'jadwal_absen_slot_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
