<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Pelanggaran severity level (jenis_pelanggaran.level) — string codes.
 *
 * - ringan : Pelanggaran Ringan
 * - sedang : Pelanggaran Sedang
 * - berat  : Pelanggaran Berat
 */
final class PelanggaranLevel
{
    public const RINGAN = 'ringan';

    public const SEDANG = 'sedang';

    public const BERAT = 'berat';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::RINGAN => 'Ringan',
            self::SEDANG => 'Sedang',
            self::BERAT => 'Berat',
        ];
    }

    public static function label(string|int|null $level): string
    {
        $code = self::normalize($level);

        if ($code === null) {
            return '-';
        }

        return self::labels()[$code] ?? (string) $code;
    }

    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = strtolower(trim((string) $value));

        return array_key_exists($key, self::labels()) ? $key : null;
    }

    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return ['required', Rule::in(array_keys(self::labels()))];
    }
}
