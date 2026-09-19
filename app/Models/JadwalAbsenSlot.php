<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalAbsenSlot extends BaseModel
{
    protected $table = 'jadwal_absen_slot';

    protected $fillable = [
        'jadwal_absen_hari_id', 'pelajaran_id', 'guru_id',
        'time_start', 'time_end', 'tolerance_minutes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tolerance_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function hari(): BelongsTo
    {
        return $this->belongsTo(JadwalAbsenHari::class, 'jadwal_absen_hari_id')->withTrashed();
    }

    public function pelajaran(): BelongsTo
    {
        return $this->belongsTo(Pelajaran::class, 'pelajaran_id')->withTrashed();
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id')->withTrashed();
    }

    public function isWithinWindow(?\Illuminate\Support\Carbon $at = null): bool
    {
        $at = $at ?? now();
        $date = $at->toDateString();
        $start = \Illuminate\Support\Carbon::parse($date.' '.$this->time_start);
        $end = \Illuminate\Support\Carbon::parse($date.' '.$this->time_end);

        return $at->between($start, $end);
    }
}
