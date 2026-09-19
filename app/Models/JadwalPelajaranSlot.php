<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPelajaranSlot extends BaseModel
{
    protected $table = 'jadwal_pelajaran_slot';

    protected $fillable = [
        'jadwal_pelajaran_id',
        'day_of_week',
        'time_start',
        'time_end',
        'mata_pelajaran_id',
        'guru_id',
        'ruang',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }

    public function dayLabel(): string
    {
        return self::dayLabels()[$this->day_of_week] ?? (string) $this->day_of_week;
    }
}
