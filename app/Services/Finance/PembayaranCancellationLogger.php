<?php

namespace App\Services\Finance;

use App\Models\LogPembayaranBatal;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class PembayaranCancellationLogger
{
    public function log(Request $request, Pembayaran $payment): LogPembayaranBatal
    {
        $payment->loadMissing(['details.tagihan', 'siswa']);

        return LogPembayaranBatal::create([
            'pembayaran_id' => $payment->id,
            'siswa_id' => $payment->siswa_id,
            'cancelled_by' => $request->user()?->id,
            'original_user_id' => $payment->user_id,
            'reference' => $payment->reference,
            'method' => $payment->method,
            'total_amount' => $payment->total_amount,
            'paid_at' => $payment->paid_dt,
            'items' => $this->buildItemsSnapshot($payment),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildItemsSnapshot(Pembayaran $payment): array
    {
        return $payment->details
            ->sortBy('id')
            ->map(function ($detail) use ($payment) {
                $tagihan = $detail->tagihan;
                $currentPaid = (float) $detail->amount;
                $billAmount = $tagihan?->displayAmount() ?? $currentPaid;
                $paidTotal = $tagihan?->isInstallmentParent()
                    ? max((float) $tagihan->paid, $currentPaid)
                    : max((float) ($tagihan?->paid ?? 0), $currentPaid);

                if ($tagihan?->isInstallmentParent()) {
                    $childQuery = $tagihan->cicilanChildren()
                        ->where('reference', $payment->reference)
                        ->where('amount', $currentPaid);

                    if ($payment->paid_dt !== null) {
                        $childQuery->where('paid_dt', $payment->paid_dt);
                    }

                    $child = $childQuery->orderByDesc('cicilan_ke')->first();
                    if ($child !== null) {
                        $paidTotal = (float) $tagihan->cicilanChildren()
                            ->where('cicilan_ke', '<=', $child->cicilan_ke)
                            ->sum('amount');
                    }
                }

                return [
                    'tagihan_id' => $detail->tagihan_id,
                    'jenis' => $tagihan?->jenis,
                    'periode' => $tagihan?->displayPeriode(),
                    'amount' => $currentPaid,
                    'bill_amount' => $billAmount,
                    'paid_total' => $paidTotal,
                    'is_cicilan' => (bool) ($tagihan?->isInstallmentParent()),
                ];
            })
            ->values()
            ->all();
    }
}
