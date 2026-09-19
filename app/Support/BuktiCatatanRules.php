<?php

namespace App\Support;

final class BuktiCatatanRules
{
    private const IMAGE_MIMES = 'jpeg,jpg,png,webp';
    private const PDF_MIMES = 'pdf';
    private const IMAGE_MAX_KB = 1024;
    private const PDF_MAX_KB = 2048;
    private const MAX_FILES = 10;

    public static function rules(string $field = 'bukti'): array
    {
        return [
            "{$field}" => ['nullable', 'array', 'max:'.self::MAX_FILES],
            "{$field}.*" => [
                'file',
                'max:'.self::PDF_MAX_KB,
                'mimes:'.self::IMAGE_MIMES.','.self::PDF_MIMES,
            ],
        ];
    }

    public static function messages(string $field = 'bukti'): array
    {
        return [
            "{$field}.max" => 'Maksimal '.self::MAX_FILES.' file bukti.',
            "{$field}.*.max" => 'Ukuran file maksimal 2 MB untuk PDF atau 1 MB untuk gambar.',
            "{$field}.*.mimes" => 'Format file bukti harus gambar (JPEG, PNG, WebP) atau PDF.',
        ];
    }

    public static function validateFile(\Illuminate\Http\UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $file->getSize() <= self::IMAGE_MAX_KB * 1024;
        }

        if ($extension === 'pdf') {
            return $file->getSize() <= self::PDF_MAX_KB * 1024;
        }

        return false;
    }
}
