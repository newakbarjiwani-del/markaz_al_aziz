<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotonganPemakaian extends Model
{
    protected $table = 'potongan_pemakaian';

    protected $fillable = [
        'potongan_siswa_id',
        'tagihan_id',
        'amount_bruto',
        'potongan_amount',
        'amount_net',
        'urutan',
        'applied_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_bruto' => 'decimal:2',
            'potongan_amount' => 'decimal:2',
            'amount_net' => 'decimal:2',
            'urutan' => 'integer',
            'applied_at' => 'datetime',
        ];
    }

    public function potonganSiswa(): BelongsTo
    {
        return $this->belongsTo(PotonganSiswa::class, 'potongan_siswa_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
