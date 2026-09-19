<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class HukumanStatus
{
    public const MENUNGGU = 0;

    public const DITERBITKAN = 1;

    public const SELESAI = 2;

    public const DIBATALKAN = 3;

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::MENUNGGU => 'Menunggu',
            self::DITERBITKAN => 'Diterbitkan',
            self::SELESAI => 'Selesai',
            self::DIBATALKAN => 'Dibatalkan',
        ];
    }

    public static function label(int|string|null $status): string
    {
        $code = self::normalize($status);

        if ($code === null) {
            return '-';
        }

        return self::labels()[$code] ?? (string) $code;
    }

    public static function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $code = (int) $value;

            return array_key_exists($code, self::labels()) ? $code : null;
        }

        $text = strtolower(trim((string) $value));

        foreach (self::labels() as $code => $label) {
            if ($text === strtolower($label)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return ['required', Rule::in(array_keys(self::labels()))];
    }
}
