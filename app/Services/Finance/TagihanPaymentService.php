<?php

namespace App\Services\Finance;

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Sccttran;
use App\Models\Tagihan;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TagihanPaymentService
{
    public function __construct(
        private readonly TagihanCicilanService $cicilanService,
    ) {}

    public function payFullBill(
        Pembayaran $payment,
        Tagihan $tagihan,
        int $amount,
        ?Sccttran $sccttran = null,
        Carbon|string|null $paidAt = null,
    ): PembayaranDetail {
        return $this->payBill($payment, $tagihan, $amount, $sccttran, $paidAt);
    }

    public function payBill(
        Pembayaran $payment,
        Tagihan $tagihan,
        int $amount,
        ?Sccttran $sccttran = null,
        Carbon|string|null $paidAt = null,
    ): PembayaranDetail {
        $paidAtCarbon = $paidAt instanceof Carbon
            ? $paidAt
            : (filled($paidAt) ? Carbon::parse($paidAt) : ($payment->paid_dt ?? now()));

        $remaining = (int) round($tagihan->remaining());
        if ($amount <= 0 || $amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran tidak valid untuk tagihan ini.',
            ]);
        }

        if (! $tagihan->is_cicilan && $amount !== $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'Tagihan non-cicilan harus dibayar lunas sekaligus.',
            ]);
        }

        $detail = PembayaranDetail::create([
            'pembayaran_id' => $payment->id,
            'tagihan_id' => $tagihan->id,
            'amount' => $amount,
            'sccttran_id' => $sccttran?->id,
        ]);

        if ($tagihan->is_cicilan) {
            $this->cicilanService->recordInstallment(
                $tagihan,
                $detail,
                $amount,
                $paidAtCarbon,
                $payment,
                $sccttran,
            );
            $tagihan->refresh();
        } else {
            $tagihan->increment('paid', $amount);
            $tagihan->refresh();
            $tagihan->syncPaymentStatus($paidAtCarbon);

            if ($tagihan->isPaid()) {
                $tagihan->update([
                    'reference' => $payment->reference,
                    'fidbank' => $sccttran?->FIDBANK ?? $payment->method,
                    'user_id' => $payment->user_id,
                    'sccttran_id' => $sccttran?->id,
                ]);
            }
        }

        return $detail;
    }

    public static function masterBillName(Tagihan $tagihan): string
    {
        return $tagihan->tahunAkademik?->name
            ?? $tagihan->jenisTagihan?->name
            ?? $tagihan->jenis
            ?? '';
    }
}
