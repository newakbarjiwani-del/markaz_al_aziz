<?php

namespace App\Models;

use App\Support\TahfidzRekapStatus;
use Database\Factories\TahfidzRekapFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahfidzRekap extends BaseModel
{
    /** @use HasFactory<TahfidzRekapFactory> */
    use HasFactory;

    protected $table = 'tahfidz_rekap';

    protected $fillable = [
        'program_id',
        'sekolah_id',
        'starts_on',
        'ends_on',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TahfidzProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(TahfidzProgram::class, 'program_id');
    }

    /**
     * @return BelongsTo<Sekolah, $this>
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TahfidzRekapSiswa, $this>
     */
    public function baris(): HasMany
    {
        return $this->hasMany(TahfidzRekapSiswa::class, 'rekap_id');
    }

    public function periodLabel(): string
    {
        $start = $this->starts_on;
        $end = $this->ends_on;

        if ($start === null || $end === null) {
            return '-';
        }

        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $start->day.'- '.$end->translatedFormat('j F Y');
        }

        return $start->translatedFormat('j F Y').' - '.$end->translatedFormat('j F Y');
    }

    public function statusLabel(): string
    {
        return TahfidzRekapStatus::label($this->status);
    }
}
