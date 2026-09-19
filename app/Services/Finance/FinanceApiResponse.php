<?php

namespace App\Services\Finance;

class FinanceApiResponse
{
    public const ERR_SUCCESS = '00';

    public const ERR_FAILED = '15';

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public static function success(array $fields): array
    {
        return array_merge(['ERR' => self::ERR_SUCCESS], $fields);
    }

    /**
     * @return array<string, mixed>
     */
    public static function failed(string $method, string $message): array
    {
        return match (strtoupper($method)) {
            'INQUIRY' => self::inquiryFailed($message),
            'REVERSAL' => self::reversalFailed($message),
            default => self::paymentFailed($message),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function inquiryFailed(string $message): array
    {
        return [
            'CCY' => '360',
            'BILL' => '',
            'DESCRIPTION' => '',
            'DESCRIPTION2' => '',
            'CUSTNAME' => '',
            'ERR' => self::ERR_FAILED,
            'METHOD' => 'INQUIRY',
            'err' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentFailed(string $message): array
    {
        return [
            'CCY' => '360',
            'BILL' => '',
            'DESCRIPTION' => '',
            'DESCRIPTION2' => '',
            'CUSTNAME' => '',
            'ERR' => self::ERR_FAILED,
            'METHOD' => 'PAYMENT',
            'err' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function reversalFailed(string $message): array
    {
        return [
            'ERR' => self::ERR_FAILED,
            'METHOD' => 'REVERSAL',
            'err' => $message,
        ];
    }
}
