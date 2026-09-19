<?php

namespace App\Support;

class ProfilePhotoRules
{
    /** @return array<string, list<string>> */
    public static function forField(string $field = 'photo', bool $required = false): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpeg,jpg,png,webp',
            'max:2048',
            'dimensions:min_width=200,min_height=200,max_width=2000,max_height=2000',
        ];

        return [$field => $rules];
    }

    /** @return array<string, string> */
    public static function messages(string $field = 'photo'): array
    {
        return [
            "{$field}.required" => 'Foto profil wajib diunggah.',
            "{$field}.image" => 'File harus berupa gambar.',
            "{$field}.mimes" => 'Format foto harus JPEG, PNG, atau WebP.',
            "{$field}.max" => 'Ukuran foto maksimal 2 MB.',
            "{$field}.dimensions" => 'Foto minimal 200×200 px dan maksimal 2000×2000 px.',
        ];
    }
}
