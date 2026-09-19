<?php

namespace App\Support;

class KelasLabel
{
    /** @var array<string, int> */
    private const ROMAN_LEVELS = [
        'XII' => 12,
        'XI' => 11,
        'X' => 10,
        'IX' => 9,
        'VIII' => 8,
        'VII' => 7,
        'VI' => 6,
        'V' => 5,
        'IV' => 4,
        'III' => 3,
        'II' => 2,
        'I' => 1,
    ];

    /** @var list<string> */
    private const NAMED_LEVELS = [
        'Tsaniyah',
        'Aliyah',
        'Takhossus',
    ];

    public static function displayName(?string $kelas, ?string $kelompok): string
    {
        $kelas = trim((string) $kelas);
        $kelompok = trim((string) $kelompok);

        if ($kelas !== '' && $kelompok !== '') {
            if (ctype_digit($kelas) && strlen($kelompok) === 1 && ctype_alpha($kelompok)) {
                return $kelas.$kelompok;
            }

            return $kelas.' '.$kelompok;
        }

        if ($kelas !== '') {
            return $kelas;
        }

        return $kelompok;
    }

    public static function short(?string $kelas, ?string $kelompok): string
    {
        return self::displayName($kelas, $kelompok);
    }

    public static function long(?string $kelas, ?string $kelompok): string
    {
        $parts = [];
        $kelas = trim((string) $kelas);
        $kelompok = trim((string) $kelompok);

        if ($kelas !== '') {
            if (strcasecmp($kelas, 'Kelompok') === 0) {
                $parts[] = $kelompok !== '' ? 'Kelompok '.$kelompok : 'Kelompok';
            } else {
                $parts[] = 'Kelas '.$kelas;

                if ($kelompok !== '') {
                    $parts[] = 'Kelompok '.$kelompok;
                }
            }
        } elseif ($kelompok !== '') {
            $parts[] = 'Kelompok '.$kelompok;
        }

        return implode(', ', $parts);
    }

    public static function exportKelasNumber(?string $kelas): int
    {
        $kelas = trim((string) $kelas);

        if ($kelas === '') {
            return 0;
        }

        if (isset(self::ROMAN_LEVELS[$kelas])) {
            return self::ROMAN_LEVELS[$kelas];
        }

        if (ctype_digit($kelas)) {
            return (int) $kelas;
        }

        return 0;
    }

    public static function kelasFromImportNumber(int $kelasNumber): ?string
    {
        if ($kelasNumber <= 0) {
            return null;
        }

        foreach (self::ROMAN_LEVELS as $roman => $numeric) {
            if ($numeric === $kelasNumber) {
                return $roman;
            }
        }

        return (string) $kelasNumber;
    }

    /**
     * @return array{kelas: ?string, kelompok: ?string}
     */
    public static function parseClassName(string $className): array
    {
        $className = trim($className);

        if ($className === '') {
            return ['kelas' => null, 'kelompok' => null];
        }

        if (preg_match('/^(\d{1,2})([A-Za-z].*)$/', $className, $matches) === 1) {
            return [
                'kelas' => $matches[1],
                'kelompok' => trim($matches[2]) !== '' ? trim($matches[2]) : null,
            ];
        }

        if (preg_match('/^kelompok\s+(.+)$/iu', $className, $matches) === 1) {
            return [
                'kelas' => 'Kelompok',
                'kelompok' => trim($matches[1]) !== '' ? trim($matches[1]) : null,
            ];
        }

        foreach (self::NAMED_LEVELS as $named) {
            if (strcasecmp($className, $named) === 0) {
                return ['kelas' => $named, 'kelompok' => null];
            }

            if (str_starts_with(strtoupper($className), strtoupper($named).' ')) {
                $kelompok = trim(substr($className, strlen($named)));

                return [
                    'kelas' => $named,
                    'kelompok' => $kelompok !== '' ? $kelompok : null,
                ];
            }
        }

        foreach (self::ROMAN_LEVELS as $roman => $level) {
            unset($level);

            if ($className === $roman) {
                return ['kelas' => $roman, 'kelompok' => null];
            }

            if (str_starts_with($className, $roman.' ')) {
                $kelompok = trim(substr($className, strlen($roman)));

                return [
                    'kelas' => $roman,
                    'kelompok' => $kelompok !== '' ? $kelompok : null,
                ];
            }
        }

        return ['kelas' => null, 'kelompok' => null];
    }
}
