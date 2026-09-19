<?php

namespace App\Support;

use App\Models\AbsensiSiswa;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard attendance aggregates that count distinct students / days,
 * not multi-slot absensi_siswa rows.
 */
final class AttendanceDashboard
{
    public static function distinctStudentsOnDate(CarbonInterface|string|null $date = null, ?array $statuses = null): int
    {
        $query = AbsensiSiswa::query()->whereDate('date', $date ?? today());

        if ($statuses !== null) {
            $query->whereIn('status', $statuses);
        }

        return (int) $query->distinct()->count('siswa_id');
    }

    /**
     * @return array<string, int>
     */
    public static function distinctStudentsByStatus(CarbonInterface|string|null $date = null): array
    {
        return AbsensiSiswa::query()
            ->whereDate('date', $date ?? today())
            ->select('status', DB::raw('COUNT(DISTINCT siswa_id) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Distinct present days (siswa_id + date) with status hadir in the current month.
     *
     * @param  list<int>|int  $siswaIds
     */
    public static function presentDayCountThisMonth(array|int $siswaIds): int
    {
        $ids = is_array($siswaIds) ? $siswaIds : [$siswaIds];
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return 0;
        }

        $days = AbsensiSiswa::query()
            ->whereIn('siswa_id', $ids)
            ->where('status', 'hadir')
            ->where('date', '>=', now()->startOfMonth())
            ->select('siswa_id', 'date')
            ->groupBy('siswa_id', 'date');

        return (int) DB::query()->fromSub($days, 'present_days')->count();
    }
}
