<?php

namespace App\Http\Traits;

use App\Models\Guru;
use App\Models\PengunjungPerpustakaan;
use App\Models\Siswa;
use App\Support\GuruSekolahFilter;

trait PerpustakaanPortal
{
    protected function librarySekolahId(): ?int
    {
        $sekolahId = auth()->user()?->sekolah_id;

        return $sekolahId !== null ? (int) $sekolahId : null;
    }

    protected function siswaOutsideLibraryScope(Siswa $siswa): bool
    {
        return false;
    }

    protected function guruOutsideLibraryScope(Guru $guru): bool
    {
        return false;
    }

    protected function pengunjungOutsideLibraryScope(PengunjungPerpustakaan $pengunjung): bool
    {
        return false;
    }
}
