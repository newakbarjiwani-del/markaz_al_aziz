<?php

namespace App\Models;

use Database\Factories\QrisPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrisPayment extends BaseModel
{
    /** @use HasFactory<QrisPaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'qris_payments';

    protected $fillable = [
        'siswa_id',
        'sekolah_id',
        'vano',
        'amount',
        'qris_id',
        'transaction_id',
        'lazismu_transaction_id',
        'account_no',
        'mitra_customer_id',
        'raw_qr_data',
        'merchant_id',
        'merchant_pan',
        'expired_at',
        'status',
        'paid_flag',
        'paid_at',
        'pembayaran_id',
        'request_payload',
        'response_payload',
        'push_payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expired_at' => 'datetime',
            'paid_flag' => 'boolean',
            'paid_at' => 'datetime',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'push_payload' => 'array',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QrisPaymentItem::class, 'qris_payment_id');
    }

    public function tagihans(): BelongsToMany
    {
        return $this->belongsToMany(Tagihan::class, 'qris_payment_tagihan', 'qris_payment_id', 'tagihan_id')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->paid_flag;
    }

    public function isPaid(): bool
    {
        return $this->paid_flag || $this->status === self::STATUS_PAID;
    }
}
