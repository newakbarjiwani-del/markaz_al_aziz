<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends BaseModel
{
    protected $table = 'absensi_siswa';

    protected $fillable = ['sekolah_id', 'siswa_id', 'jadwal_absen_slot_id', 'date', 'status', 'method', 'time_in', 'notes'];

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

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function jadwalSlot(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsenSlot::class, 'jadwal_absen_slot_id')->withTrashed();
    }
}
