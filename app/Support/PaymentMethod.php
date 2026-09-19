<?php

namespace App\Support;

/**
 * Human-readable labels for pembayaran / tagihan.fidbank method codes.
 */
final class PaymentMethod
{
    public static function label(?string $method): string
    {
        $method = trim((string) $method);

        if ($method === '') {
            return '-';
        }

        return match ($method) {
            '1140000' => 'Tunai',
            '1140001' => 'Manual BMI',
            '1140002' => 'Saldo Keuangan',
            '1140003' => 'Transfer Bank Lain',
            'transfer' => 'Transfer',
            'tunai' => 'Tunai',
            'qris' => 'QRIS',
            'va' => 'Virtual Account',
            default => strtoupper($method),
        };
    }
}
