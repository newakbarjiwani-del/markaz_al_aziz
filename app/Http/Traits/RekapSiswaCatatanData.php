<?php

namespace App\Http\Traits;

use App\Support\AdminSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait RekapSiswaCatatanData
{
    /**
     * @param  class-string<\App\Models\PrestasiSiswa|\App\Models\PelanggaranSiswa>  $modelClass
     */
    protected function rekapSiswaData(
        Request $request,
        string $modelClass,
        ?callable $extraActions = null,
    ): JsonResponse {
        $table = (new $modelClass)->getTable();

        $query = $modelClass::query()
            ->join('siswa', 'siswa.id', '=', "{$table}.siswa_id")
            ->leftJoin('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->select("{$table}.siswa_id")
            ->selectRaw('MAX(siswa.name) as siswa_name')
            ->selectRaw('MAX(siswa.nis) as siswa_nis')
            ->selectRaw('MAX(kelas.name) as kelas_name')
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw("COALESCE(SUM({$table}.point), 0) as total_point")
            ->groupBy("{$table}.siswa_id");

        if ($table === 'pelanggaran_siswa') {
            $query->where("{$table}.is_punished", false);
        }

        AdminSchoolScope::apply($query, "{$table}.sekolah_id");

        if ($request->filled('kelas_id')) {
            $query->where('siswa.kelas_id', $request->integer('kelas_id'));
        }

        if ($request->filled('sekolah_id')) {
            $query->where("{$table}.sekolah_id", $request->integer('sekolah_id'));
        }

        if ($request->filled('date_from')) {
            $query->where("{$table}.tanggal", '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where("{$table}.tanggal", '<=', $request->input('date_to'));
        }

        if ($request->filled('siswa')) {
            $siswa = trim((string) $request->input('siswa'));
            $query->where(function (Builder $q) use ($siswa) {
                $q->where('siswa.name', 'like', "%{$siswa}%")
                    ->orWhere('siswa.nis', 'like', "%{$siswa}%");
            });
        }

        if ($request->filled('min_total_point')) {
            $min = max(0, (int) $request->input('min_total_point'));
            $query->havingRaw("COALESCE(SUM({$table}.point), 0) >= ?", [$min]);
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function (Builder $q) use ($search) {
                $q->where('siswa.name', 'like', "%{$search}%")
                    ->orWhere('siswa.nis', 'like', "%{$search}%");
            });
        }

        $totalPointExpr = DB::raw("COALESCE(SUM({$table}.point), 0)");
        $totalRecordsExpr = DB::raw('COUNT(*)');

        if (! $request->has('order.0.column')) {
            $query->orderByDesc($totalPointExpr)->orderByDesc($totalRecordsExpr);
        }

        $columns = [
            'searchable' => [],
            'orderable' => [
                DB::raw('MAX(siswa.name)'),
                DB::raw('MAX(siswa.nis)'),
                DB::raw('MAX(kelas.name)'),
                $totalRecordsExpr,
                $totalPointExpr,
            ],
        ];

        return $this->datatableResponse($request, $query, $columns, function ($row) use ($extraActions) {
            $actionCell = $extraActions ? $extraActions($row) : '-';

            return [
                $row->siswa_name ?? '-',
                $row->siswa_nis ?? '-',
                $row->kelas_name ?? '-',
                (int) $row->total_records,
                (int) $row->total_point,
                is_array($actionCell) ? $this->cell('', ['actions' => $actionCell], 'action') : $actionCell,
            ];
        });
    }
}
