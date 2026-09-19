<?php

namespace App\Support;

class TahunAkademikName
{
    public const PATTERN = '/^\d{4}\/\d{4}$/';

    public static function isValid(?string $value): bool
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match(self::PATTERN, $value) !== 1) {
            return false;
        }

        [$startYear, $endYear] = array_map('intval', explode('/', $value, 2));

        return $endYear === $startYear + 1;
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:50'];

        $rules[] = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $name = trim((string) $value);

            if (preg_match(self::PATTERN, $name) !== 1) {
                $fail('Nama tahun akademik harus berformat YYYY/YYYY (mis. 2025/2026).');

                return;
            }

            [$startYear, $endYear] = array_map('intval', explode('/', $name, 2));

            if ($endYear !== $startYear + 1) {
                $fail('Tahun akhir harus tepat satu tahun setelah tahun awal (mis. 2025/2026).');
            }
        };

        return $rules;
    }
}
