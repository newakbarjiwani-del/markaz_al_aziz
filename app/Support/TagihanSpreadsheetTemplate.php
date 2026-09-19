<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagihanSpreadsheetTemplate
{
    public const TEMPLATE_VERSION = '20260714a';

    public const EMPTY_DATA_ROWS = 200;

    /** @var list<string> */
    public const COLUMNS = [
        'NIS',
        'TAGIHAN',
        'JENIS_TAGIHAN',
        'PERIODE',
        'TAHUN_AKADEMIK',
    ];

    /** @var list<list<string>> */
    private const EXAMPLE_ROWS = [
        [
            '1000001',
            '500000',
            'SPP',
            '2025-07',
            '2025/2026',
        ],
        [
            '512336041084210044',
            '750000',
            'Uang Gedung',
            '2026-01',
            '2025/2026',
        ],
    ];

    public static function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Input Tagihan');

        $columnCount = count(self::COLUMNS);
        $lastColumn = self::columnLetter($columnCount);

        $sheet->fromArray(self::COLUMNS, null, 'A1');

        foreach (self::EXAMPLE_ROWS as $rowOffset => $row) {
            $excelRow = $rowOffset + 2;
            foreach ($row as $colOffset => $value) {
                $col = $colOffset + 1;
                if ($col === 1) {
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
                if ($col === 1) {
                    $sheet->setCellValueExplicit([$col, $row], '', DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$col, $row], '');
                }
            }
        }

        $sheet->getStyle('A2:A'.$lastDataRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

        self::styleHeaderRow($sheet, $lastColumn);
        self::styleExampleRows($sheet, $lastColumn, count(self::EXAMPLE_ROWS));
        self::setColumnWidths($sheet);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.'1');

        return $spreadsheet;
    }

    public static function downloadResponse(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $writer = new Xlsx(self::buildSpreadsheet());
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Format Input Tagihan.xlsx"',
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

        return match ($normalized) {
            'NIS', 'NO_NIS' => 'NIS',
            'TAGIHAN', 'BILL', 'NOMINAL', 'JUMLAH', 'AMOUNT' => 'TAGIHAN',
            'JENIS_TAGIHAN', 'JENIS', 'JENIS_TAGIHAN_(NAMA)', 'NAMA_JENIS_TAGIHAN' => 'JENIS_TAGIHAN',
            'PERIODE', 'BULAN', 'PERIODE_TAGIHAN' => 'PERIODE',
            'TAHUN_AKADEMIK', 'TAHUN_AJARAN', 'TA', 'TAHUN' => 'TAHUN_AKADEMIK',
            default => $normalized,
        };
    }

    /** @param array<string, string> $record */
    public static function normalizedNis(array $record): string
    {
        return VirtualAccountNumber::normalizeNis($record['NIS'] ?? '') ?? '';
    }

    /** @param array<string, string> $record */
    public static function normalizedAmount(array $record): ?int
    {
        $raw = trim($record['TAGIHAN'] ?? '');

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        return (int) $digits;
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
            'A' => 18,
            'B' => 16,
            'C' => 24,
            'D' => 14,
            'E' => 16,
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
