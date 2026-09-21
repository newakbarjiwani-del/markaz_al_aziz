<?php

namespace App\Services\Finance\Qris;

use App\Models\Pembayaran;
use App\Models\QrisPayment;
use App\Models\Tagihan;
use App\Services\Finance\PembayaranRecordingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QrisSettleService
{
    public function __construct(
        private readonly PembayaranRecordingService $recordingService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $pushPayload
     */
    public function settle(QrisPayment $qris, ?Carbon $paidAt = null, ?array $pushPayload = null, ?string $reference = null): QrisPayment
    {
        return DB::transaction(function () use ($qris, $paidAt, $pushPayload, $reference) {
            /** @var QrisPayment $locked */
            $locked = QrisPayment::query()->lockForUpdate()->findOrFail($qris->id);

            if ($locked->isPaid()) {
                return $locked->fresh(['items.tagihan', 'pembayaran', 'siswa']);
            }

            $locked->load(['items.tagihan', 'siswa']);

            if ($locked->siswa === null) {
                throw new RuntimeException('Siswa QRIS tidak ditemukan.');
            }

            $locked->siswa->assertCanTransact('siswa_id');

            $paymentItems = [];

            foreach ($locked->items as $item) {
                $tagihan = Tagihan::query()->lockForUpdate()->find($item->tagihan_id);

                if ($tagihan === null) {
                    throw new RuntimeException('Tagihan QRIS tidak ditemukan.');
                }

                if ($tagihan->isPaid()) {
                    throw new RuntimeException("Tagihan {$tagihan->jenis} sudah lunas sebelum settle QRIS.");
                }

                $amount = (int) round((float) $item->amount);
                $remaining = (int) round((float) $tagihan->remaining());

                if ($amount <= 0 || $amount > $remaining) {
                    throw new RuntimeException("Nominal QRIS tidak cocok untuk tagihan {$tagihan->jenis}.");
                }

                if (! $tagihan->is_cicilan && $amount !== $remaining) {
                    throw new RuntimeException("Tagihan non-cicilan harus dilunasi penuh via QRIS ({$tagihan->jenis}).");
                }

                $paymentItems[] = [
                    'tagihan' => $tagihan,
                    'amount' => $amount,
                    'sccttran' => null,
                ];
            }

            if ($paymentItems === []) {
                throw new RuntimeException('Tidak ada tagihan pada sesi QRIS.');
            }

            $paidAtCarbon = $paidAt ?? now();
            $method = (string) config('finance.qris.payment_method', 'qris');
            $ref = $reference
                ?: ($locked->qris_id ?: $locked->transaction_id);

            $pembayaran = $this->recordingService->record(
                $locked->siswa,
                $method,
                (string) $ref,
                $paidAtCarbon,
                $locked->created_by,
                $paymentItems,
            );

            $locked->update([
                'status' => QrisPayment::STATUS_PAID,
                'paid_flag' => true,
                'paid_at' => $paidAtCarbon,
                'pembayaran_id' => $pembayaran->id,
                'push_payload' => $pushPayload,
            ]);

            return $locked->fresh(['items.tagihan', 'pembayaran', 'siswa']);
        });
    }

    public function alreadyProcessedResponse(QrisPayment $qris): array
    {
        return [
            'responseCode' => '00',
            'responseMessage' => 'TRANSACTION SUCCESS',
            'responseTimestamp' => now()->format('Y-m-d H:i:s'),
            'transactionId' => $qris->lazismu_transaction_id ?? $qris->transaction_id,
            'paymentTime' => optional($qris->paid_at)?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'amount' => (string) (int) round((float) $qris->amount),
            'vano' => $qris->vano,
            'qrisId' => $qris->qris_id,
            'processed' => 'already_processed',
            'paymentType' => 'tagihan',
            'pembayaran_id' => $qris->pembayaran_id,
        ];
    }

    public function successResponse(QrisPayment $qris, ?Pembayaran $pembayaran = null): array
    {
        return [
            'responseCode' => '00',
            'responseMessage' => 'TRANSACTION SUCCESS',
            'responseTimestamp' => now()->format('Y-m-d H:i:s'),
            'transactionId' => $qris->lazismu_transaction_id ?? $qris->transaction_id,
            'paymentTime' => optional($qris->paid_at)?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'amount' => (string) (int) round((float) $qris->amount),
            'vano' => $qris->vano,
            'qrisId' => $qris->qris_id,
            'processed' => 'new_processing',
            'paymentType' => 'tagihan',
            'pembayaran_id' => $pembayaran?->id ?? $qris->pembayaran_id,
        ];
    }
}
