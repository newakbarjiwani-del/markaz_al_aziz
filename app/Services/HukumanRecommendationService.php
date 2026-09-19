<?php

namespace App\Services;

use App\Models\PelanggaranSiswa;
use App\Models\Siswa;
use App\Support\AdminSchoolScope;
use App\Support\PelanggaranSanction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HukumanRecommendationService
{
    public function minPoints(): int
    {
        return max(0, (int) config('prestasi-pelanggaran.hukuman_min_points', 250));
    }

    public function totalPointsForSiswa(int $siswaId): int
    {
        return (int) PelanggaranSiswa::query()
            ->activePoints()
            ->where('siswa_id', $siswaId)
            ->sum('point');
    }

    public function isEligible(int $siswaId, ?int $totalPoint = null): bool
    {
        $total = $totalPoint ?? $this->totalPointsForSiswa($siswaId);

        return $total >= $this->minPoints();
    }

    /**
     * Students whose SUM(unpunished pelanggaran point) meets the hukuman threshold.
     */
    public function eligibleSiswaQuery(): Builder
    {
        $min = $this->minPoints();

        $query = Siswa::query()
            ->select('siswa.id', 'siswa.name', 'siswa.nis', 'siswa.kelas_id', 'siswa.sekolah_id')
            ->selectRaw('COALESCE(SUM(pelanggaran_siswa.point), 0) as total_point')
            ->selectRaw('COUNT(pelanggaran_siswa.id) as total_records')
            ->join('pelanggaran_siswa', function ($join) {
                $join->on('pelanggaran_siswa.siswa_id', '=', 'siswa.id')
                    ->where('pelanggaran_siswa.is_punished', false)
                    ->whereNull('pelanggaran_siswa.deleted_at');
            })
            ->groupBy('siswa.id', 'siswa.name', 'siswa.nis', 'siswa.kelas_id', 'siswa.sekolah_id')
            ->havingRaw('COALESCE(SUM(pelanggaran_siswa.point), 0) >= ?', [$min]);

        AdminSchoolScope::apply($query, 'siswa.sekolah_id');

        return $query;
    }

    /**
     * Build recommendation payload for issuing punishment to a student.
     *
     * @return array{
     *     siswa_id: int,
     *     siswa_name: ?string,
     *     siswa_nis: ?string,
     *     siswa_kelas: ?string,
     *     total_point: int,
     *     min_points: int,
     *     eligible: bool,
     *     recommended_sanction: ?string,
     *     recommended_label: string,
     *     violations: list<array{
     *         id: int,
     *         judul: string,
     *         tanggal: string,
     *         point: int,
     *         jenis_nama: ?string,
     *         sanction: ?string,
     *         sanction_label: string
     *     }>
     * }
     */
    public function recommendForSiswa(Siswa $siswa, ?array $pelanggaranIds = null): array
    {
        $query = PelanggaranSiswa::query()
            ->activePoints()
            ->with('jenisPelanggaran:id,nama,sanction')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($pelanggaranIds !== null) {
            $query->whereIn('id', $pelanggaranIds);
        }

        $violations = $query->get();

        $totalPoint = (int) $violations->sum('point');
        // Eligibility always uses all-time unpunished total, not the filtered subset.
        $allTimeTotal = $this->totalPointsForSiswa($siswa->id);

        $sanctions = $violations
            ->map(fn (PelanggaranSiswa $row) => $row->jenisPelanggaran?->sanction)
            ->filter()
            ->values()
            ->all();

        $recommended = PelanggaranSanction::highest($sanctions);

        return [
            'siswa_id' => $siswa->id,
            'siswa_name' => $siswa->name,
            'siswa_nis' => $siswa->nis,
            'siswa_kelas' => $siswa->kelas?->name,
            'total_point' => $totalPoint,
            'available_total_point' => $allTimeTotal,
            'min_points' => $this->minPoints(),
            'eligible' => $this->isEligible($siswa->id, $allTimeTotal),
            'recommended_sanction' => $recommended,
            'recommended_label' => PelanggaranSanction::label($recommended),
            'violations' => $violations->map(fn (PelanggaranSiswa $row) => [
                'id' => $row->id,
                'judul' => $row->judul,
                'tanggal' => $row->tanggal->format('Y-m-d'),
                'point' => (int) $row->point,
                'jenis_nama' => $row->jenisPelanggaran?->nama,
                'sanction' => $row->jenisPelanggaran?->sanction,
                'sanction_label' => PelanggaranSanction::label($row->jenisPelanggaran?->sanction),
            ])->values()->all(),
        ];
    }
}
