<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherSpreadsheetTemplate
{
    /** @var list<string> */
    public const COLUMNS = [
        'NIP',
        'Nama Guru',
        'Jabatan',
        'Jenis Guru',
        'Golongan',
        'Telepon',
        'Status',
    ];

    public static function templatePath(): string
    {
        $public = public_path('templates/format-input-guru.xlsx');

        if (is_file($public)) {
            return $public;
        }

        return base_path('Format Input Guru.xlsx');
    }

    /** @return list<array<string, string>> */
    public static function parseRows(?string $path = null): array
    {
        $path ??= self::templatePath();

        if (! is_file($path)) {
            return [];
        }

        $sheet = IOFactory::load($path)->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return [];
        }

        $headers = array_map(
            fn ($value) => self::normalizeHeader((string) $value),
            array_shift($rows) ?? []
        );

        $records = [];

        foreach ($rows as $row) {
            $firstCell = strtoupper(trim((string) ($row[0] ?? '')));
            if (str_starts_with($firstCell, 'PETUNJUK')) {
                break;
            }

            $record = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $record[$header] = trim((string) ($row[$index] ?? ''));
            }

            if (self::isBlankRecord($record)) {
                continue;
            }

            $records[] = $record;
        }

        return $records;
    }

    /** @param array<string, string> $record */
    public static function isBlankRecord(array $record): bool
    {
        foreach ($record as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    public static function normalizeHeader(string $header): string
    {
        $normalized = strtoupper(trim($header));

        return match (true) {
            $normalized === 'NIP' => 'NIP',
            str_contains($normalized, 'NAMA') => 'NAMA',
            str_contains($normalized, 'JABATAN') => 'JABATAN',
            str_contains($normalized, 'JENIS') => 'JENIS_GURU',
            str_contains($normalized, 'GOLONGAN') => 'GOLONGAN',
            str_contains($normalized, 'TELEPON') || str_contains($normalized, 'PHONE') => 'TELEPON',
            str_contains($normalized, 'STATUS') => 'STATUS',
            default => $normalized,
        };
    }

    /** @param array<string, string> $record */
    public static function normalizedStatus(array $record): string
    {
        $status = strtolower(trim($record['STATUS'] ?? 'aktif'));

        if ($status === '') {
            return 'aktif';
        }

        if (! in_array($status, ['aktif', 'nonaktif'], true)) {
            throw new \InvalidArgumentException('Status harus aktif atau nonaktif.');
        }

        return $status;
    }

    /** @param array<string, string> $record */
    public static function normalizedPhone(array $record): ?string
    {
        $phone = preg_replace('/\D/', '', $record['TELEPON'] ?? '') ?? '';

        return $phone !== '' ? $phone : null;
    }
}
