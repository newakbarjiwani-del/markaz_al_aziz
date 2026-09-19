<?php

namespace App\Services;

use App\Models\PrestasiSiswa;

class PrestasiSpreadsheetImporter extends SiswaCatatanSpreadsheetImporter
{
    protected function modelClass(): string
    {
        return PrestasiSiswa::class;
    }

    public function label(): string
    {
        return 'Prestasi';
    }
}
