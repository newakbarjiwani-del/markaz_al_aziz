<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiGuru;
use Illuminate\View\View;

class GuruDashboardController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $guru = $this->linkedGuru();
        $scope = $this->guruTeachingScope();
        $classes = $scope->classLabels();

        $kelasIds = [];
        foreach ($scope->activeJadwal() as $jadwal) {
            if ($jadwal->isKelasAssignment()) {
                foreach ($jadwal->kelas as $kelas) {
                    $kelasIds[] = $kelas->id;
                }
            }
        }

        $perizinanSummary = \App\Support\PerizinanDashboard::summaryForGuru($kelasIds, $guru->sekolah_id);

        return view('portal.guru.dashboard', [
            'title' => '',
            'guru' => $guru,
            'classes' => $classes,
            'stats' => [
                'kelas_mengajar' => $scope->classCount(),
                'hadir_siswa_hari_ini' => $scope->todayPresentStudentCount(),
                'absensi_saya_bulan_ini' => AbsensiGuru::where('guru_id', $guru->id)
                    ->where('status', 'hadir')
                    ->where('date', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'perizinanSummary' => $perizinanSummary,
        ]);
    }
}
