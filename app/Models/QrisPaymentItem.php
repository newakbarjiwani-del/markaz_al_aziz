<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisPaymentItem extends BaseModel
{
    protected $table = 'qris_payment_tagihan';

    protected $fillable = [
        'qris_payment_id',
        'tagihan_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function qrisPayment(): BelongsTo
    {
        return $this->belongsTo(QrisPayment::class, 'qris_payment_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }
}
