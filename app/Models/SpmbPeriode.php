<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpmbPeriode extends BaseModel
{
    protected $table = 'spmb_periode';

    protected $fillable = [
        'name',
        'sekolah_id',
        'tahun_akademik_id',
        'opens_at',
        'closes_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(SpmbPendaftar::class, 'spmb_periode_id');
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }

        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        return true;
    }

    public static function currentOpen(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->orderByDesc('opens_at')
            ->get()
            ->first(fn (self $periode) => $periode->isOpen());
    }
}
