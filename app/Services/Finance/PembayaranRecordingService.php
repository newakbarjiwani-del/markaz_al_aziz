<?php

namespace App\Services\Finance;

use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Sccttran;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Support\Carbon;

class PembayaranRecordingService
{
    public function __construct(
        private readonly TagihanPaymentService $tagihanPaymentService,
    ) {}

    /**
     * @param  list<array{tagihan: Tagihan, amount: int|float, sccttran: ?Sccttran}>  $items
     */
    public function record(
        Siswa $siswa,
        string $method,
        string $reference,
        Carbon $paidAt,
        ?int $userId,
        array $items,
    ): Pembayaran {
        $totalAmount = array_sum(array_map(fn (array $item) => (float) $item['amount'], $items));

        $payment = Pembayaran::create([
            'user_id' => $userId,
            'siswa_id' => $siswa->id,
            'method' => $method,
            'reference' => $reference,
            'total_amount' => $totalAmount,
            'paid_dt' => $paidAt,
            'paid_dt_actual' => now(),
        ]);

        foreach ($items as $item) {
            $this->tagihanPaymentService->payFullBill(
                payment: $payment,
                tagihan: $item['tagihan'],
                amount: (int) round((float) $item['amount']),
                sccttran: $item['sccttran'] ?? null,
                paidAt: $paidAt,
            );
        }

        return $payment->fresh(['details.tagihan', 'siswa.kelas.sekolah']);
    }

    public function attachBill(
        Pembayaran $payment,
        Tagihan $tagihan,
        int $amount,
        ?Sccttran $sccttran = null,
        ?Carbon $paidAt = null,
    ): PembayaranDetail {
        return $this->tagihanPaymentService->payFullBill(
            payment: $payment,
            tagihan: $tagihan,
            amount: $amount,
            sccttran: $sccttran,
            paidAt: $paidAt ?? $payment->paid_dt,
        );
    }

    public function createOnlinePaymentHeader(
        Siswa $siswa,
        string $method,
        string $reference,
        Carbon|string|null $paidAt,
    ): Pembayaran {
        $paidAtCarbon = $paidAt instanceof Carbon
            ? $paidAt
            : (filled($paidAt) ? Carbon::parse($paidAt) : now());

        return Pembayaran::create([
            'user_id' => null,
            'siswa_id' => $siswa->id,
            'method' => $method,
            'reference' => $reference,
            'total_amount' => 0,
            'paid_dt' => $paidAtCarbon,
            'paid_dt_actual' => now(),
        ]);
    }

    public function syncPaymentTotal(Pembayaran $payment): void
    {
        $payment->update([
            'total_amount' => (float) $payment->details()->sum('amount'),
        ]);
    }
}
