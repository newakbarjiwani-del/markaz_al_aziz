<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzHalaqohAnggotaRequest;
use App\Http\Requests\Tahfidz\StoreTahfidzHalaqohRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzHalaqohRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzHalaqohAnggota;
use App\Models\TahfidzProgram;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HalaqohController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $programs = TahfidzProgram::query()->orderBy('name');
        AdminSchoolScope::apply($programs);

        return view('admin.tahfidz.halaqoh.index', [
            'title' => 'Halaqoh',
            'programs' => $programs->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzHalaqoh::query()
            ->with(['program', 'guru'])
            ->withCount('anggota')
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')));

        AdminSchoolScope::apply($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'guru.name', 'program.name'],
            'orderable' => [null, null, 'anggota_count', null],
        ], function (TahfidzHalaqoh $halaqoh) {
            $fields = [
                'program_id' => $halaqoh->program_id,
                'sekolah_id' => $halaqoh->sekolah_id,
                'guru_id' => $halaqoh->guru_id,
                'name' => $halaqoh->name,
                'guru_label' => $halaqoh->guru?->name,
            ];

            return [
                $this->cell(
                    '<a href="'.e(route('admin.tahfidz.halaqoh.show', $halaqoh)).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($halaqoh->displayName()).'</a>'
                    .'<div class="text-xs text-slate-500">'.e($halaqoh->program?->label() ?? '').'</div>',
                    $halaqoh->displayName(),
                    'text'
                ),
                e($halaqoh->guru?->name ?? '-'),
                (string) $halaqoh->anggota_count,
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.tahfidz.halaqoh.update', $halaqoh),
                            'form_target' => 'tahfidz-halaqoh-form',
                            'modal_target' => 'tahfidz-halaqoh-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.tahfidz.halaqoh.destroy', $halaqoh),
                            'confirm_title' => 'Hapus Halaqoh',
                            'confirm_message' => 'Hapus halaqoh ini beserta anggotanya?',
                            'confirm_detail' => [
                                ['label' => 'Halaqoh', 'value' => $halaqoh->displayName()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreTahfidzHalaqohRequest $request): JsonResponse
    {
        $data = $request->validated();
        $program = TahfidzProgram::query()->findOrFail($data['program_id']);
        $this->authorizeProgram($program);

        $halaqoh = TahfidzHalaqoh::query()->create([
            'program_id' => $program->id,
            'sekolah_id' => $program->sekolah_id,
            'guru_id' => (int) $data['guru_id'],
            'name' => $data['name'] ?? null,
        ]);

        return $this->jsonSuccess('Halaqoh ditambahkan.', $halaqoh, 201);
    }

    public function update(UpdateTahfidzHalaqohRequest $request, TahfidzHalaqoh $halaqoh): JsonResponse
    {
        $this->authorizeSchool($halaqoh);
        $data = $request->validated();
        $program = TahfidzProgram::query()->findOrFail($data['program_id']);
        $this->authorizeProgram($program);

        $halaqoh->update([
            'program_id' => $program->id,
            'sekolah_id' => $program->sekolah_id,
            'guru_id' => (int) $data['guru_id'],
            'name' => $data['name'] ?? null,
        ]);

        return $this->jsonSuccess('Halaqoh diperbarui.', $halaqoh);
    }

    public function destroy(TahfidzHalaqoh $halaqoh): JsonResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeSchool($halaqoh);
        $halaqoh->delete();

        return $this->jsonSuccess('Halaqoh dihapus.');
    }

    public function show(TahfidzHalaqoh $halaqoh): View
    {
        $this->authorize('tahfidz.view');
        $this->authorizeSchool($halaqoh);

        $halaqoh->load(['program', 'guru', 'anggota.siswa']);

        return view('admin.tahfidz.halaqoh.show', [
            'title' => $halaqoh->displayName(),
            'halaqoh' => $halaqoh,
        ]);
    }

    public function storeAnggota(StoreTahfidzHalaqohAnggotaRequest $request, TahfidzHalaqoh $halaqoh): JsonResponse|RedirectResponse
    {
        $this->authorizeSchool($halaqoh);
        $data = $request->validated();

        $anggota = TahfidzHalaqohAnggota::query()->firstOrCreate(
            [
                'halaqoh_id' => $halaqoh->id,
                'siswa_id' => (int) $data['siswa_id'],
            ],
            [
                'total_juz' => (int) ($data['total_juz'] ?? 0),
            ],
        );

        if (! $anggota->wasRecentlyCreated && isset($data['total_juz'])) {
            $anggota->update(['total_juz' => (int) $data['total_juz']]);
        }

        if ($request->wantsJson()) {
            return $this->jsonSuccess('Anggota halaqoh disimpan.', $anggota, $anggota->wasRecentlyCreated ? 201 : 200);
        }

        return redirect()
            ->route('admin.tahfidz.halaqoh.show', $halaqoh)
            ->with('success', 'Anggota halaqoh disimpan.');
    }

    public function destroyAnggota(TahfidzHalaqoh $halaqoh, TahfidzHalaqohAnggota $tahfidzHalaqohAnggota): JsonResponse|RedirectResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeSchool($halaqoh);
        abort_unless($tahfidzHalaqohAnggota->halaqoh_id === $halaqoh->id, 404);
        $tahfidzHalaqohAnggota->delete();

        if (request()->wantsJson()) {
            return $this->jsonSuccess('Anggota dihapus dari halaqoh.');
        }

        return redirect()
            ->route('admin.tahfidz.halaqoh.show', $halaqoh)
            ->with('success', 'Anggota dihapus dari halaqoh.');
    }

    private function authorizeSchool(TahfidzHalaqoh $halaqoh): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $halaqoh->sekolah_id !== $scoped) {
            abort(403);
        }
    }

    private function authorizeProgram(TahfidzProgram $program): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $program->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
