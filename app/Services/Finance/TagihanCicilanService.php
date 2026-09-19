<?php

namespace App\Services\Finance;

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Sccttran;
use App\Models\Tagihan;
use App\Support\DisplayDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TagihanCicilanService
{
    public function enable(Tagihan $tagihan): void
    {
        if ($tagihan->isInstallmentChild()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Baris cicilan tidak dapat diaktifkan sebagai cicilan.',
            ]);
        }

        if ($tagihan->is_cicilan) {
            throw ValidationException::withMessages([
                'tagihan' => 'Tagihan sudah diaktifkan untuk cicilan.',
            ]);
        }

        if ($tagihan->isBillingLocked()) {
            throw ValidationException::withMessages([
                'tagihan' => $tagihan->billingLockMessage(),
            ]);
        }

        $tagihan->update([
            'is_cicilan' => true,
            'total_amount' => $tagihan->amount,
        ]);
    }

    public function cancel(Tagihan $tagihan): void
    {
        if (! $tagihan->is_cicilan || $tagihan->isInstallmentChild()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Tagihan ini tidak menggunakan cicilan.',
            ]);
        }

        if ((float) $tagihan->paid > 0 || $tagihan->cicilanChildren()->exists()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Cicilan yang sudah dibayar sebagian tidak dapat dibatalkan.',
            ]);
        }

        $tagihan->update([
            'is_cicilan' => false,
            'total_amount' => null,
            'amount' => (float) $tagihan->paid + (float) $tagihan->amount,
        ]);
    }

    public function payableAmount(Tagihan $tagihan): int
    {
        if ($tagihan->isInstallmentChild() || $tagihan->isPaid()) {
            return 0;
        }

        if (! $tagihan->is_cicilan) {
            return (int) round($tagihan->remaining());
        }

        return (int) round(max(0, (float) $tagihan->amount));
    }

    public function recordInstallment(
        Tagihan $parent,
        PembayaranDetail $detail,
        int $amount,
        Carbon $paidAt,
        Pembayaran $payment,
        ?Sccttran $sccttran = null,
    ): Tagihan {
        if (! $parent->is_cicilan || $parent->isInstallmentChild()) {
            throw ValidationException::withMessages([
                'tagihan' => 'Tagihan ini tidak mendukung pencatatan cicilan.',
            ]);
        }

        $remaining = (int) round(max(0, (float) $parent->amount));
        if ($amount <= 0 || $amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal cicilan tidak valid untuk sisa tagihan ini.',
            ]);
        }

        return DB::transaction(function () use ($parent, $detail, $amount, $paidAt, $payment, $sccttran) {
            $parent = Tagihan::query()->lockForUpdate()->findOrFail($parent->id);
            $cicilanKe = (int) ($parent->cicilanChildren()->max('cicilan_ke') ?? 0) + 1;

            $child = Tagihan::create([
                'sekolah_id' => $parent->sekolah_id,
                'siswa_id' => $parent->siswa_id,
                'tahun_akademik_id' => $parent->tahun_akademik_id,
                'jenis_tagihan_id' => $parent->jenis_tagihan_id,
                'jenis' => $parent->jenis,
                'periode' => $parent->periode,
                'urutan' => $parent->urutan,
                'due_date' => $parent->due_date,
                'parent_id' => $parent->id,
                'cicilan_ke' => $cicilanKe,
                'total_amount' => $parent->total_amount ?? $parent->amount,
                'amount' => $amount,
                'paid' => $amount,
                'status' => Tagihan::STATUS_PAID,
                'is_cicilan' => false,
                'paid_dt' => $paidAt,
                'paid_dt_actual' => now(),
                'reference' => $payment->reference,
                'fidbank' => $sccttran?->FIDBANK ?? $payment->method,
                'user_id' => $payment->user_id,
                'sccttran_id' => $sccttran?->id,
            ]);

            $parent->decrement('amount', $amount);
            $parent->increment('paid', $amount);
            $parent->refresh();

            if ((float) $parent->amount <= 0) {
                $parent->update([
                    'status' => Tagihan::STATUS_PAID,
                    'amount' => 0,
                    'paid' => $parent->total_amount ?? $parent->paid,
                    'paid_dt' => $paidAt,
                    'paid_dt_actual' => $parent->paid_dt_actual ?? now(),
                    'reference' => $payment->reference,
                    'fidbank' => $sccttran?->FIDBANK ?? $payment->method,
                    'user_id' => $payment->user_id,
                    'sccttran_id' => $sccttran?->id,
                ]);
            } else {
                $parent->update([
                    'status' => Tagihan::STATUS_CICILAN,
                    'paid_dt' => null,
                    'paid_dt_actual' => null,
                ]);
            }

            return $child;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function scheduleSummary(Tagihan $tagihan): array
    {
        if (! $tagihan->is_cicilan || $tagihan->isInstallmentChild()) {
            return [];
        }

        return $tagihan->cicilanChildren()
            ->orderBy('cicilan_ke')
            ->get()
            ->map(fn (Tagihan $row) => [
                'urutan' => $row->cicilan_ke,
                'amount' => (float) $row->amount,
                'paid' => (float) $row->paid,
                'remaining' => 0.0,
                'due_date' => DisplayDate::date($row->paid_dt),
                'status' => $row->status,
                'status_label' => $row->statusLabel(),
                'paid_at' => DisplayDate::datetime($row->paid_dt),
            ])
            ->all();
    }
}
