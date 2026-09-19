<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPembayaranBatal extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_pembayaran_batal';

    protected $fillable = [
        'pembayaran_id',
        'siswa_id',
        'cancelled_by',
        'original_user_id',
        'reference',
        'method',
        'total_amount',
        'paid_at',
        'items',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'items' => 'array',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function originalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_user_id');
    }
}
