<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\ImportBukuController as BaseController;

class ImportBukuController extends BaseController
{
    protected function routePrefix(): string
    {
        return 'portal.perpustakaan.impor-buku';
    }

    /** @return array<string, mixed> */
    protected function viewData(): array
    {
        return array_merge(parent::viewData(), [
            'exportDescription' => 'Ekspor katalog buku (Excel/PDF sesuai filter) tersedia di halaman Katalog Buku.',
            'exportRoute' => route('portal.perpustakaan.katalog-buku.index'),
            'exportButtonLabel' => 'Buka Katalog Buku',
        ]);
    }
}
