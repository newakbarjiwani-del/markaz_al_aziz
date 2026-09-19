<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreJadwalPelajaranRequest;
use App\Http\Requests\Akademik\StoreJadwalPelajaranSlotRequest;
use App\Http\Requests\Akademik\UpdateJadwalPelajaranRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPelajaranSlot;
use App\Models\MataPelajaran;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPelajaranController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        return view('admin.akademik.jadwal-pelajaran', [
            'title' => 'Jadwal Pelajaran',
            'schools' => AdminSchoolScope::schools(),
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'classes' => AdminSchoolScope::kelasList()->load('sekolah:id,name'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = JadwalPelajaran::query()
            ->with(['sekolah', 'tahunAkademik', 'kelas'])
            ->withCount('slots')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('kelas_id', $request->integer('kelas_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name'],
            'orderable' => ['name', 'is_active'],
        ], function (JadwalPelajaran $jadwal) {
            $fields = array_merge(
                $jadwal->only(['name', 'sekolah_id', 'tahun_akademik_id', 'kelas_id']),
                ['is_active' => $jadwal->is_active ? '1' : '0']
            );

            $showUrl = route('admin.akademik.jadwal-pelajaran.show', $jadwal);
            $label = $jadwal->name ?: ($jadwal->kelas?->name ?? '#'.$jadwal->id);

            $actions = [
                'edit' => [
                    'update_url' => route('admin.akademik.jadwal-pelajaran.update', $jadwal),
                    'form_target' => 'akademik-jadwal-form',
                    'modal_target' => 'akademik-jadwal-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.akademik.jadwal-pelajaran.destroy', $jadwal),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus jadwal pelajaran ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $jadwal->name ?: '-'],
                        ['label' => 'Kelas', 'value' => $jadwal->kelas?->name ?? '-'],
                    ],
                ],
            ];

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($label).'</a>',
                    $label,
                    'text'
                ),
                $jadwal->kelas?->name ?? '-',
                $jadwal->tahunAkademik?->name ?? '-',
                $jadwal->sekolah?->name ?? 'Semua sekolah',
                $jadwal->slots_count,
                $this->badgeCell(
                    $jadwal->is_active ? 'Aktif' : 'Nonaktif',
                    $jadwal->is_active ? 'badge badge-green' : 'badge badge-red'
                ),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreJadwalPelajaranRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $jadwal = JadwalPelajaran::create($data);

        return $this->jsonSuccess('Jadwal pelajaran berhasil ditambahkan.', $jadwal, 201);
    }

    public function update(UpdateJadwalPelajaranRequest $request, JadwalPelajaran $jadwalPelajaran): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active');

        $jadwalPelajaran->update($data);

        return $this->jsonSuccess('Jadwal pelajaran berhasil diperbarui.', $jadwalPelajaran->fresh());
    }

    public function destroy(JadwalPelajaran $jadwalPelajaran): JsonResponse
    {
        $this->authorize('akademik.delete');

        $label = $jadwalPelajaran->name ?: ('#'.$jadwalPelajaran->id);
        $jadwalPelajaran->delete();

        return $this->jsonSuccess('Jadwal pelajaran "'.$label.'" berhasil dihapus.');
    }

    public function show(JadwalPelajaran $jadwalPelajaran): View
    {
        $this->authorize('akademik.view');

        $jadwalPelajaran->load(['kelas', 'tahunAkademik', 'sekolah', 'slots.mataPelajaran', 'slots.guru']);

        $mapelQuery = MataPelajaran::query()->where('is_active', true)->orderBy('name');
        AdminSchoolScope::applyWithGlobal($mapelQuery);

        $guruQuery = Guru::query()->orderBy('name');
        AdminSchoolScope::apply($guruQuery);

        return view('admin.akademik.jadwal-pelajaran-show', [
            'title' => 'Jadwal · '.($jadwalPelajaran->name ?: $jadwalPelajaran->kelas?->name),
            'jadwal' => $jadwalPelajaran,
            'mapelOptions' => $mapelQuery->get(),
            'guruOptions' => $guruQuery->get(),
            'dayLabels' => JadwalPelajaranSlot::dayLabels(),
        ]);
    }

    public function storeSlot(StoreJadwalPelajaranSlotRequest $request, JadwalPelajaran $jadwalPelajaran): JsonResponse
    {
        $data = $request->validated();
        $data['jadwal_pelajaran_id'] = $jadwalPelajaran->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $slot = JadwalPelajaranSlot::create($data);

        return $this->jsonSuccess('Slot jadwal berhasil ditambahkan.', $slot->load(['mataPelajaran', 'guru']), 201);
    }

    public function destroySlot(JadwalPelajaran $jadwalPelajaran, JadwalPelajaranSlot $slot): JsonResponse
    {
        $this->authorize('akademik.delete');

        abort_unless((int) $slot->jadwal_pelajaran_id === (int) $jadwalPelajaran->id, 404);

        $slot->delete();

        return $this->jsonSuccess('Slot jadwal berhasil dihapus.');
    }
}
