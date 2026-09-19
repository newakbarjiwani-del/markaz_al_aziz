<?php

namespace App\Models;

use App\Support\LibrarySettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single book copy row in a borrow transaction.
 *
 * Parent transaction (borrower identity, dates, notes) lives in the `peminjaman` table.
 * Access parent fields through the `peminjaman` relation, or via the convenience
 * accessors defined below that proxy to `$this->peminjaman->*`.
 */
class PeminjamanBuku extends BaseModel
{
    public const STATUS_DIPINJAM    = 'dipinjam';

    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    public const STATUS_HILANG = 'hilang';

    // Borrower-type constants – kept for backward compat; they mirror Peminjaman constants.
    public const BORROWER_SISWA = 'siswa';

    public const BORROWER_GURU  = 'guru';

    public const BORROWER_TAMU  = 'tamu';

    public const KONDISI_BAIK        = 'baik';

    public const KONDISI_RUSAK_RINGAN = 'rusak_ringan';

    public const KONDISI_RUSAK_BERAT  = 'rusak_berat';

    public const KONDISI_HILANG       = 'hilang';

    protected $table = 'peminjaman_buku';

    protected $fillable = [
        'peminjaman_id',
        'buku_id',
        'qty',
        'return_date',
        'status',
        'fine_amount',
        'catatan_kembali',
        'kondisi_kembali',
        'late_days',
        'perpanjangan_count',
        'processed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'return_date'         => 'date',
            'fine_amount'         => 'decimal:2',
            'late_days'           => 'integer',
            'perpanjangan_count'  => 'integer',
            'qty'                 => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'buku_id');
    }

    /** Delegate to parent relation so callers can do $item->siswa without changes. */
    public function siswa(): BelongsTo
    {
        // Route through the peminjaman parent's siswa relationship.
        return $this->peminjaman()->getModel()->siswa();
    }

    /** Delegate to parent relation so callers can do $item->guru without changes. */
    public function guru(): BelongsTo
    {
        return $this->peminjaman()->getModel()->guru();
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // ─── Parent-field accessors (proxy to peminjaman) ─────────────────────────

    public function getBorrowerTypeAttribute(): ?string
    {
        return $this->peminjaman?->borrower_type;
    }

    public function getSiswaIdAttribute(): ?int
    {
        return $this->peminjaman?->siswa_id;
    }

    public function getGuruIdAttribute(): ?int
    {
        return $this->peminjaman?->guru_id;
    }

    public function getTamaNamaAttribute(): ?string
    {
        return $this->peminjaman?->tamu_nama;
    }

    public function getTamaAsalAttribute(): ?string
    {
        return $this->peminjaman?->tamu_asal;
    }

    public function getTamaTeleponAttribute(): ?string
    {
        return $this->peminjaman?->tamu_telepon;
    }

    public function getLoanDateAttribute(): mixed
    {
        return $this->peminjaman?->loan_date;
    }

    public function getDueDateAttribute(): mixed
    {
        return $this->peminjaman?->due_date;
    }

    public function getCatatanPinjamAttribute(): ?string
    {
        return $this->peminjaman?->catatan_pinjam;
    }

    /** Siswa model through parent relation. */
    public function getSiswaAttribute(): ?Siswa
    {
        return $this->peminjaman?->siswa;
    }

    /** Guru model through parent relation. */
    public function getGuruAttribute(): ?Guru
    {
        return $this->peminjaman?->guru;
    }

    // ─── Borrower helpers (delegate to parent) ───────────────────────────────

    public static function borrowerTypeOptions(): array
    {
        return Peminjaman::borrowerTypeOptions();
    }

    public function borrowerTypeLabel(): string
    {
        return $this->peminjaman?->borrowerTypeLabel() ?? '-';
    }

    public function isGuest(): bool
    {
        return $this->peminjaman?->isGuest() ?? false;
    }

    public function borrowerName(): string
    {
        return $this->peminjaman?->borrowerName() ?? '-';
    }

    public function borrowerIdentifier(): string
    {
        return $this->peminjaman?->borrowerIdentifier() ?? '-';
    }

    public function borrowerMeta(): string
    {
        return $this->peminjaman?->borrowerMeta() ?? '-';
    }

    public function borrowerDisplayLabel(): string
    {
        return $this->peminjaman?->borrowerDisplayLabel() ?? '-';
    }

    // ─── Timing helpers ───────────────────────────────────────────────────────

    public function isOverdue(?CarbonInterface $referenceDate = null): bool
    {
        if ($this->status !== self::STATUS_DIPINJAM) {
            return false;
        }

        $referenceDate = ($referenceDate ?? now())->startOfDay();
        $dueDate       = $this->due_date;

        if (! $dueDate) {
            return false;
        }

        return $referenceDate->gt($dueDate);
    }

    public function daysLate(?CarbonInterface $referenceDate = null): int
    {
        if (! $this->isOverdue($referenceDate)) {
            return 0;
        }

        $referenceDate = ($referenceDate ?? now())->startOfDay();

        return (int) $this->due_date->diffInDays($referenceDate);
    }

    public function daysRemaining(?CarbonInterface $referenceDate = null): int
    {
        if ($this->status !== self::STATUS_DIPINJAM) {
            return 0;
        }

        $dueDate = $this->due_date;
        if (! $dueDate) {
            return 0;
        }

        $referenceDate = ($referenceDate ?? now())->startOfDay();

        if ($referenceDate->gt($dueDate)) {
            return 0;
        }

        return (int) $referenceDate->diffInDays($dueDate);
    }

    /**
     * @return array{late_days: int, late_fine: int, damage_fine: int, total_fine: int}
     */
    public function calculateFine(?CarbonInterface $returnDate = null, ?string $kondisi = self::KONDISI_BAIK): array
    {
        $qty        = max(1, (int) $this->qty);
        $returnDate = ($returnDate ?? now())->startOfDay();
        $dueDate    = $this->due_date;
        $lateDays   = 0;
        $lateFine   = 0;

        if ($dueDate && $returnDate->gt($dueDate)) {
            $lateDays = (int) $dueDate->diffInDays($returnDate);
            $lateFine = $lateDays * LibrarySettings::finePerDay();
        }

        $perCopyDamageFine = LibrarySettings::fineForKondisi($kondisi);

        return [
            'late_days'   => $lateDays,
            'late_fine'   => $lateFine * $qty,
            'damage_fine' => $perCopyDamageFine * $qty,
            'total_fine'  => ($lateFine + $perCopyDamageFine) * $qty,
        ];
    }

    public function canExtend(): bool
    {
        if ($this->status !== self::STATUS_DIPINJAM) {
            return false;
        }

        if ($this->isOverdue()) {
            return false;
        }

        return $this->perpanjangan_count < LibrarySettings::maxExtensions();
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_DIPINJAM && $this->isOverdue()) {
            return 'Terlambat';
        }

        return match ($this->status) {
            self::STATUS_DIPINJAM     => 'Dipinjam',
            self::STATUS_DIKEMBALIKAN => 'Dikembalikan',
            self::STATUS_HILANG       => 'Hilang',
            default                   => ucfirst($this->status),
        };
    }

    public static function kondisiOptions(): array
    {
        return [
            self::KONDISI_BAIK         => 'Baik',
            self::KONDISI_RUSAK_RINGAN => 'Rusak ringan',
            self::KONDISI_RUSAK_BERAT  => 'Rusak berat',
            self::KONDISI_HILANG       => 'Hilang',
        ];
    }

    public function kondisiKembaliLabel(): ?string
    {
        if (! filled($this->kondisi_kembali)) {
            return null;
        }

        return self::kondisiOptions()[$this->kondisi_kembali] ?? ucfirst(str_replace('_', ' ', $this->kondisi_kembali));
    }
}
