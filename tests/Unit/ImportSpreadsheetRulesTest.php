<?php

use App\Support\ImportSpreadsheetRules;

test('import spreadsheet rules define excel extensions and size', function () {
    expect(ImportSpreadsheetRules::acceptedExtensions())->toBe(['xlsx', 'xls']);
    expect(ImportSpreadsheetRules::MAX_KB)->toBe(10240);
    expect(ImportSpreadsheetRules::forField()['file'])->toContain('mimes:xlsx,xls');
    expect(ImportSpreadsheetRules::forField()['file'])->toContain('extensions:xlsx,xls');
});

test('import spreadsheet messages are defined for file field', function () {
    expect(ImportSpreadsheetRules::messages()['file.mimes'])
        ->toBe('Format file harus Excel (.xlsx atau .xls).');
});
