<?php

namespace App\Support;

final class InfaqTiers
{
    public static function mode(): string
    {
        return (string) config('finance.infaq_mode', 'off');
    }

    public static function isEnabled(): bool
    {
        return static::mode() !== 'off';
    }

    public static function isOptional(): bool
    {
        return static::mode() === 'optional';
    }

    public static function max(): int
    {
        return (int) config('finance.infaq_max', 10000);
    }

    /**
     * @return list<array{min: int, max: int|null, amount: int}>
     */
    public static function tiers(): array
    {
        return config('finance.infaq_tiers', []);
    }

    /**
     * Calculate infaq amount for a given transfer amount.
     */
    public static function calculate(int $amount): int
    {
        if (! static::isEnabled()) {
            return 0;
        }

        $max = static::max();

        foreach (static::tiers() as $tier) {
            $min = (int) $tier['min'];
            $tierMax = $tier['max'] !== null ? (int) $tier['max'] : PHP_INT_MAX;
            $tierAmount = (int) $tier['amount'];

            if ($amount >= $min && $amount <= $tierMax) {
                return min($tierAmount, $max);
            }
        }

        return 0;
    }

    /**
     * Return all tiers with the max cap applied, for UI display.
     *
     * @return list<array{min: int, max: int|null, amount: int}>
     */
    public static function allTiers(): array
    {
        $max = static::max();

        return array_map(
            fn (array $tier) => [
                'min' => (int) $tier['min'],
                'max' => $tier['max'] !== null ? (int) $tier['max'] : null,
                'amount' => min((int) $tier['amount'], $max),
            ],
            static::tiers()
        );
    }
}
