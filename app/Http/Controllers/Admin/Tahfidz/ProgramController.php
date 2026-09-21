<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzProgramRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzProgramRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahfidzProgram;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        return view('admin.tahfidz.program.index', [
            'title' => 'Program Tahfidz',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzProgram::query()->with('sekolah');
        AdminSchoolScope::apply($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'peserta_label'],
            'orderable' => ['name', 'angkatan', null, 'is_active', null],
        ], function (TahfidzProgram $program) {
            $fields = [
                'sekolah_id' => $program->sekolah_id,
                'tahun_akademik_id' => $program->tahun_akademik_id,
                'name' => $program->name,
                'angkatan' => $program->angkatan,
                'peserta_label' => $program->peserta_label,
                'is_active' => $program->is_active ? '1' : '0',
            ];

            return [
                e($program->name),
                (string) $program->angkatan,
                e($program->peserta_label),
                $program->is_active ? 'Aktif' : 'Nonaktif',
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.tahfidz.program.update', $program),
                            'form_target' => 'tahfidz-program-form',
                            'modal_target' => 'tahfidz-program-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.tahfidz.program.destroy', $program),
                            'confirm_title' => 'Hapus Program',
                            'confirm_message' => 'Hapus program tahfidz ini?',
                            'confirm_detail' => [
                                ['label' => 'Program', 'value' => $program->label()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreTahfidzProgramRequest $request): JsonResponse
    {
        $data = $request->validated();

        $program = TahfidzProgram::query()->create([
            'sekolah_id' => AdminSchoolScope::resolveOptionalFromRequest($request) ?? ($data['sekolah_id'] ?? null),
            'tahun_akademik_id' => $data['tahun_akademik_id'] ?? null,
            'name' => $data['name'],
            'angkatan' => (int) $data['angkatan'],
            'peserta_label' => $data['peserta_label'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Program tahfidz ditambahkan.', $program, 201);
    }

    public function update(UpdateTahfidzProgramRequest $request, TahfidzProgram $program): JsonResponse
    {
        $this->authorizeSchool($program);

        $data = $request->validated();
        $program->update([
            'sekolah_id' => AdminSchoolScope::resolveOptionalFromRequest($request) ?? ($data['sekolah_id'] ?? $program->sekolah_id),
            'tahun_akademik_id' => $data['tahun_akademik_id'] ?? null,
            'name' => $data['name'],
            'angkatan' => (int) $data['angkatan'],
            'peserta_label' => $data['peserta_label'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Program tahfidz diperbarui.', $program);
    }

    public function destroy(TahfidzProgram $program): JsonResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeSchool($program);
        $program->delete();

        return $this->jsonSuccess('Program tahfidz dihapus.');
    }

    private function authorizeSchool(TahfidzProgram $program): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $program->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
