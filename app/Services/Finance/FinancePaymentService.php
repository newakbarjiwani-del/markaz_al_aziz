<?php

namespace App\Services\Finance;

use App\Services\Finance\Handlers\InquiryHandler;
use App\Services\Finance\Handlers\PaymentHandler;
use App\Services\Finance\Handlers\ReversalHandler;
use RuntimeException;
use Throwable;

class FinancePaymentService
{
    public function __construct(
        private readonly FinanceJwtCodec $jwtCodec,
        private readonly FinanceVaResolver $vaResolver,
        private readonly InquiryHandler $inquiryHandler,
        private readonly PaymentHandler $paymentHandler,
        private readonly ReversalHandler $reversalHandler,
    ) {}

    public function process(string $token): string
    {
        try {
            $payload = $this->jwtCodec->decode($token);
        } catch (RuntimeException $e) {
            return $this->jwtCodec->encode(FinanceApiResponse::paymentFailed($e->getMessage()));
        }

        $method = strtoupper((string) ($payload['METHOD'] ?? ''));

        if (! in_array($method, ['INQUIRY', 'PAYMENT', 'REVERSAL'], true)) {
            return $this->jwtCodec->encode(FinanceApiResponse::failed(
                $method !== '' ? $method : 'PAYMENT',
                'Invalid METHOD.',
            ));
        }

        $vano = (string) ($payload['VANO'] ?? '');

        if ($vano === '') {
            return $this->jwtCodec->encode(FinanceApiResponse::failed($method, 'Customer not found.'));
        }

        $siswa = $this->vaResolver->resolveSiswa($vano);

        if ($siswa === null) {
            return $this->jwtCodec->encode(FinanceApiResponse::failed($method, 'Customer not found.'));
        }

        if (! $siswa->canTransact()) {
            return $this->jwtCodec->encode(FinanceApiResponse::failed($method, 'Customer inactive.'));
        }

        try {
            $response = match ($method) {
                'INQUIRY' => $this->inquiryHandler->handle($siswa),
                'PAYMENT' => $this->paymentHandler->handle($siswa, $payload),
                'REVERSAL' => $this->reversalHandler->handle($siswa),
            };
        } catch (Throwable $e) {
            $response = FinanceApiResponse::failed($method, $e->getMessage());
        }

        return $this->jwtCodec->encode($response);
    }
}
