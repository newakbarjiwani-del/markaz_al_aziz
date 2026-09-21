<?php

namespace App\Services\Finance\Qris;

use App\Models\QrisPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class QrisStatusService
{
    public function __construct(
        private readonly QrisJwtCodec $jwtCodec,
        private readonly QrisSettleService $settleService,
    ) {}

    /**
     * @return array{qris: QrisPayment, remote: array<string, mixed>|null, settled: bool}
     */
    public function check(QrisPayment $qris, bool $settleIfPaid = true): array
    {
        $qris->refresh();

        if ($qris->isPaid()) {
            return [
                'qris' => $qris->fresh(['items.tagihan', 'pembayaran', 'siswa']),
                'remote' => null,
                'settled' => false,
            ];
        }

        $payload = [
            'accountNo' => $qris->account_no ?: (string) config('finance.qris.account_no'),
            'amount' => (string) (int) round((float) $qris->amount),
            'mitraCustomerId' => $qris->mitra_customer_id ?: (string) config('finance.qris.mitra_customer_id'),
            'transactionId' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'tipeTransaksi' => (string) config('finance.qris.tipe_check'),
            'vano' => (string) $qris->vano,
            'transactionQrId' => (string) $qris->qris_id,
        ];

        $token = $this->jwtCodec->encode($payload);
        $url = rtrim((string) config('finance.qris.generate_url'), '?&');
        $timeout = (int) config('finance.qris.timeout', 20);

        // Match ICT dummy: POST with token in query, empty body, JSON Content-Type header.
        $response = Http::connectTimeout(5)
            ->timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->withBody('', 'application/json')
            ->post($url.'?token='.urlencode($token));

        if (! $response->successful()) {
            throw new RuntimeException('Gagal cek status QRIS (HTTP '.$response->status().').');
        }

        $remote = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', trim($response->body())) ?? '', true);

        if (is_string($remote)) {
            $remote = json_decode($remote, true);
        }

        if (! is_array($remote)) {
            $remote = ['raw' => $response->body()];
        }

        $paid = $this->detectPaid($remote);
        $settled = false;

        if ($paid && $settleIfPaid && $qris->isPending()) {
            $this->settleService->settle($qris, now(), ['check_status' => $remote], $qris->qris_id);
            $settled = true;
        }

        return [
            'qris' => $qris->fresh(['items.tagihan', 'pembayaran', 'siswa']),
            'remote' => $remote,
            'settled' => $settled,
        ];
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function detectPaid(array $remote): bool
    {
        $candidates = [
            data_get($remote, 'transactionDetail.status'),
            data_get($remote, 'status'),
            data_get($remote, 'responseMessage'),
        ];

        foreach ($candidates as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            $normalized = strtolower($value);
            if (str_contains($normalized, 'paid')
                || str_contains($normalized, 'success')
                || str_contains($normalized, 'settlement')
                || str_contains($normalized, 'lunas')) {
                return true;
            }
        }

        return false;
    }

    public function formatPublic(QrisPayment $qris): array
    {
        return [
            'id' => $qris->id,
            'status' => $qris->status,
            'paid_flag' => (bool) $qris->paid_flag,
            'amount' => (int) round((float) $qris->amount),
            'amount_label' => 'Rp '.number_format((int) round((float) $qris->amount), 0, ',', '.'),
            'vano' => $qris->vano,
            'qris_id' => $qris->qris_id,
            'transaction_id' => $qris->transaction_id,
            'raw_qr_data' => $qris->raw_qr_data,
            'expired_at' => $qris->expired_at instanceof Carbon
                ? $qris->expired_at->format('Y-m-d H:i:s')
                : null,
            'paid_at' => $qris->paid_at instanceof Carbon
                ? $qris->paid_at->format('Y-m-d H:i:s')
                : null,
            'pembayaran_id' => $qris->pembayaran_id,
            'siswa' => [
                'id' => $qris->siswa_id,
                'name' => $qris->siswa?->name,
                'nis' => $qris->siswa?->nis,
            ],
            'tagihan_ids' => $qris->items->pluck('tagihan_id')->values()->all(),
        ];
    }
}
