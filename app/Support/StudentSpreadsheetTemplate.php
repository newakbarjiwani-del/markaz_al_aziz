<?php

namespace App\Support;

use App\Models\Siswa;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentSpreadsheetTemplate
{
    public const TEMPLATE_VERSION = '20260714a';

    public const EMPTY_DATA_ROWS = 200;

    /** @var list<string> */
    public const COLUMNS = [
        'NIS',
        'Nama',
        'NODAF',
        'UNIT',
        'KELAS',
        'KELOMPOK',
        'ANGKATAN',
        'GENDER',
        'TEMPAT_LAHIR',
        'TANGGAL_LAHIR',
        'ALAMAT',
        'WALI',
        'STATUS',
    ];

    /** @var list<list<string>> */
    private const EXAMPLE_ROWS = [
        [
            '1000001',
            'Ahmad Fauzi',
            'NODAF001',
            'MA',
            '10',
            '01-IBNU HAJAR',
            '2026/2027',
            'L',
            'Serang',
            '2010-05-15',
            'Jl. Raya Serang No. 10',
            'Bapak Fauzi',
            'aktif',
        ],
        [
            '512336041084210044',
            'Siti Aisyah',
            'NODAF002',
            'MA',
            '10',
            '02-IMAM GHOJALI',
            '2026/2027',
            'P',
            'Cilegon',
            '2011-08-20',
            'Jl. Merdeka Cilegon No. 5',
            'Ibu Aisyah',
            'aktif',
        ],
    ];

    public static function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Format Input Siswa');

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

        // Keep long NIS as text so Excel does not convert to scientific notation.
        $sheet->getStyle('A2:A'.$lastDataRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

        self::styleHeaderRow($sheet, $lastColumn);
        self::styleExampleRows($sheet, $lastColumn, count(self::EXAMPLE_ROWS));
        self::setColumnWidths($sheet, $lastColumn);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.'1');

        return $spreadsheet;
    }

    public static function generateTemplateFile(): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template-siswa-').'.xlsx';
        (new Xlsx(self::buildSpreadsheet()))->save($tempPath);

        return $tempPath;
    }

    public static function downloadResponse(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $writer = new Xlsx(self::buildSpreadsheet());
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Format Input Siswa.xlsx"',
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
        return match (strtoupper(trim($header))) {
            'NIS' => 'NIS',
            'NAMA' => 'NAMA',
            'NODAF' => 'NODAF',
            'UNIT' => 'UNIT',
            'KELAS' => 'KELAS',
            'KELOMPOK' => 'KELOMPOK',
            'ANGKATAN' => 'ANGKATAN',
            'GENDER' => 'GENDER',
            'TEMPAT LAHIR', 'TEMPAT_LAHIR', 'TEMPATLAHIR' => 'TEMPAT_LAHIR',
            'TANGGAL LAHIR', 'TANGGAL_LAHIR', 'TGLLAHIR', 'TGL_LAHIR' => 'TANGGAL_LAHIR',
            'ALAMAT' => 'ALAMAT',
            'WALI' => 'WALI',
            'STATUS' => 'STATUS',
            default => strtoupper(trim($header)),
        };
    }

    public static function jenjangLabel(int $kelas): string
    {
        return match ($kelas) {
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            default => $kelas > 0 ? 'KELAS '.$kelas : 'KELAS',
        };
    }

    /** @param array<string, string> $record */
    public static function classNameFromRow(array $record): string
    {
        $kelasNumber = (int) ($record['KELAS'] ?? 0);
        $kelasKelompok = trim($record['KELOMPOK'] ?? '');

        if ($kelasKelompok === '') {
            return self::jenjangLabel($kelasNumber);
        }

        return trim(self::jenjangLabel($kelasNumber).' '.$kelasKelompok);
    }

    /** @param array<string, string> $record */
    public static function normalizedNis(array $record): string
    {
        return VirtualAccountNumber::normalizeNis($record['NIS'] ?? '') ?? '';
    }

    /** @param array<string, string> $record */
    public static function normalizedGender(array $record): string
    {
        $gender = strtoupper(trim($record['GENDER'] ?? 'L'));

        return in_array($gender, ['L', 'P'], true) ? $gender : 'L';
    }

    public static function schoolCodeFromKelasNumber(int $kelasNumber): ?string
    {
        return match (true) {
            $kelasNumber >= 8 && $kelasNumber <= 9 => 'mts',
            $kelasNumber >= 10 && $kelasNumber <= 12 => 'ma',
            default => null,
        };
    }

    /** @return list<string> */
    public static function exportRowFromSiswa(Siswa $siswa): array
    {
        $extra = $siswa->profil?->extra_fields ?? [];
        [$kelasNumber, $kelompok] = self::kelasPartsFromSiswa($siswa);

        return [
            $siswa->nis,
            $siswa->name,
            (string) ($siswa->nomor_pendaftaran ?? ''),
            (string) ($extra['unit'] ?? strtoupper($siswa->sekolah?->code ?? '')),
            $kelasNumber > 0 ? (string) $kelasNumber : '',
            $kelompok,
            (string) ($extra['angkatan'] ?? ''),
            $siswa->gender ?? 'L',
            $siswa->birth_place ?? '',
            $siswa->birth_date?->format('Y-m-d') ?? '',
            $siswa->address ?? '',
            (string) ($extra['nama_wali'] ?? ''),
            $siswa->status !== null
                ? SiswaStatus::exportValue($siswa->status)
                : 'aktif',
        ];
    }

    /**
     * @return array{0: int, 1: string}
     */
    public static function kelasPartsFromSiswa(Siswa $siswa): array
    {
        $extra = $siswa->profil?->extra_fields ?? [];
        $kelompok = trim((string) ($extra['kelas_kelompok'] ?? ''));
        $kelasNumber = KelasLabel::exportKelasNumber($siswa->kelas?->kelas);

        if ($kelasNumber === 0) {
            $kelasNumber = self::kelasNumberFromJenjang($siswa->kelas?->jenjang);
        }

        if ($kelasNumber === 0 && $siswa->kelas?->name) {
            $kelasNumber = self::kelasNumberFromClassName($siswa->kelas->name);
        }

        if ($kelompok === '' && $siswa->kelas?->kelompok) {
            $kelompok = trim((string) $siswa->kelas->kelompok);
        }

        if ($kelompok === '' && $siswa->kelas?->name) {
            $kelompok = self::kelompokFromClassName($siswa->kelas->name);
        }

        return [$kelasNumber, $kelompok];
    }

    public static function kelasNumberFromJenjang(?string $jenjang): int
    {
        return match (strtoupper(trim((string) $jenjang))) {
            'VIII' => 8,
            'IX' => 9,
            'X' => 10,
            'XI' => 11,
            'XII' => 12,
            default => 0,
        };
    }

    public static function kelasNumberFromClassName(string $className): int
    {
        foreach (['XII' => 12, 'XI' => 11, 'X' => 10, 'IX' => 9, 'VIII' => 8] as $prefix => $number) {
            if (str_starts_with($className, $prefix)) {
                return $number;
            }
        }

        if (preg_match('/\b(\d{1,2})\b/', $className, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    public static function kelompokFromClassName(string $className): string
    {
        foreach (['XII', 'XI', 'X', 'IX', 'VIII'] as $prefix) {
            if (str_starts_with($className, $prefix.' ')) {
                return trim(substr($className, strlen($prefix)));
            }
        }

        return '';
    }

    /**
     * @param  list<list<string>>  $rows
     */
    public static function buildDataSpreadsheet(array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $lastColumn = self::columnLetter(count(self::COLUMNS));
        $sheet->fromArray(self::COLUMNS, null, 'A1');

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        self::styleHeaderRow($sheet, $lastColumn);
        self::setColumnWidths($sheet, $lastColumn);
        $sheet->freezePane('A2');

        $lastRow = max(1, count($rows) + 1);
        $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);

        return $spreadsheet;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    public static function dataDownloadResponse(array $rows, string $filename = 'Data Siswa.xlsx'): StreamedResponse
    {
        return new StreamedResponse(function () use ($rows) {
            $writer = new Xlsx(self::buildDataSpreadsheet($rows));
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
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

    private static function setColumnWidths(Worksheet $sheet, string $lastColumn): void
    {
        $widths = [
            'A' => 18,
            'B' => 28,
            'C' => 14,
            'D' => 10,
            'E' => 8,
            'F' => 18,
            'G' => 14,
            'H' => 10,
            'I' => 16,
            'J' => 14,
            'K' => 32,
            'L' => 20,
            'M' => 10,
        ];

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth($widths[$column] ?? 14);
        }
    }

    private static function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter;
    }
}
