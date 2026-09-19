<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmTopup extends Model
{
    public const TOPUPNO_CASHLESS = 'CASHLESS';

    protected $table = 'sm_topup';

    protected $fillable = [
        'urut',
        'CUSTID',
        'user_id',
        'NOMINAL',
        'TOPUPNO',
        'TRXDATE',
    ];

    protected $attributes = [
        'TOPUPNO' => self::TOPUPNO_CASHLESS,
    ];

    protected function casts(): array
    {
        return [
            'urut' => 'integer',
            'CUSTID' => 'integer',
            'NOMINAL' => 'integer',
            'TRXDATE' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $topup): void {
            if (blank($topup->TOPUPNO)) {
                $topup->TOPUPNO = self::TOPUPNO_CASHLESS;
            }
        });

        static::created(function (self $topup): void {
            if ($topup->urut === null) {
                $topup->forceFill(['urut' => $topup->id])->saveQuietly();
            }
        });
    }
}
