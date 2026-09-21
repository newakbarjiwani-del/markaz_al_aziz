<?php

namespace App\Services\Finance\Qris;

use App\Models\QrisPayment;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

class QrisPushNotifService
{
    public function __construct(
        private readonly QrisJwtCodec $jwtCodec,
        private readonly QrisSettleService $settleService,
    ) {}

    /**
     * @return array{http: int, body: array<string, mixed>}
     */
    public function handle(?string $token): array
    {
        if (! filled($token)) {
            return [
                'http' => 400,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Token tidak ditemukan',
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                ],
            ];
        }

        try {
            $decoded = $this->jwtCodec->decode($token);
        } catch (Throwable $e) {
            return [
                'http' => 400,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Token tidak valid: '.$e->getMessage(),
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $responseCode = (string) ($decoded['responseCode'] ?? '');

        if ($responseCode !== '00') {
            return [
                'http' => 400,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => (string) ($decoded['responseMessage'] ?? 'Pembayaran gagal'),
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $data = $decoded['data'] ?? null;

        if (! is_array($data)) {
            return [
                'http' => 400,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Data pembayaran tidak ditemukan',
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $transactionQrId = (string) ($data['transactionQrId'] ?? '');
        $vano = (string) ($data['vano1'] ?? $data['vano'] ?? '');
        $amount = $data['amount'] ?? null;
        $pgTransactionId = isset($decoded['transactionId']) ? (string) $decoded['transactionId'] : null;

        $qris = $this->findPayment($transactionQrId, $vano);

        if ($qris === null) {
            return [
                'http' => 404,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => 'Data QR tagihan tidak ditemukan',
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                    'transactionQrId' => $transactionQrId,
                    'vano' => $vano,
                ],
            ];
        }

        if ($qris->isPaid()) {
            return [
                'http' => 200,
                'body' => $this->settleService->alreadyProcessedResponse($qris),
            ];
        }

        try {
            $paidAt = now();
            if (filled($decoded['responseTimestamp'] ?? null)) {
                try {
                    $paidAt = Carbon::parse((string) $decoded['responseTimestamp']);
                } catch (Throwable) {
                    $paidAt = now();
                }
            }

            if ($amount !== null && is_numeric($amount)) {
                $paidAmount = (int) round((float) $amount);
                $expected = (int) round((float) $qris->amount);
                if ($paidAmount > 0 && $paidAmount !== $expected) {
                    // Allow settle with push amount logged; still require exact match for safety.
                    throw new RuntimeException(
                        'Nominal push ('.$paidAmount.') tidak sama dengan QR ('.$expected.').'
                    );
                }
            }

            $settled = $this->settleService->settle(
                $qris,
                $paidAt,
                [
                    'decoded' => $decoded,
                    'pg_transaction_id' => $pgTransactionId,
                ],
                $transactionQrId !== '' ? $transactionQrId : $pgTransactionId,
            );

            if ($pgTransactionId) {
                $settled->update([
                    'lazismu_transaction_id' => $pgTransactionId,
                ]);
            }

            return [
                'http' => 200,
                'body' => $this->settleService->successResponse($settled),
            ];
        } catch (Throwable $e) {
            return [
                'http' => 500,
                'body' => [
                    'responseCode' => '01',
                    'responseMessage' => $e->getMessage(),
                    'responseTimestamp' => now()->format('Y-m-d H:i:s'),
                    'transactionQrId' => $transactionQrId,
                    'vano' => $vano,
                ],
            ];
        }
    }

    private function findPayment(string $transactionQrId, string $vano): ?QrisPayment
    {
        if ($transactionQrId !== '') {
            $byQris = QrisPayment::query()->where('qris_id', $transactionQrId)->first();
            if ($byQris) {
                return $byQris;
            }
        }

        if ($vano !== '') {
            return QrisPayment::query()
                ->where('vano', $vano)
                ->where('status', QrisPayment::STATUS_PENDING)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }
}
