<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\BuildRaporRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Rapor;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Services\RaporBuilderService;
use App\Support\AdminSchoolScope;
use App\Support\AkademikSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaporController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        $siswaQuery = Siswa::query()->where('status', Siswa::STATUS_ACTIVE)->orderBy('name');
        AdminSchoolScope::apply($siswaQuery);

        return view('admin.akademik.rapor', [
            'title' => 'Rapor',
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'siswaOptions' => $siswaQuery->limit(500)->get(),
            'semesters' => AkademikSemester::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = Rapor::query()
            ->with(['siswa.kelas', 'tahunAkademik'])
            ->withCount('mapel')
            ->when($request->filled('siswa_id'), fn ($q) => $q->where('siswa_id', $request->integer('siswa_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')))
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->string('semester')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        AdminSchoolScope::applyRelation($query, 'siswa');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['status', 'catatan_wali'],
            'orderable' => ['semester', 'status', 'finalized_at', 'created_at'],
        ], function (Rapor $rapor) {
            $showUrl = route('admin.akademik.rapor.show', $rapor);
            $siswaName = $rapor->siswa?->name ?? '-';

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($siswaName).'</a>',
                    $siswaName,
                    'text'
                ),
                $rapor->siswa?->kelas?->name ?? '-',
                $rapor->tahunAkademik?->name ?? '-',
                AkademikSemester::label($rapor->semester),
                $this->badgeCell(
                    $rapor->isFinal() ? 'Final' : 'Draft',
                    $rapor->isFinal() ? 'badge badge-green' : 'badge badge-amber'
                ),
                $rapor->mapel_count,
                $this->dateCell($rapor->finalized_at),
                $this->cell(
                    '<a href="'.e($showUrl).'" class="btn-action btn-action-view" title="Detail">'
                    .'<i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></a>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(Rapor $rapor): View
    {
        $this->authorize('akademik.view');

        $rapor->load(['siswa.kelas', 'tahunAkademik', 'mapel.mataPelajaran']);

        return view('admin.akademik.rapor-show', [
            'title' => 'Rapor · '.($rapor->siswa?->name ?? '#'),
            'rapor' => $rapor,
        ]);
    }

    public function build(BuildRaporRequest $request, RaporBuilderService $builder): JsonResponse
    {
        $siswa = Siswa::query()->findOrFail($request->integer('siswa_id'));

        $rapor = $builder->buildDraft(
            $siswa,
            $request->integer('tahun_akademik_id'),
            $request->string('semester')->toString()
        );

        if ($request->filled('catatan_wali')) {
            $rapor->update(['catatan_wali' => $request->string('catatan_wali')->toString()]);
            $rapor = $rapor->fresh(['mapel.mataPelajaran', 'siswa', 'tahunAkademik']);
        }

        return $this->jsonSuccess('Draft rapor berhasil dibangun.', $rapor, 201);
    }

    public function finalize(Rapor $rapor, RaporBuilderService $builder): JsonResponse
    {
        $this->authorize('akademik.update');

        $rapor = $builder->finalize($rapor);

        return $this->jsonSuccess('Rapor berhasil difinalisasi.', $rapor);
    }
}
