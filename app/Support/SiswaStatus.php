<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Student account status (siswa.status) — unsigned tinyint.
 *
 * Current values:
 * - 0 inactive (nonaktif) — cannot transact
 * - 1 active (aktif) — default; can transact
 * - 2 pending (menunggu) — SPMB / admission waiting verification; cannot transact
 */
final class SiswaStatus
{
    public const INACTIVE = 0;

    public const ACTIVE = 1;

    /** SPMB applicant / menunggu verifikasi. */
    public const PENDING = 2;

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::INACTIVE => 'Nonaktif',
            self::ACTIVE => 'Aktif',
            self::PENDING => 'Menunggu',
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

    /**
     * Spreadsheet / import-compatible lowercase token.
     */
    public static function exportValue(int|string|null $status): string
    {
        return match (self::normalize($status)) {
            self::INACTIVE => 'nonaktif',
            self::PENDING => 'pending',
            default => 'aktif',
        };
    }

    public static function canTransact(int|string|null $status): bool
    {
        return self::normalize($status) === self::ACTIVE;
    }

    /**
     * Normalize form/import/API input to a status code.
     * Accepts 0/1/2 and legacy labels aktif|nonaktif|pending.
     */
    public static function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $code = (int) $value;

            return array_key_exists($code, self::labels()) ? $code : null;
        }

        $key = strtolower(trim((string) $value));

        return match ($key) {
            'aktif', 'active' => self::ACTIVE,
            'nonaktif', 'inactive', 'non-aktif' => self::INACTIVE,
            'pending', 'menunggu' => self::PENDING,
            default => null,
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true): array
    {
        $rule = Rule::in(self::values());

        return $required
            ? ['required', 'integer', $rule]
            : ['nullable', 'integer', $rule];
    }

    /**
     * Map legacy varchar values for SQL / migration scripts.
     *
     * @return array<string, int>
     */
    public static function legacyMap(): array
    {
        return [
            'nonaktif' => self::INACTIVE,
            'aktif' => self::ACTIVE,
            'pending' => self::PENDING,
        ];
    }
}
