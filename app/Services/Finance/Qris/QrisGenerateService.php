<?php

namespace App\Services\Finance\Qris;

use App\Models\QrisPayment;
use App\Models\QrisPaymentItem;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Services\Finance\TagihanCicilanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QrisGenerateService
{
    public function __construct(
        private readonly QrisJwtCodec $jwtCodec,
        private readonly TagihanCicilanService $cicilanService,
    ) {}

    /**
     * @param  list<int>  $tagihanIds
     * @return array{qris: QrisPayment, raw_qr_data: string}
     */
    public function generate(Siswa $siswa, array $tagihanIds, ?int $createdBy = null, string $description = ''): array
    {
        $this->assertEnabled();
        $siswa->assertCanTransact('siswa_id');

        $tagihanIds = array_values(array_unique(array_map('intval', $tagihanIds)));

        if ($tagihanIds === []) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Pilih minimal satu tagihan.',
            ]);
        }

        if (count($tagihanIds) > 30) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Maksimal 30 tagihan per QR.',
            ]);
        }

        $items = [];
        $total = 0;

        foreach ($tagihanIds as $index => $tagihanId) {
            $tagihan = Tagihan::query()->findOrFail($tagihanId);

            if ((int) $tagihan->siswa_id !== (int) $siswa->id) {
                throw ValidationException::withMessages([
                    "tagihan_ids.{$index}" => 'Tagihan tidak milik siswa yang dipilih.',
                ]);
            }

            if ($tagihan->isPaid()) {
                throw ValidationException::withMessages([
                    "tagihan_ids.{$index}" => "Tagihan {$tagihan->jenis} sudah lunas.",
                ]);
            }

            $remaining = $this->cicilanService->payableAmount($tagihan);

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    "tagihan_ids.{$index}" => "Tagihan {$tagihan->jenis} tidak memiliki sisa bayar.",
                ]);
            }

            if (! $tagihan->is_cicilan && $remaining !== (int) round((float) $tagihan->remaining())) {
                throw ValidationException::withMessages([
                    "tagihan_ids.{$index}" => 'Tagihan non-cicilan harus dibayar lunas.',
                ]);
            }

            $items[] = ['tagihan' => $tagihan, 'amount' => $remaining];
            $total += $remaining;
        }

        if ($total < 1000) {
            throw ValidationException::withMessages([
                'tagihan_ids' => 'Nominal QR minimal Rp 1.000.',
            ]);
        }

        $accountNo = (string) config('finance.qris.account_no');
        $mitraCustomerId = (string) config('finance.qris.mitra_customer_id');
        $transactionId = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $vano = $this->makeVano();
        $amount = (string) $total;

        $requestPayload = [
            'accountNo' => $accountNo,
            'amount' => $amount,
            'mitraCustomerId' => $mitraCustomerId,
            'transactionId' => $transactionId,
            'tipeTransaksi' => (string) config('finance.qris.tipe_generate'),
            'vano' => $vano,
        ];

        if ($description !== '') {
            $requestPayload['description'] = $description;
        }

        $token = $this->jwtCodec->encode($requestPayload);
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
            throw new RuntimeException('Gagal menghubungi server QRIS (HTTP '.$response->status().').');
        }

        $serverResponse = $this->parseServerResponse($response->body());

        if (($serverResponse['responseCode'] ?? null) !== '00') {
            $message = (string) ($serverResponse['responseMessage'] ?? 'Generate QRIS gagal.');
            throw new RuntimeException($message);
        }

        $detail = $serverResponse['transactionDetail'] ?? null;

        if (! is_array($detail)) {
            throw new RuntimeException('transactionDetail tidak ditemukan pada respons QRIS.');
        }

        $qrisId = (string) ($detail['transactionQrId'] ?? '');
        $rawQrData = (string) ($detail['rawQrData'] ?? '');

        if ($qrisId === '' || $rawQrData === '') {
            throw new RuntimeException('transactionQrId atau rawQrData kosong pada respons QRIS.');
        }

        $expiredAt = null;
        if (filled($detail['expiredTime'] ?? null)) {
            try {
                $expiredAt = Carbon::parse((string) $detail['expiredTime']);
            } catch (\Throwable) {
                $expiredAt = null;
            }
        }

        $qris = DB::transaction(function () use (
            $siswa,
            $items,
            $total,
            $vano,
            $transactionId,
            $accountNo,
            $mitraCustomerId,
            $qrisId,
            $rawQrData,
            $detail,
            $serverResponse,
            $requestPayload,
            $expiredAt,
            $createdBy,
        ) {
            $payment = QrisPayment::query()->create([
                'siswa_id' => $siswa->id,
                'sekolah_id' => $siswa->sekolah_id,
                'vano' => $vano,
                'amount' => $total,
                'qris_id' => $qrisId,
                'transaction_id' => $transactionId,
                'lazismu_transaction_id' => isset($serverResponse['transactionId'])
                    ? (string) $serverResponse['transactionId']
                    : null,
                'account_no' => $accountNo,
                'mitra_customer_id' => $mitraCustomerId,
                'raw_qr_data' => $rawQrData,
                'merchant_id' => isset($detail['merchantId']) ? (string) $detail['merchantId'] : null,
                'merchant_pan' => isset($detail['merchantPan']) ? (string) $detail['merchantPan'] : null,
                'expired_at' => $expiredAt,
                'status' => QrisPayment::STATUS_PENDING,
                'paid_flag' => false,
                'request_payload' => $requestPayload,
                'response_payload' => $serverResponse,
                'created_by' => $createdBy,
            ]);

            foreach ($items as $item) {
                QrisPaymentItem::query()->create([
                    'qris_payment_id' => $payment->id,
                    'tagihan_id' => $item['tagihan']->id,
                    'amount' => $item['amount'],
                ]);
            }

            return $payment->fresh(['items.tagihan', 'siswa']);
        });

        return [
            'qris' => $qris,
            'raw_qr_data' => $rawQrData,
        ];
    }

    private function assertEnabled(): void
    {
        if (! config('finance.qris.enabled')) {
            throw ValidationException::withMessages([
                'qris' => 'Pembayaran QRIS belum diaktifkan.',
            ]);
        }
    }

    private function makeVano(): string
    {
        $prefix = preg_replace('/\D/', '', (string) config('finance.qris.vano_prefix', '111111')) ?: '111111';
        $prefix = substr($prefix, 0, 6);
        $prefix = str_pad($prefix, 6, '0', STR_PAD_RIGHT);

        return $prefix.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseServerResponse(string $body): array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', trim($body)) ?? '';

        if ($body === '') {
            throw new RuntimeException('Respons server QRIS kosong.');
        }

        $decoded = json_decode($body, true);

        // Some gateways wrap JSON as a quoted string.
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (! is_array($decoded)) {
            $snippet = mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 180);
            throw new RuntimeException('Respons server QRIS tidak valid: '.$snippet);
        }

        return $decoded;
    }
}
