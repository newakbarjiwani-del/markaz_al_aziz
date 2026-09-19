<?php

namespace App\Support;

use App\Models\Siswa;
use App\Models\SpmbPendaftar;

final class SpmbRegistrationNumber
{
    public static function generate(): string
    {
        do {
            $number = sprintf('SPMB-%s-%04d', now()->format('Ymd'), random_int(0, 9999));
        } while (
            SpmbPendaftar::query()->where('nomor_pendaftaran', $number)->exists()
            || Siswa::query()->where('nomor_pendaftaran', $number)->exists()
        );

        return $number;
    }
}
