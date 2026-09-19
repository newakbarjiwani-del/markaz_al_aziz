<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * User account status (users.status) — unsigned tinyint.
 *
 * - 0 disabled / blocked — cannot login (web, API, magic link)
 * - 1 active (default) — can login
 */
final class UserStatus
{
    public const DISABLED = 0;

    public const ACTIVE = 1;

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            self::DISABLED => 'Nonaktif',
            self::ACTIVE => 'Aktif',
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

    public static function canLogin(int|string|null $status): bool
    {
        return self::normalize($status) === self::ACTIVE;
    }

    /**
     * Normalize form/API/legacy input to a status code.
     * Accepts 0/1 and legacy labels aktif|nonaktif|active|disabled|blocked|inactive.
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
            'nonaktif', 'inactive', 'non-aktif', 'disabled', 'blocked' => self::DISABLED,
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
            'nonaktif' => self::DISABLED,
            'disabled' => self::DISABLED,
            'blocked' => self::DISABLED,
            'inactive' => self::DISABLED,
            'aktif' => self::ACTIVE,
            'active' => self::ACTIVE,
        ];
    }
}
