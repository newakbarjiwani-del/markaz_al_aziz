<?php

namespace App\Support;

final class AlumniTracerStatus
{
    public const BEKERJA = 'bekerja';

    public const KULIAH = 'kuliah';

    public const WIRAUSAHA = 'wirausaha';

    public const MENCARI = 'mencari';

    public const LAINNYA = 'lainnya';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::BEKERJA => 'Bekerja',
            self::KULIAH => 'Kuliah / Melanjutkan studi',
            self::WIRAUSAHA => 'Wirausaha',
            self::MENCARI => 'Mencari kerja',
            self::LAINNYA => 'Lainnya',
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
