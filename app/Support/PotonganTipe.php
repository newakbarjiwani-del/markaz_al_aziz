<?php

namespace App\Support;

final class PotonganTipe
{
    public const PERCENT = 'percent';

    public const FIXED = 'fixed';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PERCENT => 'Persen',
            self::FIXED => 'Nominal tetap',
        ];
    }

    public static function label(?string $value): string
    {
        return self::labels()[self::normalize($value)] ?? (string) $value;
    }

    public static function normalize(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, [self::PERCENT, self::FIXED], true)
            ? $normalized
            : self::PERCENT;
    }

    /**
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'in:'.self::PERCENT.','.self::FIXED];
    }
}
