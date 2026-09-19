<?php

namespace App\Support;

use App\Models\PengaturanCashless;

final class CashlessSettings
{
    /** Only daily spending limit is enforced; weekly/monthly are not configured yet. */
    public const DEFAULT_DAILY_TRANSACTION_LIMIT = 50000;

    public const DEFAULT_MIN_TOPUP = 10000;

    public static function forSekolah(int $sekolahId): PengaturanCashless
    {
        return PengaturanCashless::query()->firstOrCreate(
            ['sekolah_id' => $sekolahId],
            [
                'daily_transaction_limit' => self::DEFAULT_DAILY_TRANSACTION_LIMIT,
                'min_topup' => self::DEFAULT_MIN_TOPUP,
                'allow_transfer' => true,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(?int $sekolahId = null): array
    {
        $settings = self::forSekolah(self::resolveSekolahId($sekolahId));

        return [
            'daily_transaction_limit' => (float) $settings->daily_transaction_limit,
            'min_topup' => (float) $settings->min_topup,
            'allow_transfer' => (bool) $settings->allow_transfer,
        ];
    }

    public static function dailyTransactionLimit(?int $sekolahId = null): ?float
    {
        $value = self::forSekolah(self::resolveSekolahId($sekolahId))->daily_transaction_limit;

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateForSekolah(int $sekolahId, array $data): PengaturanCashless
    {
        $settings = self::forSekolah($sekolahId);
        $settings->update([
            'daily_transaction_limit' => $data['daily_transaction_limit'] ?? $settings->daily_transaction_limit,
            'min_topup' => $data['min_topup'] ?? $settings->min_topup,
            'allow_transfer' => array_key_exists('allow_transfer', $data)
                ? (bool) $data['allow_transfer']
                : $settings->allow_transfer,
        ]);

        return $settings->fresh();
    }

    private static function resolveSekolahId(?int $sekolahId): int
    {
        if ($sekolahId !== null) {
            return $sekolahId;
        }

        return AdminSchoolScope::resolveForStore();
    }
}
