<?php

namespace App\Services\Finance\Handlers;

use App\Models\Siswa;
use App\Services\Finance\FinanceApiResponse;
use Throwable;

class ReversalHandler
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Siswa $siswa): array
    {
        try {
            return FinanceApiResponse::success([
                'CCY' => '360',
                'CUSTNAME' => $siswa->name,
                'METHOD' => 'REVERSAL',
            ]);
        } catch (Throwable $e) {
            return FinanceApiResponse::reversalFailed($e->getMessage());
        }
    }
}
