<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;

class DisplayDate
{
    /**
     * Compact numeric date, e.g. "06/07/2026".
     */
    public static function date(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::parse($value)->format('d/m/Y');
    }

    /**
     * Alias of date() for ID cards / tight UI.
     */
    public static function shortDate(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        return self::date($value, $fallback);
    }

    /**
     * Compact datetime, e.g. "06/07/2026 11:00".
     * DataTables should send ISO via dateCell() and format on the client.
     */
    public static function datetime(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::parse($value)->format('d/m/Y H:i');
    }

    /**
     * Indonesian long datetime, e.g. "Senin, 6 Juli 2026 11:00".
     */
    public static function longDatetime(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::parse($value)->locale('id')->translatedFormat('l, j F Y H:i');
    }

    /**
     * Indonesian long date, e.g. "Senin, 6 Juli 2026".
     */
    public static function longDate(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::parse($value)->locale('id')->translatedFormat('l, j F Y');
    }

    public static function time(null|DateTimeInterface|string $value, string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        $value = trim((string) $value);
        if (preg_match('/^\d{2}:\d{2}/', $value)) {
            return substr($value, 0, 5);
        }

        return self::parse($value)->format('H:i');
    }

    /**
     * Normalize values for DataTable payloads (ISO for dates; no locale formatting).
     */
    public static function cell(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return self::parse($value)->format('Y-m-d H:i:s');
        }

        if (is_string($value) && self::isIsoDateTime($value)) {
            return self::parse($value)->format('Y-m-d H:i:s');
        }

        if (is_string($value) && self::isIsoDate($value)) {
            return $value;
        }

        return $value;
    }

    public static function isIsoDateTime(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $value);
    }

    public static function isIsoDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    private static function parse(DateTimeInterface|string $value): CarbonInterface
    {
        return $value instanceof CarbonInterface
            ? $value
            : \Carbon\Carbon::parse($value);
    }
}
