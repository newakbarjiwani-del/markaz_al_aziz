<?php

namespace App\Support;

/**
 * Tagihan billing period — 6-digit code: {tahun akademik akhir}{bulan}.
 *
 * Indonesian schools (non-college) use an academic year from July through June.
 * The first four digits are the **end year** of the tahun ajaran label (e.g. 2026 for
 * "2025/2026"); the last two digits are the calendar month (01–12).
 *
 * | periode | Bulan (kalender) | Tahun ajaran |
 * |---------|------------------|--------------|
 * | 202607  | Juli 2025        | 2025/2026    |
 * | 202601  | Januari 2026     | 2025/2026    |
 * | 202606  | Juni 2026        | 2025/2026    |
 * | 202707  | Juli 2026        | 2026/2027    |
 * | 202801  | Januari 2028     | 2027/2028    |
 *
 * UI month pickers use calendar year-month; this helper converts to/from storage.
 */
final class TagihanPeriode
{
    /** @var array<string, int> */
    private const MONTH_NAMES = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    public static function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return self::isValid($value) ? $value : null;
        }

        $raw = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $matches)) {
            return self::fromCalendarMonth((int) $matches[1], (int) $matches[2]);
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if (strlen($digits) === 6) {
            $periode = (int) $digits;

            return self::isValid($periode) ? $periode : null;
        }

        if (strlen($digits) === 8) {
            $periode = (int) substr($digits, 0, 6);

            return self::isValid($periode) ? $periode : null;
        }

        return null;
    }

    public static function isValid(int $periode): bool
    {
        $endYear = intdiv($periode, 100);
        $month = $periode % 100;

        return $endYear >= 2000 && $endYear <= 2100 && $month >= 1 && $month <= 12;
    }

    public static function current(): int
    {
        return self::fromCalendarMonth((int) now()->year, (int) now()->month);
    }

    /**
     * Encode a calendar month into stored periode (tahun ajaran akhir + bulan).
     */
    public static function fromCalendarMonth(int $calendarYear, int $month): int
    {
        $endYear = $month >= 7 ? $calendarYear + 1 : $calendarYear;

        return ($endYear * 100) + $month;
    }

    /**
     * @return array{0: int, 1: int} [calendarYear, month]
     */
    public static function toCalendar(int $periode): array
    {
        $endYear = intdiv($periode, 100);
        $month = $periode % 100;
        $calendarYear = $month >= 7 ? $endYear - 1 : $endYear;

        return [$calendarYear, $month];
    }

    /**
     * Academic year label (July–June), e.g. 2025/2026.
     */
    public static function academicYearLabel(int $periode): string
    {
        $endYear = intdiv($periode, 100);

        return ($endYear - 1).'/'.$endYear;
    }

    public static function display(?int $periode, bool $withAcademicYear = true): string
    {
        if ($periode === null || ! self::isValid($periode)) {
            return '-';
        }

        [$calendarYear, $month] = self::toCalendar($periode);
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $label = ($names[$month] ?? (string) $month).' '.$calendarYear;

        if ($withAcademicYear) {
            $label .= ' · TA '.self::academicYearLabel($periode);
        }

        return $label;
    }

    public static function toMonthInput(?int $periode): string
    {
        if ($periode === null || ! self::isValid($periode)) {
            return now()->format('Y-m');
        }

        [$calendarYear, $month] = self::toCalendar($periode);

        return sprintf('%04d-%02d', $calendarYear, $month);
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true): array
    {
        $rules = [$required ? 'required' : 'nullable'];

        $rules[] = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (self::normalize($value) === null) {
                $fail('Periode tidak valid. Gunakan bulan tagihan (mis. 2026-01 → 202601, 2026-07 → 202707).');
            }
        };

        return $rules;
    }
}
