<?php

namespace App\Models;

use App\Support\TagihanPeriode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends BaseModel
{
    public const STATUS_UNPAID = 0;

    public const STATUS_PAID = 1;

    public const STATUS_CICILAN = 2;

    protected $table = 'tagihan';

    protected $fillable = [
        'sekolah_id', 'siswa_id', 'parent_id', 'cicilan_ke', 'tahun_akademik_id', 'jenis_tagihan_id', 'jenis',
        'amount', 'amount_bruto', 'potongan_amount', 'total_amount', 'paid', 'status', 'is_cicilan', 'paid_dt', 'paid_dt_actual', 'due_date', 'periode', 'urutan',
        'sccttran_id', 'reference', 'fidbank', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_bruto' => 'decimal:2',
            'potongan_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid' => 'decimal:2',
            'status' => 'integer',
            'is_cicilan' => 'boolean',
            'paid_dt' => 'datetime',
            'paid_dt_actual' => 'datetime',
            'periode' => 'integer',
            'urutan' => 'integer',
            'cicilan_ke' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function cicilanChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('cicilan_ke');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function jenisTagihan(): BelongsTo
    {
        return $this->belongsTo(JenisTagihan::class, 'jenis_tagihan_id');
    }

    public function pembayaranDetails(): HasMany
    {
        return $this->hasMany(PembayaranDetail::class, 'tagihan_id');
    }

    public function sccttran(): BelongsTo
    {
        return $this->belongsTo(Sccttran::class, 'sccttran_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function potonganPemakaian(): HasMany
    {
        return $this->hasMany(PotonganPemakaian::class, 'tagihan_id')->orderBy('urutan');
    }

    public function displayBruto(): float
    {
        if ($this->amount_bruto !== null) {
            return (float) $this->amount_bruto;
        }

        return $this->displayAmount();
    }

    public function hasPotongan(): bool
    {
        return (float) $this->potongan_amount > 0;
    }

    public function isInstallmentChild(): bool
    {
        return $this->parent_id !== null;
    }

    public function isInstallmentParent(): bool
    {
        return $this->is_cicilan && ! $this->isInstallmentChild();
    }

    public function displayAmount(): float
    {
        if ($this->isInstallmentChild()) {
            return (float) $this->amount;
        }

        if ($this->is_cicilan && $this->total_amount !== null) {
            return (float) $this->total_amount;
        }

        return (float) $this->amount;
    }

    public function remaining(): float
    {
        if ($this->isInstallmentChild()) {
            return 0.0;
        }

        if ($this->is_cicilan) {
            return max(0, (float) $this->amount);
        }

        return max(0, (float) $this->amount - (float) $this->paid);
    }

    public function isPaid(): bool
    {
        return (int) $this->status === self::STATUS_PAID;
    }

    public function isUnpaid(): bool
    {
        return ! $this->isPaid();
    }

    public function isCicilanInProgress(): bool
    {
        return $this->isInstallmentParent()
            && ((float) $this->paid > 0 || $this->hasPaidInstallments());
    }

    public function canCancelCicilan(): bool
    {
        if (! $this->isInstallmentParent()) {
            return false;
        }

        return (float) $this->paid <= 0 && ! $this->hasPaidInstallments();
    }

    public function hasPaidInstallments(): bool
    {
        if ($this->relationLoaded('cicilanChildren')) {
            return $this->cicilanChildren->isNotEmpty();
        }

        if (isset($this->cicilan_children_count)) {
            return (int) $this->cicilan_children_count > 0;
        }

        return $this->cicilanChildren()->exists();
    }

    public function isBillingLocked(): bool
    {
        if ($this->isInstallmentChild()) {
            return true;
        }

        if ($this->isPaid()) {
            return true;
        }

        if ($this->isCicilanInProgress()) {
            return true;
        }

        if (! $this->is_cicilan && (float) $this->paid > 0) {
            return true;
        }

        return false;
    }

    public function isDeletionLocked(): bool
    {
        if ($this->isInstallmentChild() || $this->isPaid()) {
            return true;
        }

        if ((float) $this->paid > 0) {
            return true;
        }

        if ($this->is_cicilan && $this->cicilanChildren()->exists()) {
            return true;
        }

        return false;
    }

    public function isImportLocked(): bool
    {
        if ($this->isBillingLocked()) {
            return true;
        }

        if ($this->is_cicilan && ((float) $this->paid > 0 || $this->cicilanChildren()->exists())) {
            return true;
        }

        return false;
    }

    public function billingLockMessage(): string
    {
        if ($this->isPaid()) {
            return 'Tagihan yang sudah lunas tidak dapat diubah.';
        }

        if ($this->isCicilanInProgress()) {
            return 'Tagihan cicilan yang sudah berjalan tidak dapat diubah.';
        }

        if (! $this->is_cicilan && (float) $this->paid > 0) {
            return 'Tagihan yang sudah dibayar sebagian tidak dapat diubah.';
        }

        return 'Tagihan ini tidak dapat diubah.';
    }

    public function deletionLockMessage(): string
    {
        if ($this->isPaid()) {
            return 'Tagihan yang sudah lunas tidak dapat dihapus.';
        }

        if ((float) $this->paid > 0 || ($this->is_cicilan && $this->cicilanChildren()->exists())) {
            return 'Tagihan yang sudah memiliki pembayaran cicilan tidak dapat dihapus.';
        }

        return 'Tagihan ini tidak dapat dihapus.';
    }

    public function displayPeriode(): string
    {
        return TagihanPeriode::display($this->periode);
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor((int) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return self::statusBadgeClassFor((int) $this->status);
    }

    public static function statusLabelFor(int $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'Lunas',
            self::STATUS_CICILAN => 'Cicilan',
            default => 'Belum Lunas',
        };
    }

    public static function statusBadgeClassFor(int $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'badge-success',
            self::STATUS_CICILAN => 'badge-info',
            default => 'badge-warning',
        };
    }

    public function scopeRootBill(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_UNPAID, self::STATUS_CICILAN]);
    }

    public function scopeHasRemaining(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where(function (Builder $inner) {
                $inner->where('is_cicilan', false)->whereColumn('amount', '>', 'paid');
            })->orWhere(function (Builder $inner) {
                $inner->where('is_cicilan', true)->where('amount', '>', 0);
            });
        });
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCicilan(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CICILAN);
    }

    public function syncPaymentStatus(null|\DateTimeInterface $paidAt = null): void
    {
        if ($this->is_cicilan) {
            return;
        }

        $paid = (float) $this->paid;
        $amount = (float) $this->amount;

        if ($paid >= $amount && $amount > 0) {
            $this->update([
                'status' => self::STATUS_PAID,
                'paid' => $amount,
                'paid_dt' => $paidAt ?? $this->paid_dt ?? now(),
                'paid_dt_actual' => $this->paid_dt_actual ?? now(),
            ]);

            return;
        }

        $this->update([
            'status' => self::STATUS_UNPAID,
            'paid_dt' => null,
            'paid_dt_actual' => null,
        ]);
    }
}
