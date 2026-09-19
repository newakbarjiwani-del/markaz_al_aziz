<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzTargetRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzTargetRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Siswa;
use App\Models\TahfidzSurah;
use App\Models\TahfidzTarget;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        return view('admin.tahfidz.target.index', [
            'title' => 'Target Hafalan',
            'schools' => AdminSchoolScope::schools(),
            'surahs' => TahfidzSurah::query()->orderBy('number')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzTarget::query()
            ->with(['siswa', 'sekolah', 'surah', 'assignedBy'])
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('period'), fn ($q) => $q->where('period', $request->input('period')));

        AdminSchoolScope::apply($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['note', 'siswa.name', 'siswa.nis'],
            'orderable' => [null, null, 'period', 'due_date', null],
        ], function (TahfidzTarget $target) {
            $fields = [
                'siswa_id' => $target->siswa_id,
                'sekolah_id' => $target->sekolah_id,
                'range_type' => $target->range_type,
                'juz' => $target->juz,
                'surah_id' => $target->surah_id,
                'ayah_from' => $target->ayah_from,
                'ayah_to' => $target->ayah_to,
                'period' => $target->period,
                'due_date' => $target->due_date?->format('Y-m-d'),
                'note' => $target->note,
                'siswa_label' => $target->siswa
                    ? $target->siswa->nis.' — '.$target->siswa->name
                    : null,
            ];

            return [
                $this->cell(
                    e($target->siswa?->name ?? '-').'<div class="text-xs text-slate-500">'.e($target->siswa?->nis ?? '').'</div>',
                    $target->siswa?->name,
                    'text'
                ),
                e($target->rangeLabel()),
                $target->period === 'daily' ? 'Harian' : 'Mingguan',
                $this->dateCell($target->due_date),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.tahfidz.target.update', $target),
                            'form_target' => 'tahfidz-target-form',
                            'modal_target' => 'tahfidz-target-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.tahfidz.target.destroy', $target),
                            'confirm_title' => 'Hapus Target',
                            'confirm_message' => 'Hapus target hafalan ini?',
                            'confirm_detail' => [
                                ['label' => 'Siswa', 'value' => $target->siswa?->name ?? '-'],
                                ['label' => 'Target', 'value' => $target->rangeLabel()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreTahfidzTargetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        $target = TahfidzTarget::query()->create([
            ...$this->normalizedRange($data),
            'siswa_id' => $siswa->id,
            'sekolah_id' => $data['sekolah_id'] ?? $siswa->sekolah_id,
            'period' => $data['period'],
            'due_date' => $data['due_date'] ?? null,
            'note' => $data['note'] ?? null,
            'assigned_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess('Target hafalan ditambahkan.', $target, 201);
    }

    public function update(UpdateTahfidzTargetRequest $request, TahfidzTarget $target): JsonResponse
    {
        $this->authorizeSchool($target);

        $data = $request->validated();
        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        $target->update([
            ...$this->normalizedRange($data),
            'siswa_id' => $siswa->id,
            'sekolah_id' => $data['sekolah_id'] ?? $siswa->sekolah_id,
            'period' => $data['period'],
            'due_date' => $data['due_date'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return $this->jsonSuccess('Target hafalan diperbarui.', $target);
    }

    public function destroy(TahfidzTarget $target): JsonResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeSchool($target);
        $target->delete();

        return $this->jsonSuccess('Target hafalan dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedRange(array $data): array
    {
        if ($data['range_type'] === 'juz') {
            return [
                'range_type' => 'juz',
                'juz' => (int) $data['juz'],
                'surah_id' => null,
                'ayah_from' => null,
                'ayah_to' => null,
            ];
        }

        return [
            'range_type' => 'ayat',
            'juz' => null,
            'surah_id' => (int) $data['surah_id'],
            'ayah_from' => (int) $data['ayah_from'],
            'ayah_to' => (int) $data['ayah_to'],
        ];
    }

    private function authorizeSchool(TahfidzTarget $target): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $target->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
