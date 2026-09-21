<?php

namespace App\Support;

final class TahfidzHari
{
    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }

    public static function label(int $dayOfWeek): string
    {
        return self::labels()[$dayOfWeek] ?? (string) $dayOfWeek;
    }
}
