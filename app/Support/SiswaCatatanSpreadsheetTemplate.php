<?php

namespace App\Support;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared Excel template for importing student achievements (prestasi) and
 * violations (pelanggaran). Both use the same columns keyed by NIS.
 */
class SiswaCatatanSpreadsheetTemplate
{
    public const TEMPLATE_VERSION = '20260804a';

    public const EMPTY_DATA_ROWS = 200;

    /** @var list<string> */
    public const COLUMNS = [
        'NIS',
        'JUDUL',
        'KETERANGAN',
        'TANGGAL',
        'POINT',
    ];

    /** @var list<list<string>> */
    private const EXAMPLE_ROWS = [
        [
            '1000001',
            'Juara 1 Lomba Matematika',
            'Juara tingkat kabupaten',
            '2026-07-15',
            '10',
        ],
        [
            '512336041084210044',
            'Hafalan 5 Juz',
            'Pencapaian tahfidz',
            '15/07/2026',
            '5',
        ],
    ];

    /** @var list<string> */
    public const PELANGGARAN_COLUMNS = [
        'NIS',
        'JENIS_PELANGGARAN',
        'JUDUL',
        'KETERANGAN',
        'TANGGAL',
        'POINT',
    ];

    /** @var list<list<string>> */
    private const PELANGGARAN_EXAMPLE_ROWS = [
        [
            '1000001',
            'Terlambat Masuk Kelas',
            '',
            'Datang terlambat 15 menit',
            '2026-07-15',
            '',
        ],
        [
            '512336041084210044',
            '',
            'Tidak memakai seragam lengkap',
            'Seragam olahraga',
            '15/07/2026',
            '5',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function columnsFor(string $type = 'prestasi'): array
    {
        return $type === 'pelanggaran' ? self::PELANGGARAN_COLUMNS : self::COLUMNS;
    }

    public static function buildSpreadsheet(string $type = 'prestasi'): Spreadsheet
    {
        $columns = self::columnsFor($type);
        $exampleRows = $type === 'pelanggaran' ? self::PELANGGARAN_EXAMPLE_ROWS : self::EXAMPLE_ROWS;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Input');

        $columnCount = count($columns);
        $lastColumn = self::columnLetter($columnCount);

        $sheet->fromArray($columns, null, 'A1');

        foreach ($exampleRows as $rowOffset => $row) {
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

        $firstEmptyRow = count($exampleRows) + 2;
        $lastDataRow = self::EMPTY_DATA_ROWS + count($exampleRows) + 1;

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
        self::styleExampleRows($sheet, $lastColumn, count($exampleRows));
        self::setColumnWidths($sheet, $columnCount);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.'1');

        return $spreadsheet;
    }

    public static function downloadResponse(string $type = 'prestasi'): StreamedResponse
    {
        $filename = self::filename($type);

        return new StreamedResponse(function () use ($type) {
            $writer = new Xlsx(self::buildSpreadsheet($type));
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public static function filename(string $type = 'prestasi'): string
    {
        $label = $type === 'pelanggaran' ? 'Pelanggaran' : 'Prestasi';

        return 'Format Input '.$label.' Siswa.xlsx';
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
            'NIS', 'NO_NIS', 'NO_INDUK' => 'NIS',
            'JUDUL', 'PRESTASI', 'PELANGGARAN', 'KEGIATAN', 'NAMA_KEGIATAN', 'KETERANGAN_KEGIATAN' => 'JUDUL',
            'KETERANGAN', 'DESKRIPSI', 'CATATAN' => 'KETERANGAN',
            'TANGGAL', 'TANGGAL_KEJADIAN', 'TGL', 'TANGGAL_KEJADIAN_' => 'TANGGAL',
            'POINT', 'SKOR', 'NILAI', 'POINTS' => 'POINT',
            'JENIS_PELANGGARAN', 'JENIS', 'KATALOG', 'NAMA_JENIS' => 'JENIS_PELANGGARAN',
            default => $normalized,
        };
    }

    /** @param array<string, string> $record */
    public static function normalizedNis(array $record): string
    {
        return VirtualAccountNumber::normalizeNis($record['NIS'] ?? '') ?? '';
    }

    /** @param array<string, string> $record */
    public static function normalizedTanggal(array $record): ?string
    {
        $raw = trim((string) ($record['TANGGAL'] ?? ''));

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))
                    ->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $raw);
                if ($date !== false && $date->format($format) === $raw) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, string> $record */
    public static function normalizedPoint(array $record): ?int
    {
        $raw = trim($record['POINT'] ?? '');

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

    private static function setColumnWidths(Worksheet $sheet, int $columnCount = 5): void
    {
        $widths = array_fill(0, $columnCount, 18);
        $widths[1] = 28;
        if ($columnCount >= 3) {
            $widths[2] = 42;
        }
        if ($columnCount >= 4) {
            $widths[3] = 42;
        }

        for ($i = 0; $i < $columnCount; $i++) {
            $sheet->getColumnDimension(self::columnLetter($i + 1))->setWidth($widths[$i] ?? 16);
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
