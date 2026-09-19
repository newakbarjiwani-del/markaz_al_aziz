<?php

namespace App\Services\Finance\Handlers;

use App\Models\Siswa;
use App\Services\Finance\FinanceApiResponse;
use App\Services\Finance\FirstUnpaidTagihanQuery;
use App\Services\Finance\TagihanCicilanService;
use App\Services\Finance\TagihanPaymentService;
use Throwable;

class InquiryHandler
{
    public function __construct(
        private readonly TagihanCicilanService $cicilanService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Siswa $siswa): array
    {
        try {
            $tagihan = FirstUnpaidTagihanQuery::forSiswa($siswa->id);

            if ($tagihan === null) {
                return FinanceApiResponse::success([
                    'CCY' => '360',
                    'BILL' => '0',
                    'DESCRIPTION' => '',
                    'DESCRIPTION2' => '',
                    'CUSTNAME' => $siswa->name,
                    'METHOD' => 'INQUIRY',
                ]);
            }

            $biayaAdmin = (int) config('finance.biaya_admin', 0);
            $billAmount = $this->cicilanService->payableAmount($tagihan);

            return FinanceApiResponse::success([
                'CCY' => '360',
                'BILL' => (string) (($billAmount + $biayaAdmin) * 100),
                'DESCRIPTION' => TagihanPaymentService::masterBillName($tagihan),
                'DESCRIPTION2' => $tagihan->jenis,
                'CUSTNAME' => $siswa->name,
                'METHOD' => 'INQUIRY',
            ]);
        } catch (Throwable $e) {
            return FinanceApiResponse::inquiryFailed($e->getMessage());
        }
    }
}
