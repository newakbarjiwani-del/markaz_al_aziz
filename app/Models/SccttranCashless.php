<?php

namespace App\Models;

use App\Models\Scopes\OperatorSekolahRelationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SccttranCashless extends Model
{
    protected $table = 'sccttran_cashless';

    protected $fillable = [
        'urut',
        'CUSTID',
        'user_id',
        'METODE',
        'wallet',
        'TRXDATE',
        'NOREFF',
        'FIDBANK',
        'KDCHANNEL',
        'DEBET',
        'KREDIT',
        'REFFBANK',
        'TRANSNO',
        'description',
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

    protected static function booted(): void
    {
        static::created(function (self $transaction): void {
            if ($transaction->urut === null) {
                $transaction->forceFill(['urut' => $transaction->id])->saveQuietly();
            }
        });
    }

    /**
     * Route bindings ignore the school relation scope so portal detail views
     * can resolve the row and enforce their own explicit authorization (403)
     * instead of a silent 404 raised during binding.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->newQueryWithoutScope(OperatorSekolahRelationScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'CUSTID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
