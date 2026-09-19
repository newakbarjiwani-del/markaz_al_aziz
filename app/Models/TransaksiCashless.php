<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiCashless extends BaseModel
{
    protected $table = 'transaksi_cashless';

    protected $fillable = [
        'siswa_id', 'type', 'category', 'amount', 'wallet',
        'from_wallet', 'to_wallet', 'description',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
