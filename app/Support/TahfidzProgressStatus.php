<?php

namespace App\Support;

final class TahfidzProgressStatus
{
    public const BELUM = 'belum';

    public const PROSES = 'proses';

    public const LANCAR = 'lancar';

    public const MUTQIN = 'mutqin';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::BELUM => 'Belum hafal',
            self::PROSES => 'Proses',
            self::LANCAR => 'Lancar',
            self::MUTQIN => 'Mutqin',
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

    public static function badgeClass(?string $value): string
    {
        return match ($value) {
            self::LANCAR => 'badge badge-green',
            self::MUTQIN => 'badge badge-blue',
            self::PROSES => 'badge badge-amber',
            default => 'badge',
        };
    }
}
