<?php

namespace App\Support;

final class TagihanPesanKategori
{
    public const BELUM_JATUH_TEMPO = 'belum_jatuh_tempo';

    public const LEWAT_JATUH_TEMPO = 'lewat_jatuh_tempo';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::BELUM_JATUH_TEMPO => 'Belum jatuh tempo',
            self::LEWAT_JATUH_TEMPO => 'Lewat jatuh tempo',
        ];
    }

    public static function label(?string $kategori): string
    {
        if ($kategori === null) {
            return '-';
        }

        return self::labels()[$kategori] ?? $kategori;
    }

    public static function normalize(mixed $value): ?string
    {
        $value = is_string($value) ? strtolower(trim($value)) : null;
        if ($value === null || $value === '') {
            return null;
        }

        return array_key_exists($value, self::labels()) ? $value : null;
    }

    /** @return list<string> */
    public static function rules(): array
    {
        return ['required', 'string', 'in:'.implode(',', array_keys(self::labels()))];
    }
}
