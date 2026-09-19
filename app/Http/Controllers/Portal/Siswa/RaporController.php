<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Rapor;
use App\Support\AkademikSemester;
use Illuminate\View\View;

class RaporController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        $raporList = Rapor::query()
            ->with(['tahunAkademik', 'mapel.mataPelajaran'])
            ->where('siswa_id', $siswa->id)
            ->where('status', Rapor::STATUS_FINAL)
            ->orderByDesc('finalized_at')
            ->get();

        return view('portal.siswa.rapor', [
            'title' => 'Rapor Saya',
            'siswa' => $siswa,
            'raporList' => $raporList,
            'semesters' => AkademikSemester::labels(),
        ]);
    }
}
