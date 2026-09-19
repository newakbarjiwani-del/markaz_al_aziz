<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanCashless extends BaseModel
{
    protected $table = 'pengaturan_cashless';

    protected $fillable = [
        'sekolah_id',
        'daily_transaction_limit',
        'min_topup',
        'allow_transfer',
    ];

    protected function casts(): array
    {
        return [
            'daily_transaction_limit' => 'decimal:2',
            'min_topup' => 'decimal:2',
            'allow_transfer' => 'boolean',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }
}
