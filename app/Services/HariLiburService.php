<?php

namespace App\Services;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class HariLiburService
{
    public function findForDate(string $date, ?int $sekolahId, string $scope = HariLibur::APPLIES_BOTH): ?HariLibur
    {
        return HariLibur::query()
            ->whereDate('date', $date)
            ->where(function ($query) use ($sekolahId) {
                $query->whereNull('sekolah_id');
                if ($sekolahId) {
                    $query->orWhere('sekolah_id', $sekolahId);
                }
            })
            ->where(function ($query) use ($scope) {
                $query->where('applies_to', HariLibur::APPLIES_BOTH)
                    ->orWhere('applies_to', $scope);
            })
            ->orderByRaw('CASE WHEN sekolah_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    public function isLibur(string $date, ?int $sekolahId, string $scope = HariLibur::APPLIES_BOTH): bool
    {
        return $this->findForDate($date, $sekolahId, $scope) !== null;
    }

    public function assertNotLibur(string $date, ?int $sekolahId, string $scope = HariLibur::APPLIES_BOTH): void
    {
        $libur = $this->findForDate($date, $sekolahId, $scope);

        if (! $libur) {
            return;
        }

        throw new RuntimeException(
            'Hari ini libur ('.$libur->name.'). Absensi tidak dapat dicatat.'
        );
    }

    /**
     * @return Collection<int, HariLibur>
     */
    public function forMonth(string $yearMonth, ?int $sekolahId, string $scope = HariLibur::APPLIES_BOTH): Collection
    {
        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return HariLibur::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) use ($sekolahId) {
                $query->whereNull('sekolah_id');
                if ($sekolahId) {
                    $query->orWhere('sekolah_id', $sekolahId);
                }
            })
            ->where(function ($query) use ($scope) {
                $query->where('applies_to', HariLibur::APPLIES_BOTH)
                    ->orWhere('applies_to', $scope);
            })
            ->orderBy('date')
            ->get();
    }

    public function countInMonth(string $yearMonth, ?int $sekolahId, string $scope = HariLibur::APPLIES_BOTH): int
    {
        return $this->forMonth($yearMonth, $sekolahId, $scope)
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->count();
    }
}
