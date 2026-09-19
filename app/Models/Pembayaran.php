<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Pembayaran extends BaseModel
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'user_id',
        'siswa_id',
        'method',
        'reference',
        'total_amount',
        'paid_dt',
        'paid_dt_actual',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_dt' => 'datetime',
            'paid_dt_actual' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PembayaranDetail::class, 'pembayaran_id');
    }

    public function tagihan(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tagihan::class,
            PembayaranDetail::class,
            'pembayaran_id',
            'id',
            'id',
            'tagihan_id',
        );
    }
}
