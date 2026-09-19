<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\RekapSiswaCatatanData;
use App\Models\PelanggaranSiswa;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapPelanggaranSiswaController extends Controller
{
    use DataTableTrait;
    use RekapSiswaCatatanData;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.rekap-pelanggaran-siswa', [
            'title' => 'Rekap Pelanggaran Siswa',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('pelanggaran-siswa.view');

        return $this->rekapSiswaData($request, PelanggaranSiswa::class, function ($row) {
            $historyUrl = route('admin.prestasi-pelanggaran.pelanggaran-siswa.index', ['siswa_id' => $row->siswa_id]);
            $minPoints = (int) config('prestasi-pelanggaran.hukuman_min_points', 250);
            $html = '<div class="flex flex-wrap gap-1">'
                .'<a href="'.e($historyUrl).'" class="btn-secondary text-xs">Riwayat</a>';

            if (
                auth()->user()?->can('hukuman-siswa.create')
                && (int) $row->total_point >= $minPoints
            ) {
                $hukumanUrl = route('admin.prestasi-pelanggaran.hukuman-siswa.index', [
                    'siswa_id' => $row->siswa_id,
                    'open' => 'create',
                ]);
                $html .= '<a href="'.e($hukumanUrl).'" class="btn-primary text-xs">Hukuman</a>';
            }

            $html .= '</div>';

            return $this->cell($html, null, 'html');
        });
    }
}
