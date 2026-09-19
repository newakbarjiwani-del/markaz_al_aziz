<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzProgressRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzProgressRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Siswa;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Services\TahfidzProgressService;
use App\Support\AdminSchoolScope;
use App\Support\TahfidzProgressStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    use DataTableTrait;

    public function __construct(private readonly TahfidzProgressService $progressService) {}

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        return view('admin.tahfidz.progress.index', [
            'title' => 'Progress Hafalan',
            'schools' => AdminSchoolScope::schools(),
            'surahs' => TahfidzSurah::query()->orderBy('number')->get(),
            'statuses' => TahfidzProgressStatus::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzProgress::query()
            ->with(['siswa', 'surah', 'verifiedBy'])
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        AdminSchoolScope::apply($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['siswa.name', 'siswa.nis', 'surah.name_id'],
            'orderable' => [null, null, 'status', 'last_reviewed_at', null],
        ], function (TahfidzProgress $progress) {
            $fields = [
                'status' => $progress->status,
                'note' => '',
                'verified' => $progress->verified_by ? '1' : '0',
            ];

            return [
                $this->cell(
                    e($progress->siswa?->name ?? '-').'<div class="text-xs text-slate-500">'.e($progress->siswa?->nis ?? '').'</div>',
                    $progress->siswa?->name,
                    'text'
                ),
                e($progress->rangeLabel()),
                $this->badgeCell($progress->statusLabel(), TahfidzProgressStatus::badgeClass($progress->status)),
                $this->dateCell($progress->last_reviewed_at),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.tahfidz.progress.update', $progress),
                            'form_target' => 'tahfidz-progress-update-form',
                            'modal_target' => 'tahfidz-progress-update-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.tahfidz.progress.destroy', $progress),
                            'confirm_title' => 'Hapus Progress',
                            'confirm_message' => 'Hapus catatan progress ini?',
                            'confirm_detail' => [
                                ['label' => 'Siswa', 'value' => $progress->siswa?->name ?? '-'],
                                ['label' => 'Rentang', 'value' => $progress->rangeLabel()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreTahfidzProgressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        $progress = $this->progressService->upsert([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $data['sekolah_id'] ?? $siswa->sekolah_id,
            'surah_id' => (int) $data['surah_id'],
            'ayah_from' => (int) $data['ayah_from'],
            'ayah_to' => (int) $data['ayah_to'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'source' => 'guru',
            'verified' => false,
            'actor_id' => $request->user()?->id,
        ]);

        return $this->jsonSuccess('Progress hafalan disimpan.', $progress, 201);
    }

    public function update(UpdateTahfidzProgressRequest $request, TahfidzProgress $progress): JsonResponse
    {
        $this->authorizeSchool($progress);

        $data = $request->validated();

        $updated = $this->progressService->upsert([
            'siswa_id' => $progress->siswa_id,
            'sekolah_id' => $progress->sekolah_id,
            'surah_id' => $progress->surah_id,
            'ayah_from' => $progress->ayah_from,
            'ayah_to' => $progress->ayah_to,
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'source' => 'guru',
            'verified' => $request->boolean('verified'),
            'actor_id' => $request->user()?->id,
        ]);

        return $this->jsonSuccess('Progress hafalan diperbarui.', $updated);
    }

    public function destroy(TahfidzProgress $progress): JsonResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeSchool($progress);
        $progress->delete();

        return $this->jsonSuccess('Progress hafalan dihapus.');
    }

    private function authorizeSchool(TahfidzProgress $progress): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $progress->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
