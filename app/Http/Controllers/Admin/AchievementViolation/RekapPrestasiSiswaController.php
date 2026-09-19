<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\RekapSiswaCatatanData;
use App\Models\PrestasiSiswa;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapPrestasiSiswaController extends Controller
{
    use DataTableTrait;
    use RekapSiswaCatatanData;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.rekap-prestasi-siswa', [
            'title' => 'Rekap Prestasi Siswa',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('prestasi-siswa.view');

        return $this->rekapSiswaData($request, PrestasiSiswa::class, function ($row) {
            $url = route('admin.prestasi-pelanggaran.prestasi-siswa.index', ['siswa_id' => $row->siswa_id]);

            return $this->cell(
                '<a href="'.e($url).'" class="btn-secondary text-xs">Lihat Riwayat</a>',
                null,
                'html'
            );
        });
    }
}
