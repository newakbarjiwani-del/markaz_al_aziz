<?php

namespace App\Support;

class Weekday
{
    public const SENIN = 1;

    public const SELASA = 2;

    public const RABU = 3;

    public const KAMIS = 4;

    public const JUMAT = 5;

    public const SABTU = 6;

    public const MINGGU = 7;

    /** @return array<int, string> */
    public static function labels(): array
    {
        return [
            self::SENIN => 'Senin',
            self::SELASA => 'Selasa',
            self::RABU => 'Rabu',
            self::KAMIS => 'Kamis',
            self::JUMAT => 'Jumat',
            self::SABTU => 'Sabtu',
            self::MINGGU => 'Minggu',
        ];
    }

    public static function label(int $day): string
    {
        return self::labels()[$day] ?? '-';
    }

    /** @return list<int> */
    public static function numbers(): array
    {
        return array_keys(self::labels());
    }

    public static function fromDate(\DateTimeInterface $date): int
    {
        return (int) $date->format('N');
    }
}
