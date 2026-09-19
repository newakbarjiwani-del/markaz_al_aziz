<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use App\Models\NilaiEntry;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use App\Support\AkademikSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanNilaiController extends Controller
{
    public function index(): View
    {
        $this->authorize('akademik.view');

        $mapelQuery = MataPelajaran::query()->where('is_active', true)->orderBy('name');
        AdminSchoolScope::applyWithGlobal($mapelQuery);

        return view('admin.akademik.laporan', [
            'title' => 'Laporan Nilai',
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
            'mapelOptions' => $mapelQuery->get(),
            'semesters' => AkademikSemester::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $inner = NilaiEntry::query()
            ->select([
                'nilai_entry.siswa_id',
                'nilai_entry.mata_pelajaran_id',
                'nilai_entry.tahun_akademik_id',
                'nilai_entry.semester',
                DB::raw('AVG(nilai_entry.skor) as avg_skor'),
                DB::raw('COUNT(*) as jumlah_entri'),
                DB::raw('MIN(nilai_entry.skor) as min_skor'),
                DB::raw('MAX(nilai_entry.skor) as max_skor'),
            ])
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')))
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->string('semester')))
            ->when($request->filled('mata_pelajaran_id'), fn ($q) => $q->where('mata_pelajaran_id', $request->integer('mata_pelajaran_id')))
            ->when($request->filled('kelas_id'), function ($q) use ($request) {
                $q->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $request->integer('kelas_id')));
            })
            ->groupBy([
                'nilai_entry.siswa_id',
                'nilai_entry.mata_pelajaran_id',
                'nilai_entry.tahun_akademik_id',
                'nilai_entry.semester',
            ]);

        AdminSchoolScope::applyRelation($inner, 'siswa');

        $rows = $inner->with(['siswa.kelas', 'mataPelajaran', 'tahunAkademik'])->get();

        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 25)));
        $total = $rows->count();
        $page = $rows->slice($start, $length)->values();

        $data = $page->map(function (NilaiEntry $row) {
            return [
                $row->siswa?->name ?? '-',
                $row->siswa?->kelas?->name ?? '-',
                $row->mataPelajaran?->name ?? '-',
                $row->tahunAkademik?->name ?? '-',
                AkademikSemester::label($row->semester),
                round((float) $row->avg_skor, 2),
                (int) $row->jumlah_entri,
                round((float) $row->min_skor, 2),
                round((float) $row->max_skor, 2),
            ];
        })->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }
}
