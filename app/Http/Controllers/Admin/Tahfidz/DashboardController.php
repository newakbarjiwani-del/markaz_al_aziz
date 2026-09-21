<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzHalaqohAnggota;
use App\Models\TahfidzRekap;
use App\Services\TahfidzRekapWhatsAppService;
use App\Support\AdminSchoolScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TahfidzRekapWhatsAppService $whatsAppService) {}

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $halaqoh = TahfidzHalaqoh::query();
        AdminSchoolScope::apply($halaqoh);

        $anggotaQuery = TahfidzHalaqohAnggota::query()->with('siswa.orangTua');
        AdminSchoolScope::applyRelation($anggotaQuery, 'halaqoh');
        $anggota = $anggotaQuery->get();

        $rekapMingguIni = TahfidzRekap::query()
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString());
        AdminSchoolScope::apply($rekapMingguIni);

        $missingPhone = $anggota->filter(
            fn (TahfidzHalaqohAnggota $row) => $this->whatsAppService->resolvePhone($row->siswa) === null
        )->count();

        return view('admin.tahfidz.dashboard', [
            'title' => 'Dashboard Tahfidz',
            'stats' => [
                [
                    'label' => 'Halaqoh',
                    'value' => (clone $halaqoh)->count(),
                    'accent' => 'primary',
                ],
                [
                    'label' => 'Anggota Halaqoh',
                    'value' => $anggota->count(),
                    'accent' => 'green',
                ],
                [
                    'label' => 'Rekap minggu ini',
                    'value' => (clone $rekapMingguIni)->count(),
                    'accent' => 'primary',
                ],
                [
                    'label' => 'Tanpa nomor WA wali',
                    'value' => $missingPhone,
                    'accent' => 'warning',
                ],
            ],
        ]);
    }
}
