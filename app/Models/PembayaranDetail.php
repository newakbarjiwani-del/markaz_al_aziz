<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranDetail extends Model
{
    protected $table = 'pembayaran_detail';

    protected $fillable = [
        'pembayaran_id',
        'tagihan_id',
        'amount',
        'sccttran_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function sccttran(): BelongsTo
    {
        return $this->belongsTo(Sccttran::class, 'sccttran_id');
    }
}
