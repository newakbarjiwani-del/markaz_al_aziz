<?php

namespace App\Support;

class PortalEntityUserRules
{
    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'siswa_id.unique' => 'Siswa ini sudah memiliki akun login.',
            'guru_id.unique' => 'Guru ini sudah memiliki akun login.',
            'orang_tua_id.unique' => 'Orang tua ini sudah memiliki akun login.',
        ];
    }
}
