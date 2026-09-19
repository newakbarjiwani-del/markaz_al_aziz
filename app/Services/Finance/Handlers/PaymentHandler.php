<?php

namespace App\Services\Finance\Handlers;

use App\Models\Siswa;
use App\Services\Finance\BillAllocator;
use App\Services\Finance\FinanceApiResponse;
use App\Services\Finance\SaldoKeuanganService;
use App\Services\Finance\SccttranLogger;
use App\Services\Finance\TagihanPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentHandler
{
    public function __construct(
        private readonly SccttranLogger $sccttranLogger,
        private readonly SaldoKeuanganService $saldoKeuanganService,
        private readonly BillAllocator $billAllocator,
    ) {}

    /**
     * @param  array<string, mixed>  $token
     * @return array<string, mixed>
     */
    public function handle(Siswa $siswa, array $token): array
    {
        $refno = (string) ($token['REFNO'] ?? '');
        $channelId = (string) ($token['CHANNELID'] ?? '');

        try {
            if ($refno === '') {
                return FinanceApiResponse::paymentFailed('REFNO is required.');
            }

            if ($this->sccttranLogger->topUpRefExists($refno)) {
                return FinanceApiResponse::paymentFailed('Duplicate transaction reference.');
            }

            $biayaAdmin = (int) config('finance.biaya_admin', 0);
            $grossPayment = (int) ($token['PAYMENT'] ?? 0);
            $netPayment = $grossPayment - $biayaAdmin;

            if ($netPayment <= 0) {
                return FinanceApiResponse::paymentFailed('Invalid payment amount.');
            }

            $context = [
                'refno' => $refno,
                'trxdate' => $token['TRXDATE'] ?? null,
                'kodebank' => $token['KODEBANK'] ?? null,
                'channelid' => $channelId,
                'payment_method' => 'va',
            ];

            $result = DB::transaction(function () use ($siswa, $netPayment, $context) {
                $saldo = $this->saldoKeuanganService->forSiswa($siswa, lock: true);

                $this->sccttranLogger->topUp($siswa->id, $netPayment, $context);
                $this->saldoKeuanganService->credit($saldo, $netPayment);
                $saldo->refresh();

                return $this->billAllocator->allocate($siswa, $saldo, 'FROM INVOICE', $context);
            });

            $firstPaid = $result->firstPaidTagihan();

            return FinanceApiResponse::success([
                'CCY' => '360',
                'BILL' => $netPayment + $biayaAdmin,
                'DESCRIPTION' => $firstPaid ? TagihanPaymentService::masterBillName($firstPaid) : '',
                'DESCRIPTION2' => $firstPaid?->jenis ?? '',
                'CUSTNAME' => $siswa->name,
                'METHOD' => 'PAYMENT',
            ]);
        } catch (Throwable $e) {
            Log::channel('payment')->error('Payment failed', [
                'error' => $e->getMessage(),
                'NOREFF' => $refno,
                'FIDBANK' => $channelId,
            ]);

            return FinanceApiResponse::paymentFailed($e->getMessage());
        }
    }
}
