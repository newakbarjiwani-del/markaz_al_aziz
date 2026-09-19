<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenarikanPendapatanKantin extends BaseModel
{
    public const METHOD_CASH = 'CASH';

    protected $table = 'penarikan_pendapatan_kantin';

    protected $fillable = [
        'kantin_user_id',
        'sekolah_id',
        'amount',
        'method',
        'noreff',
        'description',
        'settled_by',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    public function kantinUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kantin_user_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function settledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
