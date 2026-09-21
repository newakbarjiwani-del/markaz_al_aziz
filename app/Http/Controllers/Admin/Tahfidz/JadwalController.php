<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzJadwalRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzJadwalRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzJadwal;
use App\Support\AdminSchoolScope;
use App\Support\TahfidzHari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $halaqoh = TahfidzHalaqoh::query()->with('guru')->orderBy('id');
        AdminSchoolScope::apply($halaqoh);

        return view('admin.tahfidz.jadwal.index', [
            'title' => 'Jadwal Halaqoh',
            'halaqoh' => $halaqoh->get(),
            'days' => TahfidzHari::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzJadwal::query()
            ->with(['halaqoh.guru', 'halaqoh.program'])
            ->when($request->filled('halaqoh_id'), fn ($q) => $q->where('halaqoh_id', $request->integer('halaqoh_id')));

        AdminSchoolScope::applyRelation($query, 'halaqoh');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['halaqoh.name', 'halaqoh.guru.name'],
            'orderable' => [null, 'day_of_week', 'time_start', 'is_active', null],
        ], function (TahfidzJadwal $jadwal) {
            $start = substr((string) $jadwal->time_start, 0, 5);
            $end = substr((string) $jadwal->time_end, 0, 5);
            $fields = [
                'halaqoh_id' => $jadwal->halaqoh_id,
                'day_of_week' => $jadwal->day_of_week,
                'time_start' => $start,
                'time_end' => $end,
                'is_active' => $jadwal->is_active ? '1' : '0',
            ];

            return [
                e($jadwal->halaqoh?->displayName() ?? '-'),
                e($jadwal->dayLabel()),
                e($jadwal->timeLabel()),
                $jadwal->is_active ? 'Aktif' : 'Nonaktif',
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.tahfidz.jadwal.update', $jadwal),
                            'form_target' => 'tahfidz-jadwal-form',
                            'modal_target' => 'tahfidz-jadwal-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.tahfidz.jadwal.destroy', $jadwal),
                            'confirm_title' => 'Hapus Jadwal',
                            'confirm_message' => 'Hapus slot jadwal ini?',
                            'confirm_detail' => [
                                ['label' => 'Halaqoh', 'value' => $jadwal->halaqoh?->displayName() ?? '-'],
                                ['label' => 'Hari', 'value' => $jadwal->dayLabel()],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreTahfidzJadwalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $halaqoh = TahfidzHalaqoh::query()->findOrFail($data['halaqoh_id']);
        $this->authorizeHalaqoh($halaqoh);

        $jadwal = TahfidzJadwal::query()->create([
            'halaqoh_id' => $halaqoh->id,
            'day_of_week' => (int) $data['day_of_week'],
            'time_start' => $data['time_start'],
            'time_end' => $data['time_end'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Jadwal halaqoh ditambahkan.', $jadwal, 201);
    }

    public function update(UpdateTahfidzJadwalRequest $request, TahfidzJadwal $jadwal): JsonResponse
    {
        $this->authorizeHalaqoh($jadwal->halaqoh);
        $data = $request->validated();
        $halaqoh = TahfidzHalaqoh::query()->findOrFail($data['halaqoh_id']);
        $this->authorizeHalaqoh($halaqoh);

        $jadwal->update([
            'halaqoh_id' => $halaqoh->id,
            'day_of_week' => (int) $data['day_of_week'],
            'time_start' => $data['time_start'],
            'time_end' => $data['time_end'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Jadwal halaqoh diperbarui.', $jadwal);
    }

    public function destroy(TahfidzJadwal $jadwal): JsonResponse
    {
        $this->authorize('tahfidz.delete');
        $this->authorizeHalaqoh($jadwal->halaqoh);
        $jadwal->delete();

        return $this->jsonSuccess('Jadwal halaqoh dihapus.');
    }

    private function authorizeHalaqoh(?TahfidzHalaqoh $halaqoh): void
    {
        abort_unless($halaqoh, 404);
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $halaqoh->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
