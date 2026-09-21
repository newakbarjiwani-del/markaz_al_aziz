<?php

namespace App\Models;

use App\Support\TahfidzHari;
use Database\Factories\TahfidzJadwalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfidzJadwal extends Model
{
    /** @use HasFactory<TahfidzJadwalFactory> */
    use HasFactory;

    protected $table = 'tahfidz_jadwal';

    protected $fillable = [
        'halaqoh_id',
        'day_of_week',
        'time_start',
        'time_end',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TahfidzHalaqoh, $this>
     */
    public function halaqoh(): BelongsTo
    {
        return $this->belongsTo(TahfidzHalaqoh::class, 'halaqoh_id');
    }

    public function dayLabel(): string
    {
        return TahfidzHari::label((int) $this->day_of_week);
    }

    public function timeLabel(): string
    {
        $start = substr((string) $this->time_start, 0, 5);
        $end = substr((string) $this->time_end, 0, 5);

        return $start.'–'.$end;
    }
}
