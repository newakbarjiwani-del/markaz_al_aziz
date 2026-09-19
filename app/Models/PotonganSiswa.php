<?php

namespace App\Models;

use App\Support\PotonganSiswaStatus;
use App\Support\PotonganTipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PotonganSiswa extends BaseModel
{
    protected $table = 'potongan_siswa';

    protected $fillable = [
        'sekolah_id',
        'siswa_id',
        'jenis_potongan_id',
        'tipe',
        'nilai',
        'berlaku_mulai',
        'berlaku_sampai',
        'max_pemakaian',
        'status',
        'keterangan',
        'processed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
            'max_pemakaian' => 'integer',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
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

    public function jenisPotongan(): BelongsTo
    {
        return $this->belongsTo(JenisPotongan::class, 'jenis_potongan_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function jenisTagihan(): BelongsToMany
    {
        return $this->belongsToMany(
            JenisTagihan::class,
            'potongan_siswa_jenis_tagihan',
            'potongan_siswa_id',
            'jenis_tagihan_id'
        )->withPivot(['tipe', 'nilai', 'max_pemakaian'])->withTimestamps();
    }

    public function pemakaian(): HasMany
    {
        return $this->hasMany(PotonganPemakaian::class, 'potongan_siswa_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PotonganSiswaStatus::ACTIVE);
    }

    public function statusLabel(): string
    {
        return PotonganSiswaStatus::label($this->status);
    }

    public function tipeLabel(): string
    {
        return PotonganTipe::label($this->tipe);
    }

    public function appliesToAllJenisTagihan(): bool
    {
        if ($this->relationLoaded('jenisTagihan')) {
            return $this->jenisTagihan->isEmpty();
        }

        return ! $this->jenisTagihan()->exists();
    }

    /**
     * Resolve tipe/nilai for a tagihan jenis (pivot override, else parent row).
     */
    public function effectiveCutForJenisTagihan(?int $jenisTagihanId): ?object
    {
        if ($jenisTagihanId === null) {
            return null;
        }

        if ($this->appliesToAllJenisTagihan()) {
            return (object) [
                'tipe' => PotonganTipe::normalize($this->tipe),
                'nilai' => (int) $this->nilai,
            ];
        }

        if (! $this->relationLoaded('jenisTagihan')) {
            $this->load('jenisTagihan');
        }

        $match = $this->jenisTagihan->firstWhere('id', $jenisTagihanId);

        if ($match === null) {
            return null;
        }

        $tipe = $match->pivot->tipe ?? $this->tipe;
        $nilai = $match->pivot->nilai ?? $this->nilai;

        return (object) [
            'tipe' => PotonganTipe::normalize($tipe),
            'nilai' => (int) $nilai,
        ];
    }

    public function effectiveMaxForJenisTagihan(?int $jenisTagihanId): int
    {
        if ($this->appliesToAllJenisTagihan()) {
            return (int) $this->max_pemakaian;
        }

        if ($jenisTagihanId === null) {
            return (int) $this->max_pemakaian;
        }

        if (! $this->relationLoaded('jenisTagihan')) {
            $this->load('jenisTagihan');
        }

        $match = $this->jenisTagihan->firstWhere('id', $jenisTagihanId);

        if ($match === null) {
            return 0;
        }

        return (int) ($match->pivot->max_pemakaian ?? $this->max_pemakaian);
    }

    public function usageCountForJenisTagihan(?int $jenisTagihanId): int
    {
        $query = $this->pemakaian();

        if ($jenisTagihanId !== null) {
            $query->whereHas('tagihan', fn ($q) => $q->where('jenis_tagihan_id', $jenisTagihanId));
        }

        return $query->count();
    }

    public function hasQuotaForJenisTagihan(?int $jenisTagihanId): bool
    {
        return $this->usageCountForJenisTagihan($jenisTagihanId) < $this->effectiveMaxForJenisTagihan($jenisTagihanId);
    }

    public function syncExhaustedStatus(): void
    {
        if ($this->status !== PotonganSiswaStatus::ACTIVE) {
            return;
        }

        if ($this->appliesToAllJenisTagihan()) {
            if (! $this->hasQuotaForJenisTagihan(null)) {
                $this->update(['status' => PotonganSiswaStatus::EXHAUSTED]);
            }

            return;
        }

        if (! $this->relationLoaded('jenisTagihan')) {
            $this->load('jenisTagihan');
        }

        $anyQuotaLeft = $this->jenisTagihan->contains(
            fn (JenisTagihan $jenis) => $this->hasQuotaForJenisTagihan($jenis->id)
        );

        if (! $anyQuotaLeft) {
            $this->update(['status' => PotonganSiswaStatus::EXHAUSTED]);
        }
    }
}
