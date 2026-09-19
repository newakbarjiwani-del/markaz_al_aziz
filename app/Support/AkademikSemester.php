<?php

namespace App\Support;

final class AkademikSemester
{
    public const GANJIL = 'ganjil';

    public const GENAP = 'genap';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::GANJIL => 'Ganjil',
            self::GENAP => 'Genap',
        ];
    }

    public static function label(?string $value): string
    {
        return self::labels()[$value] ?? (string) $value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::labels());
    }
}
