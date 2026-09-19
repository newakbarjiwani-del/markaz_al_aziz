<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sccttran extends Model
{
    protected $table = 'sccttran';

    protected $fillable = [
        'urut',
        'CUSTID',
        'user_id',
        'METODE',
        'TRXDATE',
        'NOREFF',
        'FIDBANK',
        'KDCHANNEL',
        'DEBET',
        'KREDIT',
        'REFFBANK',
        'TRANSNO',
    ];

    protected function casts(): array
    {
        return [
            'urut' => 'integer',
            'CUSTID' => 'integer',
            'TRXDATE' => 'datetime',
            'DEBET' => 'integer',
            'KREDIT' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'CUSTID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $transaction): void {
            if ($transaction->urut === null) {
                $transaction->forceFill(['urut' => $transaction->id])->saveQuietly();
            }
        });
    }
}
