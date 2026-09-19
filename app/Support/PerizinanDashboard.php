<?php

namespace App\Support;

use App\Models\Perizinan;

class PerizinanDashboard
{
    /**
     * Get perizinan summary metrics and recent activity for Admin / Pimpinan.
     * Scoped by AdminSchoolScope automatically via Perizinan model.
     *
     * @return array{
     *     total: int,
     *     aktif: int,
     *     kembali: int,
     *     terlambat: int,
     *     pending: int,
     *     recent: \Illuminate\Database\Eloquent\Collection<int, Perizinan>
     * }
     */
    public static function summaryForAdmin(): array
    {
        $query = Perizinan::query()->with(['siswa.kelas', 'sekolah']);
        AdminSchoolScope::apply($query);

        return self::buildSummaryFromQuery($query);
    }

    /**
     * Get perizinan summary metrics for a Guru based on teaching classes or school scope.
     *
     * @param  array<int, int>|null  $kelasIds
     * @return array{
     *     total: int,
     *     aktif: int,
     *     kembali: int,
     *     terlambat: int,
     *     pending: int,
     *     recent: \Illuminate\Database\Eloquent\Collection<int, Perizinan>
     * }
     */
    public static function summaryForGuru(?array $kelasIds = null, ?int $sekolahId = null): array
    {
        $query = Perizinan::query()->with(['siswa.kelas', 'sekolah']);

        if (! empty($kelasIds)) {
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        } elseif ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        } else {
            AdminSchoolScope::apply($query);
        }

        return self::buildSummaryFromQuery($query);
    }

    /**
     * Get perizinan summary metrics for Orang Tua based on child IDs.
     *
     * @param  array<int, int>  $siswaIds
     * @return array{
     *     total: int,
     *     aktif: int,
     *     kembali: int,
     *     terlambat: int,
     *     pending: int,
     *     recent: \Illuminate\Database\Eloquent\Collection<int, Perizinan>
     * }
     */
    public static function summaryForOrangTua(array $siswaIds): array
    {
        if (empty($siswaIds)) {
            return [
                'total' => 0,
                'aktif' => 0,
                'kembali' => 0,
                'terlambat' => 0,
                'pending' => 0,
                'recent' => collect(),
            ];
        }

        $query = Perizinan::query()
            ->withoutGlobalScope(\App\Models\Scopes\OperatorSekolahScope::class)
            ->with(['siswa.kelas', 'sekolah', 'approvedBy', 'createdBy'])
            ->whereIn('siswa_id', $siswaIds);

        return self::buildSummaryFromQuery($query);
    }

    /**
     * Build summary array from given Perizinan base query.
     */
    private static function buildSummaryFromQuery(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $now = now();

        $total = (clone $query)->count();

        $aktif = (clone $query)
            ->where('status', Perizinan::STATUS_DISETUJUI)
            ->whereNull('tgl_kembali_aktual')
            ->where('tgl_sampai', '>=', $now)
            ->count();

        $kembali = (clone $query)
            ->where('status', Perizinan::STATUS_KEMBALI)
            ->count();

        $terlambat = (clone $query)->where(function ($q) use ($now) {
            $q->where('status', Perizinan::STATUS_TERLAMBAT)
                ->orWhere(function ($sq) use ($now) {
                    $sq->where('status', Perizinan::STATUS_DISETUJUI)
                        ->whereNull('tgl_kembali_aktual')
                        ->where('tgl_sampai', '<', $now);
                });
        })->count();

        $pending = (clone $query)
            ->where('status', Perizinan::STATUS_PENDING)
            ->count();

        $recent = (clone $query)
            ->latest()
            ->take(6)
            ->get();

        return [
            'total' => $total,
            'aktif' => $aktif,
            'kembali' => $kembali,
            'terlambat' => $terlambat,
            'pending' => $pending,
            'recent' => $recent,
        ];
    }
}
