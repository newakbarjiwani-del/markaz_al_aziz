<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\KalenderPendidikan;
use App\Models\Kurikulum;
use App\Models\MataPelajaran;
use App\Models\NilaiEntry;
use App\Models\Rapor;
use App\Support\AdminSchoolScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('akademik.view');

        $mapelQuery = MataPelajaran::query();
        AdminSchoolScope::applyWithGlobal($mapelQuery);

        $kurikulumQuery = Kurikulum::query();
        AdminSchoolScope::applyWithGlobal($kurikulumQuery);

        $jadwalQuery = JadwalPelajaran::query();
        AdminSchoolScope::applyWithGlobal($jadwalQuery);

        $kalenderQuery = KalenderPendidikan::query();
        AdminSchoolScope::applyWithGlobal($kalenderQuery);

        $nilaiQuery = NilaiEntry::query();
        AdminSchoolScope::applyRelation($nilaiQuery, 'siswa');

        $raporQuery = Rapor::query();
        AdminSchoolScope::applyRelation($raporQuery, 'siswa');

        return view('admin.akademik.dashboard', [
            'title' => 'Dashboard Akademik',
            'stats' => [
                ['label' => 'Mata Pelajaran', 'value' => $mapelQuery->count(), 'accent' => 'primary'],
                ['label' => 'Kurikulum', 'value' => $kurikulumQuery->count(), 'accent' => 'info'],
                ['label' => 'Jadwal Pelajaran', 'value' => $jadwalQuery->count(), 'accent' => 'purple'],
                ['label' => 'Kalender', 'value' => $kalenderQuery->count(), 'accent' => 'warning'],
                ['label' => 'Entri Nilai', 'value' => $nilaiQuery->count(), 'accent' => 'accent'],
                ['label' => 'Rapor Final', 'value' => (clone $raporQuery)->where('status', Rapor::STATUS_FINAL)->count(), 'accent' => 'green'],
                ['label' => 'Rapor Draft', 'value' => (clone $raporQuery)->where('status', Rapor::STATUS_DRAFT)->count(), 'accent' => 'neutral'],
            ],
        ]);
    }
}
