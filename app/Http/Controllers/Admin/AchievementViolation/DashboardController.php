<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Models\PrestasiSiswa;
use App\Models\PelanggaranSiswa;
use App\Models\PrestasiGuru;
use App\Models\PelanggaranGuru;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $prestasiSiswaCount = PrestasiSiswa::count();
        $pelanggaranSiswaCount = PelanggaranSiswa::count();
        $prestasiGuruCount = PrestasiGuru::count();
        $pelanggaranGuruCount = PelanggaranGuru::count();

        $recentPrestasiSiswa = PrestasiSiswa::with('siswa.kelas')->latest()->limit(5)->get();
        $recentPelanggaranSiswa = PelanggaranSiswa::with('siswa.kelas')->latest()->limit(5)->get();
        $recentPrestasiGuru = PrestasiGuru::with('guru')->latest()->limit(5)->get();
        $recentPelanggaranGuru = PelanggaranGuru::with('guru')->latest()->limit(5)->get();

        return view('admin.prestasi-pelanggaran.dashboard', [
            'title' => '',
            'stats' => [
                ['label' => 'Prestasi Siswa', 'value' => $prestasiSiswaCount, 'accent' => 'green', 'icon' => 'award'],
                ['label' => 'Pelanggaran Siswa', 'value' => $pelanggaranSiswaCount, 'accent' => 'red', 'icon' => 'gavel'],
                ['label' => 'Prestasi Guru', 'value' => $prestasiGuruCount, 'accent' => 'green', 'icon' => 'award'],
                ['label' => 'Pelanggaran Guru', 'value' => $pelanggaranGuruCount, 'accent' => 'red', 'icon' => 'gavel'],
            ],
            'recentPrestasiSiswa' => $recentPrestasiSiswa,
            'recentPelanggaranSiswa' => $recentPelanggaranSiswa,
            'recentPrestasiGuru' => $recentPrestasiGuru,
            'recentPelanggaranGuru' => $recentPelanggaranGuru,
            'topPrestasiSiswa' => $this->topRanked(PrestasiSiswa::class, 'siswa_id', 'siswa.kelas', fn ($r) => [
                'name' => $r->siswa?->name ?? '-',
                'subtitle' => $r->siswa?->kelas?->name ?? '',
            ]),
            'topPelanggaranSiswa' => $this->topRanked(PelanggaranSiswa::class, 'siswa_id', 'siswa.kelas', fn ($r) => [
                'name' => $r->siswa?->name ?? '-',
                'subtitle' => $r->siswa?->kelas?->name ?? '',
            ]),
            'topPrestasiGuru' => $this->topRanked(PrestasiGuru::class, 'guru_id', 'guru', fn ($r) => [
                'name' => $r->guru?->name ?? '-',
                'subtitle' => $r->guru?->nip ?? '',
            ]),
            'topPelanggaranGuru' => $this->topRanked(PelanggaranGuru::class, 'guru_id', 'guru', fn ($r) => [
                'name' => $r->guru?->name ?? '-',
                'subtitle' => $r->guru?->nip ?? '',
            ]),
        ]);
    }

    /**
     * Top 5 by record count, grouped per entity, normalized for the ranking panels.
     *
     * @param  class-string  $model
     * @return array<int, array{name: string, subtitle: string, total: int, total_point: int}>
     */
    private function topRanked(string $model, string $entityFk, string $with, callable $resolve): array
    {
        return $model::query()
            ->select($entityFk)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(point), 0) as total_point')
            ->with($with)
            ->whereNotNull($entityFk)
            ->groupBy($entityFk)
            ->orderByDesc('total')
            ->orderByDesc('total_point')
            ->limit(5)
            ->get()
            ->map(fn ($record) => array_merge($resolve($record), [
                'total' => (int) $record->total,
                'total_point' => (int) $record->total_point,
            ]))
            ->values()
            ->all();
    }
}
