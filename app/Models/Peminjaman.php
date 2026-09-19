<?php

namespace App\Models;

use App\Models\PeminjamanBuku;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peminjaman extends BaseModel
{
    public const BORROWER_SISWA = 'siswa';

    public const BORROWER_GURU = 'guru';

    public const BORROWER_TAMU = 'tamu';

    protected $table = 'peminjaman';

    protected $fillable = [
        'borrower_type',
        'siswa_id',
        'guru_id',
        'tamu_nama',
        'tamu_asal',
        'tamu_telepon',
        'loan_date',
        'due_date',
        'catatan_pinjam',
        'processed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date'  => 'date',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(PeminjamanBuku::class, 'peminjaman_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // ─── Borrower helpers ─────────────────────────────────────────────────────

    public static function borrowerTypeOptions(): array
    {
        return [
            self::BORROWER_SISWA => 'Siswa',
            self::BORROWER_GURU  => 'Guru',
            self::BORROWER_TAMU  => 'Tamu',
        ];
    }

    public function borrowerTypeLabel(): string
    {
        return self::borrowerTypeOptions()[$this->borrower_type] ?? ucfirst((string) $this->borrower_type);
    }

    public function isGuest(): bool
    {
        return $this->borrower_type === self::BORROWER_TAMU;
    }

    public function borrowerName(): string
    {
        return match ($this->borrower_type) {
            self::BORROWER_GURU  => $this->guru?->name ?? '-',
            self::BORROWER_TAMU  => $this->tamu_nama ?: '-',
            default              => $this->siswa?->name ?? '-',
        };
    }

    public function borrowerIdentifier(): string
    {
        return match ($this->borrower_type) {
            self::BORROWER_GURU  => $this->guru?->nip ? 'NIP '.$this->guru->nip : '-',
            self::BORROWER_TAMU  => $this->tamu_telepon ?: '-',
            default              => $this->siswa?->nis ? 'NIS '.$this->siswa->nis : '-',
        };
    }

    public function borrowerMeta(): string
    {
        return match ($this->borrower_type) {
            self::BORROWER_GURU  => $this->guru?->jabatan ?: '-',
            self::BORROWER_TAMU  => $this->tamu_asal ?: '-',
            default              => $this->siswa?->kelas?->name ?: '-',
        };
    }

    public function borrowerDisplayLabel(): string
    {
        $name = $this->borrowerName();
        $id   = match ($this->borrower_type) {
            self::BORROWER_GURU  => $this->guru?->nip ? 'NIP '.$this->guru->nip : null,
            self::BORROWER_TAMU  => $this->tamu_telepon ?: null,
            default              => $this->siswa?->nis ? 'NIS '.$this->siswa->nis : null,
        };

        return $id ? $name.' · '.$id : $name;
    }

    /**
     * Latest aggregated status across all items in this transaction.
     */
    public function latestStatus(): string
    {
        if ($this->items === null) {
            return 'dipinjam';
        }

        $allReturned = $this->items->every(fn ($i) => $i->status === PeminjamanBuku::STATUS_DIKEMBALIKAN);
        $anyActive   = $this->items->contains(fn ($i) => $i->status === PeminjamanBuku::STATUS_DIPINJAM);

        if ($allReturned) {
            return 'dikembalikan';
        }

        if ($anyActive) {
            return 'dipinjam';
        }

        return $this->items->first()?->status ?? 'dipinjam';
    }
}
