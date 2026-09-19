<?php

namespace App\Support;

class ImportSpreadsheetRules
{
    public const MAX_KB = 10240;

    public const MAX_FILE_SIZE_LABEL = '10MB';

    /** @return list<string> */
    public static function acceptedMimeTypes(): array
    {
        return [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
        ];
    }

    /** @return list<string> */
    public static function acceptedExtensions(): array
    {
        return ['xlsx', 'xls'];
    }

    /** @return array<string, list<string>> */
    public static function forField(string $field = 'file', bool $required = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:xlsx,xls',
            'extensions:xlsx,xls',
            'max:'.self::MAX_KB,
        ];

        return [$field => $rules];
    }

    /** @return array<string, string> */
    public static function messages(string $field = 'file'): array
    {
        return [
            "{$field}.required" => 'File Excel wajib diunggah.',
            "{$field}.file" => 'Unggahan harus berupa file.',
            "{$field}.mimes" => 'Format file harus Excel (.xlsx atau .xls).',
            "{$field}.extensions" => 'Ekstensi file harus .xlsx atau .xls.',
            "{$field}.max" => 'Ukuran file maksimal '.self::MAX_FILE_SIZE_LABEL.'.',
        ];
    }
}
