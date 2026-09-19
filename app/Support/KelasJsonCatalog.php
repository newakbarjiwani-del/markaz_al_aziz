<?php

namespace App\Support;

class KelasJsonCatalog
{
    public const RELATIVE_PATH = 'database/seeders/data/kelas.json';

    /** @var array<int, string> */
    private const SERVER_SEKOLAH_ID_TO_CODE = [
        1 => 'paud',
        2 => 'mts',
        3 => 'ma',
        4 => 'takhasus',
    ];

    public static function path(): string
    {
        return base_path(self::RELATIVE_PATH);
    }

    /**
     * @return list<array{
     *     school_code: string,
     *     server_sekolah_id: int,
     *     name: string,
     *     unit: ?string,
     *     kelas: ?string,
     *     kelompok: ?string,
     *     wali_kelas: ?string,
     *     is_active: bool
     * }>
     */
    public static function rows(?string $path = null): array
    {
        $path ??= self::path();

        if (! is_file($path)) {
            return [];
        }

        $raw = json_decode((string) file_get_contents($path), true);

        if (! is_array($raw)) {
            return [];
        }

        $records = [];

        foreach ($raw as $row) {
            $serverSekolahId = (int) ($row['sekolah_id'] ?? 0);
            $schoolCode = self::SERVER_SEKOLAH_ID_TO_CODE[$serverSekolahId] ?? null;

            if ($schoolCode === null) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $parts = KelasLabel::parseClassName($name);
            $waliKelas = $row['wali_kelas'] ?? null;
            $waliKelas = is_string($waliKelas) && trim($waliKelas) !== '' && trim($waliKelas) !== '-'
                ? trim($waliKelas)
                : null;
            $unit = trim((string) ($row['unit'] ?? ''));

            $records[] = [
                'school_code' => $schoolCode,
                'server_sekolah_id' => $serverSekolahId,
                'name' => $name,
                'unit' => $unit !== '' ? $unit : null,
                'kelas' => $parts['kelas'],
                'kelompok' => $parts['kelompok'],
                'wali_kelas' => $waliKelas,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];
        }

        return $records;
    }

    /**
     * @return array<string, list<array{
     *     school_code: string,
     *     server_sekolah_id: int,
     *     name: string,
     *     unit: ?string,
     *     kelas: ?string,
     *     kelompok: ?string,
     *     wali_kelas: ?string,
     *     is_active: bool
     * }>>
     */
    public static function rowsBySchoolCode(?string $path = null): array
    {
        $grouped = [];

        foreach (self::rows($path) as $row) {
            $grouped[$row['school_code']][] = $row;
        }

        return $grouped;
    }

    public static function totalSeededClassCount(): int
    {
        return count(self::rows());
    }

    public static function escapeSql(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    public static function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return "'".self::escapeSql((string) $value)."'";
    }
}
