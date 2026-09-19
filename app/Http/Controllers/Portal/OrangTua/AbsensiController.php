<?php

namespace App\Http\Controllers\Portal\OrangTua;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Http\Traits\PortalAccess;
use App\Models\AbsensiSiswa;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    use DataTableTrait;
    use FilterTrait;
    use PortalAccess;

    public function index(Request $request): View
    {
        $summary = $this->summary($request);

        return view('portal.ortu.absensi', [
            'title' => 'Absensi Anak',
            'children' => $this->ortuChildren(),
            'summary' => $summary,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AbsensiSiswa::query()->with('siswa.kelas');
        $this->applyOrtuSiswaScope($query, $request);
        $this->applyDateRange($query, $request, 'date');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status'],
            'orderable' => ['date', 'status', 'created_at'],
        ], function (AbsensiSiswa $row) {
            return [
                $row->siswa?->nis ?? '-',
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->date,
                ucfirst($row->status),
                DisplayDate::time($row->time_in),
            ];
        });
    }

    public function summaryData(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->summary($request),
        ]);
    }

    private function summary(Request $request): array
    {
        $filteredQuery = $this->scopedQuery($request);
        $this->applyDateRange($filteredQuery, $request, 'date');
        $filtered = $this->statusSummary($filteredQuery);

        $monthQuery = $this->scopedQuery($request);
        $monthQuery->whereBetween('date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ]);
        $monthly = $this->statusSummary($monthQuery);

        return [
            ...$filtered,
            'kehadiran_bulan_ini' => $monthly['kehadiran'],
            'hadir_bulan_ini' => $monthly['hadir'],
            'total_bulan_ini' => $monthly['total'],
            'bulan_ini_label' => $this->currentMonthLabel(),
        ];
    }

    private function scopedQuery(Request $request): Builder
    {
        $query = AbsensiSiswa::query();
        $this->applyOrtuSiswaScope($query, $request);

        return $query;
    }

    /**
     * @return array{total: int, hadir: int, izin: int, sakit: int, alpha: int, kehadiran: float|int}
     */
    private function statusSummary(Builder $query): array
    {
        $total = (int) (clone $query)->count();
        $statusCounts = (clone $query)
            ->selectRaw('LOWER(status) as status_key, COUNT(*) as total')
            ->groupBy('status_key')
            ->pluck('total', 'status_key');

        $count = static fn (string $status): int => (int) ($statusCounts[$status] ?? 0);
        $hadir = $count('hadir');

        return [
            'total' => $total,
            'hadir' => $hadir,
            'izin' => $count('izin'),
            'sakit' => $count('sakit'),
            'alpha' => $count('alpha'),
            'kehadiran' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
        ];
    }

    private function currentMonthLabel(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($months[now()->month] ?? now()->format('F')).' '.now()->year;
    }
}
