<?php

namespace App\Services\Finance;

use App\Models\Pembayaran;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Support\Carbon;

class BillAllocationResult
{
    /**
     * @param  list<array{tagihan: Tagihan, pembayaran_detail: \App\Models\PembayaranDetail, sccttran: Sccttran}>  $paidBills
     */
    public function __construct(
        public readonly array $paidBills,
        public readonly ?Pembayaran $payment = null,
    ) {}

    public function firstPaidTagihan(): ?Tagihan
    {
        return $this->paidBills[0]['tagihan'] ?? null;
    }
}

class BillAllocator
{
    public function __construct(
        private readonly SccttranLogger $sccttranLogger,
        private readonly PembayaranRecordingService $pembayaranRecordingService,
        private readonly TagihanCicilanService $cicilanService,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function allocate(Siswa $siswa, SaldoKeuangan $saldo, string $sccttranMetode, array $context): BillAllocationResult
    {
        $paidBills = [];
        $single = config('finance.payment_mode', 'auto_loop') === 'single';
        $payment = null;

        while (true) {
            $tagihan = FirstUnpaidTagihanQuery::forSiswa($siswa->id);

            if ($tagihan === null) {
                break;
            }

            $tagihan = Tagihan::query()->lockForUpdate()->find($tagihan->id);

            if ($tagihan === null || $tagihan->isPaid()) {
                continue;
            }

            $remaining = $this->cicilanService->payableAmount($tagihan);

            if ($remaining <= 0 || (float) $saldo->balance < $remaining) {
                break;
            }

            if ($payment === null) {
                $payment = $this->pembayaranRecordingService->createOnlinePaymentHeader(
                    $siswa,
                    (string) ($context['payment_method'] ?? 'va'),
                    (string) ($context['refno'] ?? ''),
                    $context['trxdate'] ?? null,
                );
            }

            $sccttran = $sccttranMetode === 'FROM SALDO'
                ? $this->sccttranLogger->fromSaldo($siswa->id, $remaining, $context)
                : $this->sccttranLogger->fromInvoice($siswa->id, $remaining, $context);

            $detail = $this->pembayaranRecordingService->attachBill(
                $payment,
                $tagihan,
                $remaining,
                $sccttran,
                $payment->paid_dt instanceof Carbon ? $payment->paid_dt : null,
            );

            $saldo->decrement('balance', $remaining);
            $saldo->refresh();

            $paidBills[] = [
                'tagihan' => $tagihan->fresh(['tahunAkademik', 'jenisTagihan']),
                'pembayaran_detail' => $detail,
                'sccttran' => $sccttran,
            ];

            if ($single) {
                break;
            }
        }

        if ($payment !== null) {
            $this->pembayaranRecordingService->syncPaymentTotal($payment);
            $payment->refresh();
        }

        return new BillAllocationResult($paidBills, $payment);
    }
}
