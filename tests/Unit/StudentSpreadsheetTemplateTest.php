<?php

use App\Support\StudentSpreadsheetTemplate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

uses(TestCase::class);

test('student spreadsheet template defines expected columns', function () {
    expect(StudentSpreadsheetTemplate::COLUMNS)->toBe([
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
    ]);
});

test('generated student template has complete header row and empty data area', function () {
    $path = StudentSpreadsheetTemplate::generateTemplateFile();
    $sheet = IOFactory::load($path)->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:M1', null, true, true, false)[0];

    expect($headers)->toBe(StudentSpreadsheetTemplate::COLUMNS)
        ->and(StudentSpreadsheetTemplate::parseRows($path))->toHaveCount(2);
});

test('student spreadsheet template parses uploaded rows with extended columns', function () {
    $path = StudentSpreadsheetTemplate::generateTemplateFile();
    $sheet = IOFactory::load($path)->getActiveSheet();
    $sheet->fromArray([
        '9001001',
        'AHMAD IMPORT',
        'NODAF001',
        'MA',
        '12',
        '21-AN-NAWAWI',
        '2025/2026',
        'L',
        'Pekanbaru',
        '2010-01-15',
        'Jl. Contoh',
        'WALI',
        'aktif',
    ], null, 'A4');
    (new Xlsx($sheet->getParent()))->save($path);

    $rows = StudentSpreadsheetTemplate::parseRows($path);

    expect($rows)->toHaveCount(3)
        ->and($rows[2])->toHaveKeys(['NIS', 'NAMA', 'TEMPAT_LAHIR', 'TANGGAL_LAHIR', 'STATUS'])
        ->and(StudentSpreadsheetTemplate::classNameFromRow($rows[2]))->toBe('XII 21-AN-NAWAWI');
});
