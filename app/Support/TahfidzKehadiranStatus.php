<?php

namespace App\Support;

final class TahfidzKehadiranStatus
{
    public const HADIR = 'hadir';

    public const SAKIT = 'sakit';

    public const PULANG = 'pulang';

    public const ALPHA = 'alpha';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::HADIR => 'Hadir',
            self::SAKIT => 'Sakit',
            self::PULANG => 'Pulang',
            self::ALPHA => '-',
        ];
    }

    public static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return self::labels()[$value] ?? (string) $value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @param  array<string, string>  $harian
     * @return array{hadir_hari: int, sakit_hari: int, pulang_hari: int}
     */
    public static function totals(array $harian): array
    {
        $hadir = 0;
        $sakit = 0;
        $pulang = 0;

        foreach ($harian as $status) {
            if ($status === self::HADIR) {
                $hadir++;
            } elseif ($status === self::SAKIT) {
                $sakit++;
            } elseif ($status === self::PULANG) {
                $pulang++;
            }
        }

        return [
            'hadir_hari' => $hadir,
            'sakit_hari' => $sakit,
            'pulang_hari' => $pulang,
        ];
    }
}
