<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BukuSpreadsheetTemplate
{
    public const TEMPLATE_VERSION = '20260720a';

    /** @var list<string> */
    public const COLUMNS = [
        'KODE_BUKU',
        'ISBN',
        'JUDUL',
        'PENGARANG',
        'PENERBIT',
        'TAHUN_TERBIT',
        'CETAK_KE',
        'JUMLAH',
        'KEADAAN_BAIK',
        'KEADAAN_RUSAK_RINGAN',
        'KEADAAN_RUSAK_BERAT',
        'TANGGAL_PENERIMAAN',
        'SUMBER_DANA',
        'KETERANGAN',
    ];

    /** @var list<list<string>> */
    private const EXAMPLE_ROWS = [
        [
            '',
            '',
            'Pedoman Pengelolaan Perpustakaan Masjid',
            'Direktorat URAIS',
            'Kemenag RI',
            '2023',
            '3',
            '10',
            '5',
            '3',
            '2',
            '10-01-2024',
            'Bantuan Kemenag RI',
            '',
        ],
        [
            'B-001',
            '9786020001234',
            'Matematika Dasar Kelas X',
            'Budi Santoso',
            'Erlangga',
            '2022',
            '1',
            '10',
            '',
            '',
            '',
            '',
            '',
            '',
        ],
    ];

    public static function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Input Buku');

        $columnCount = count(self::COLUMNS);
        $lastColumn = self::columnLetter($columnCount);

        $sheet->fromArray(self::COLUMNS, null, 'A1');

        foreach (self::EXAMPLE_ROWS as $rowOffset => $row) {
            $excelRow = $rowOffset + 2;
            foreach ($row as $colOffset => $value) {
                $col = $colOffset + 1;
                if (in_array($col, [1, 2], true)) {
                    $sheet->setCellValueExplicit([$col, $excelRow], (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$col, $excelRow], $value);
                }
            }
        }

        $firstEmptyRow = count(self::EXAMPLE_ROWS) + 2;
        $lastDataRow = self::EMPTY_DATA_ROWS + count(self::EXAMPLE_ROWS) + 1;

        for ($row = $firstEmptyRow; $row <= $lastDataRow; $row++) {
            for ($col = 1; $col <= $columnCount; $col++) {
                if (in_array($col, [1, 2], true)) {
                    $sheet->setCellValueExplicit([$col, $row], '', DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$col, $row], '');
                }
            }
        }

        $sheet->getStyle('A2:B'.$lastDataRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

        self::styleHeaderRow($sheet, $lastColumn);
        self::styleExampleRows($sheet, $lastColumn, count(self::EXAMPLE_ROWS));
        self::setColumnWidths($sheet);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.'1');

        return $spreadsheet;
    }

    public const EMPTY_DATA_ROWS = 200;

    public static function downloadResponse(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $writer = new Xlsx(self::buildSpreadsheet());
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Format Input Buku.xlsx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /** @return list<array<string, string>> */
    public static function parseRows(?string $path = null): array
    {
        if ($path === null || ! is_file($path)) {
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
        $normalized = strtoupper(trim(preg_replace('/\s+/', '_', $header) ?? $header));

        return match (true) {
            str_contains($normalized, 'KODE') && str_contains($normalized, 'BUKU') => 'KODE_BUKU',
            $normalized === 'ISBN' => 'ISBN',
            str_contains($normalized, 'JUDUL') || str_contains($normalized, 'TITLE') => 'JUDUL',
            str_contains($normalized, 'PENULIS') || str_contains($normalized, 'PENGARANG') || str_contains($normalized, 'AUTHOR') => 'PENGARANG',
            str_contains($normalized, 'PENERBIT') || str_contains($normalized, 'PUBLISHER') => 'PENERBIT',
            str_contains($normalized, 'TAHUN') && str_contains($normalized, 'TERBIT') => 'TAHUN_TERBIT',
            str_contains($normalized, 'CETAK') => 'CETAK_KE',
            str_contains($normalized, 'KEADAAN') && str_contains($normalized, 'BAIK') => 'KEADAAN_BAIK',
            str_contains($normalized, 'RUSAK') && str_contains($normalized, 'RINGAN') => 'KEADAAN_RUSAK_RINGAN',
            str_contains($normalized, 'RUSAK') && str_contains($normalized, 'BERAT') => 'KEADAAN_RUSAK_BERAT',
            str_contains($normalized, 'TANGGAL') && str_contains($normalized, 'PENERIMAAN') => 'TANGGAL_PENERIMAAN',
            str_contains($normalized, 'SUMBER') && str_contains($normalized, 'DANA') => 'SUMBER_DANA',
            str_contains($normalized, 'KETERANGAN') || str_contains($normalized, 'CATATAN') => 'KETERANGAN',
            str_contains($normalized, 'KATEGORI') || str_contains($normalized, 'CATEGORY') => 'KATEGORI',
            str_contains($normalized, 'STOK') || str_contains($normalized, 'STOCK') || $normalized === 'JUMLAH' => 'JUMLAH',
            default => $normalized,
        };
    }

    /** @param array<string, string> $record */
    public static function normalizedJumlah(array $record): int
    {
        $raw = trim($record['JUMLAH'] ?? '');

        if ($raw === '') {
            return 0;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return $digits === '' ? 0 : (int) $digits;
    }

    /** @param array<string, string> $record */
    public static function normalizedKeadaan(array $record, string $key): ?int
    {
        $raw = trim($record[$key] ?? '');

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return $digits === '' ? 0 : (int) $digits;
    }

    /** @param array<string, string> $record */
    public static function resolveKeadaan(array $record, int $jumlah): array
    {
        $baik = self::normalizedKeadaan($record, 'KEADAAN_BAIK');
        $ringan = self::normalizedKeadaan($record, 'KEADAAN_RUSAK_RINGAN');
        $berat = self::normalizedKeadaan($record, 'KEADAAN_RUSAK_BERAT');

        if ($baik === null && $ringan === null && $berat === null) {
            return ['baik' => $jumlah, 'ringan' => 0, 'berat' => 0];
        }

        return [
            'baik' => $baik ?? 0,
            'ringan' => $ringan ?? 0,
            'berat' => $berat ?? 0,
        ];
    }

  /** @param array<string, string> $record */
    public static function parseTanggalPenerimaan(array $record): ?string
    {
        $raw = trim($record['TANGGAL_PENERIMAAN'] ?? '');

        if ($raw === '') {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        throw new \InvalidArgumentException('Format tanggal penerimaan tidak valid (gunakan dd-mm-yyyy).');
    }

    /** @param array<string, string> $record */
    public static function normalizedCetakKe(array $record): int
    {
        $raw = trim($record['CETAK_KE'] ?? '');

        if ($raw === '') {
            return 1;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return $digits === '' ? 1 : max(1, (int) $digits);
    }

    /** @param array<string, string> $record */
    public static function normalizedTahunTerbit(array $record): ?int
    {
        $raw = trim($record['TAHUN_TERBIT'] ?? '');

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return $digits === '' ? null : (int) $digits;
    }

    private static function styleExampleRows(Worksheet $sheet, string $lastColumn, int $rowCount): void
    {
        if ($rowCount < 1) {
            return;
        }

        $sheet->getStyle('A2:'.$lastColumn.($rowCount + 1))->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F4EF'],
            ],
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
        ]);
    }

    private static function styleHeaderRow(Worksheet $sheet, string $lastColumn): void
    {
        $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '436137'],
            ],
        ]);
    }

    private static function setColumnWidths(Worksheet $sheet): void
    {
        $widths = [
            'A' => 14,
            'B' => 18,
            'C' => 36,
            'D' => 22,
            'E' => 18,
            'F' => 12,
            'G' => 10,
            'H' => 8,
            'I' => 12,
            'J' => 16,
            'K' => 16,
            'L' => 16,
            'M' => 20,
            'N' => 24,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private static function columnLetter(int $columnNumber): string
    {
        $letter = '';

        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr(65 + ($columnNumber % 26)).$letter;
            $columnNumber = intdiv($columnNumber, 26);
        }

        return $letter;
    }
}
