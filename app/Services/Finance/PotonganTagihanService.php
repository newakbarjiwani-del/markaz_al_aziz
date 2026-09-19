<?php

namespace App\Services\Finance;

use App\Models\PotonganPemakaian;
use App\Models\PotonganSiswa;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\User;
use App\Support\PotonganSiswaStatus;
use App\Support\PotonganTipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PotonganTagihanService
{
    public const MIN_TAGIHAN = 1000;

    /**
     * @return Collection<int, PotonganSiswa>
     */
    public function eligibleAssignments(Siswa $siswa, ?int $jenisTagihanId, ?Carbon $asOfDate = null): Collection
    {
        if (! $siswa->canTransact()) {
            return collect();
        }

        $asOf = ($asOfDate ?? now())->toDateString();

        return PotonganSiswa::query()
            ->with(['jenisPotongan', 'jenisTagihan'])
            ->where('siswa_id', $siswa->id)
            ->where('status', PotonganSiswaStatus::ACTIVE)
            ->whereDate('berlaku_mulai', '<=', $asOf)
            ->whereDate('berlaku_sampai', '>=', $asOf)
            ->whereHas('jenisPotongan', fn ($query) => $query->where('is_active', true))
            ->get()
            ->filter(function (PotonganSiswa $assignment) use ($jenisTagihanId) {
                if (! $assignment->hasQuotaForJenisTagihan($jenisTagihanId)) {
                    return false;
                }

                if ($assignment->appliesToAllJenisTagihan()) {
                    return true;
                }

                if ($jenisTagihanId === null) {
                    return false;
                }

                return $assignment->jenisTagihan->contains('id', $jenisTagihanId);
            })
            ->sortBy([
                fn (PotonganSiswa $a, PotonganSiswa $b) => $a->jenisPotongan->sort_order <=> $b->jenisPotongan->sort_order,
                fn (PotonganSiswa $a, PotonganSiswa $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    public function canApplyToExisting(Tagihan $tagihan): bool
    {
        if ($tagihan->status !== Tagihan::STATUS_UNPAID) {
            return false;
        }

        if ((float) $tagihan->paid > 0) {
            return false;
        }

        if ($tagihan->is_cicilan || $tagihan->isInstallmentChild()) {
            return false;
        }

        if ($tagihan->potonganPemakaian()->exists()) {
            return false;
        }

        $siswa = $tagihan->siswa;

        if ($siswa === null) {
            return false;
        }

        return $this->eligibleAssignments($siswa, $tagihan->jenis_tagihan_id)->isNotEmpty();
    }

    /**
     * @param  Collection<int, PotonganSiswa>  $assignments
     */
    public function applyStack(Collection $assignments, Tagihan $tagihan, ?User $actor = null): bool
    {
        if ($assignments->isEmpty()) {
            return false;
        }

        $bruto = (int) round((float) ($tagihan->amount_bruto ?? $tagihan->amount));
        $sisa = $bruto;
        $urutan = 1;
        $totalPotongan = 0;
        $applied = false;
        $jenisTagihanId = $tagihan->jenis_tagihan_id;

        foreach ($assignments as $assignment) {
            if ($sisa <= 0) {
                break;
            }

            if ($sisa <= self::MIN_TAGIHAN && ! $this->isFullPercentWaiver($assignment, $jenisTagihanId)) {
                break;
            }

            if (! $assignment->hasQuotaForJenisTagihan($jenisTagihanId)) {
                continue;
            }

            $step = $this->calculateStep($assignment, $sisa, $jenisTagihanId);

            if ($step <= 0) {
                continue;
            }

            $sisa -= $step;
            $totalPotongan += $step;

            PotonganPemakaian::create([
                'potongan_siswa_id' => $assignment->id,
                'tagihan_id' => $tagihan->id,
                'amount_bruto' => $bruto,
                'potongan_amount' => $step,
                'amount_net' => $sisa,
                'urutan' => $urutan++,
                'applied_at' => now(),
                'user_id' => $actor?->id,
            ]);

            $assignment->syncExhaustedStatus();

            $applied = true;
        }

        if (! $applied) {
            return false;
        }

        $tagihan->update([
            'amount_bruto' => $bruto,
            'potongan_amount' => $totalPotongan,
            'amount' => $this->netPayable($sisa),
        ]);

        return true;
    }

    public function autoApply(Tagihan $tagihan, ?User $actor = null): bool
    {
        $siswa = $tagihan->siswa ?? Siswa::query()->find($tagihan->siswa_id);

        if ($siswa === null) {
            return false;
        }

        $assignments = $this->eligibleAssignments($siswa, $tagihan->jenis_tagihan_id);

        if ($assignments->isEmpty()) {
            return false;
        }

        return $this->applyStack($assignments, $tagihan, $actor);
    }

    public function applyToExisting(Tagihan $tagihan, ?User $actor = null): Tagihan
    {
        if ($tagihan->status !== Tagihan::STATUS_UNPAID) {
            throw ValidationException::withMessages([
                'tagihan' => 'Potongan hanya dapat diterapkan pada tagihan belum lunas.',
            ]);
        }

        if ((float) $tagihan->paid > 0) {
            throw ValidationException::withMessages([
                'tagihan' => 'Potongan tidak dapat diterapkan pada tagihan yang sudah dibayar sebagian.',
            ]);
        }

        if ($tagihan->is_cicilan || $tagihan->isInstallmentChild()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Potongan tidak dapat diterapkan pada tagihan cicilan.',
            ]);
        }

        if ($tagihan->potonganPemakaian()->exists()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Potongan sudah pernah diterapkan pada tagihan ini.',
            ]);
        }

        $locked = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);
        $siswa = $locked->siswa ?? Siswa::query()->findOrFail($locked->siswa_id);
        $assignments = $this->eligibleAssignments($siswa, $locked->jenis_tagihan_id);

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Tidak ada potongan aktif yang memenuhi syarat untuk tagihan ini.',
            ]);
        }

        $this->applyStack($assignments, $locked, $actor);

        return $locked->fresh(['potonganPemakaian.potonganSiswa.jenisPotongan', 'siswa.kelas', 'tahunAkademik']);
    }

    public function previewWillApply(Siswa $siswa, ?int $jenisTagihanId, ?Carbon $asOfDate = null): bool
    {
        return $this->eligibleAssignments($siswa, $jenisTagihanId, $asOfDate)->isNotEmpty();
    }

    private function calculateStep(PotonganSiswa $assignment, int $sisa, ?int $jenisTagihanId): int
    {
        $cut = $assignment->effectiveCutForJenisTagihan($jenisTagihanId);

        if ($cut === null) {
            return 0;
        }

        if ($cut->tipe === PotonganTipe::PERCENT) {
            $percent = min(100, max(0, (int) $cut->nilai));

            if ($percent >= 100) {
                return $sisa;
            }

            return (int) floor($sisa * $percent / 100);
        }

        return min($cut->nilai, max(0, $sisa - self::MIN_TAGIHAN));
    }

    private function isFullPercentWaiver(PotonganSiswa $assignment, ?int $jenisTagihanId): bool
    {
        $cut = $assignment->effectiveCutForJenisTagihan($jenisTagihanId);

        return $cut !== null
            && $cut->tipe === PotonganTipe::PERCENT
            && (int) $cut->nilai >= 100;
    }

    private function netPayable(int $sisa): int
    {
        if ($sisa <= 0) {
            return 0;
        }

        return max(self::MIN_TAGIHAN, $sisa);
    }
}
