<?php

namespace App\Support;

final class TahfidzJuzList
{
    /**
     * @return list<int>
     */
    public static function parse(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[^\d]+/', trim((string) $value)) ?: [];
        }

        $juz = [];
        foreach ($parts as $part) {
            if ($part === null || $part === '') {
                continue;
            }

            $number = (int) $part;
            if ($number >= 1 && $number <= 30) {
                $juz[] = $number;
            }
        }

        return $juz;
    }

    /**
     * @param  list<int>|null  $juz
     */
    public static function format(?array $juz): string
    {
        if ($juz === null || $juz === []) {
            return '-';
        }

        return 'Juz '.implode(',', $juz);
    }
}
