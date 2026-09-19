<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\PeminjamanLookupController as BaseController;
use App\Http\Controllers\Portal\Perpustakaan\Concerns\ScopedToLibrarySekolah;

class PeminjamanLookupController extends BaseController
{
    use ScopedToLibrarySekolah;

    protected function scopedSekolahId(): ?int
    {
        return $this->librarySekolahId();
    }
}
