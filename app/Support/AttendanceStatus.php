<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class AttendanceStatus
{
    public const HADIR = 'hadir';

    public const TERLAMBAT = 'terlambat';

    public const IZIN = 'izin';

    public const SAKIT = 'sakit';

    public const CUTI = 'cuti';

    public const ALPHA = 'alpha';

    /** @return list<string> */
    public static function siswaManual(): array
    {
        return [
            self::HADIR,
            self::TERLAMBAT,
            self::IZIN,
            self::SAKIT,
            self::CUTI,
            self::ALPHA,
        ];
    }

    /** @return list<string> */
    public static function guruManual(): array
    {
        return [
            self::HADIR,
            self::TERLAMBAT,
            self::IZIN,
            self::SAKIT,
            self::CUTI,
            self::ALPHA,
        ];
    }

    /** @return list<string> */
    public static function absentStatuses(): array
    {
        return [
            self::IZIN,
            self::SAKIT,
            self::CUTI,
            self::ALPHA,
        ];
    }

    public static function isAbsent(?string $status): bool
    {
        return in_array($status, self::absentStatuses(), true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::HADIR => 'Hadir',
            self::TERLAMBAT => 'Terlambat',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::CUTI => 'Cuti',
            self::ALPHA => 'Alpha',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /** @return list<string> */
    public static function validationValues(): array
    {
        return [
            self::HADIR,
            self::TERLAMBAT,
            self::IZIN,
            self::SAKIT,
            self::CUTI,
            self::ALPHA,
        ];
    }

    public static function validationRule(bool $required = false): array
    {
        $rule = Rule::in(self::validationValues());

        return $required ? ['required', $rule] : ['nullable', $rule];
    }
}
