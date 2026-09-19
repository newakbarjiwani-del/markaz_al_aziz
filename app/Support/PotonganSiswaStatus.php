<?php

namespace App\Support;

final class PotonganSiswaStatus
{
    public const ACTIVE = 'aktif';

    public const INACTIVE = 'nonaktif';

    public const EXHAUSTED = 'habis';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ACTIVE => 'Aktif',
            self::INACTIVE => 'Nonaktif',
            self::EXHAUSTED => 'Habis',
        ];
    }

    public static function label(?string $value): string
    {
        return self::labels()[self::normalize($value)] ?? (string) $value;
    }

    public static function normalize(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, [self::ACTIVE, self::INACTIVE, self::EXHAUSTED], true)
            ? $normalized
            : self::ACTIVE;
    }

    /**
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'in:'.self::ACTIVE.','.self::INACTIVE.','.self::EXHAUSTED];
    }
}
