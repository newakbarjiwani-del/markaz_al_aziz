<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\BukuLookupController as BaseController;
use App\Http\Controllers\Portal\Perpustakaan\Concerns\ScopedToLibrarySekolah;

class BukuLookupController extends BaseController
{
    use ScopedToLibrarySekolah;

    protected function scopedSekolahId(): ?int
    {
        return $this->librarySekolahId();
    }
}
