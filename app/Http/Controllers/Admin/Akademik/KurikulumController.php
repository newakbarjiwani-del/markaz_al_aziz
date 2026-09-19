<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreKompetensiDasarRequest;
use App\Http\Requests\Akademik\StoreKurikulumMapelRequest;
use App\Http\Requests\Akademik\StoreKurikulumRequest;
use App\Http\Requests\Akademik\UpdateKurikulumRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\KompetensiDasar;
use App\Models\Kurikulum;
use App\Models\KurikulumMapel;
use App\Models\MataPelajaran;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use App\Support\AkademikSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KurikulumController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        return view('admin.akademik.kurikulum', [
            'title' => 'Kurikulum',
            'schools' => AdminSchoolScope::schools(),
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = Kurikulum::query()
            ->with(['sekolah', 'tahunAkademik'])
            ->withCount('mapel')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'jenjang', 'description'],
            'orderable' => ['name', 'jenjang', 'is_active'],
        ], function (Kurikulum $kurikulum) {
            $fields = array_merge(
                $kurikulum->only(['name', 'jenjang', 'sekolah_id', 'tahun_akademik_id', 'description']),
                ['is_active' => $kurikulum->is_active ? '1' : '0']
            );

            $showUrl = route('admin.akademik.kurikulum.show', $kurikulum);

            $actions = [
                'edit' => [
                    'update_url' => route('admin.akademik.kurikulum.update', $kurikulum),
                    'form_target' => 'akademik-kurikulum-form',
                    'modal_target' => 'akademik-kurikulum-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.akademik.kurikulum.destroy', $kurikulum),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus kurikulum ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $kurikulum->name],
                        ['label' => 'Mapel', 'value' => (string) $kurikulum->mapel_count],
                    ],
                ],
            ];

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($kurikulum->name).'</a>',
                    $kurikulum->name,
                    'text'
                ),
                $kurikulum->tahunAkademik?->name ?? '-',
                $kurikulum->jenjang ?: '-',
                $kurikulum->sekolah?->name ?? 'Semua sekolah',
                $kurikulum->mapel_count,
                $this->badgeCell(
                    $kurikulum->is_active ? 'Aktif' : 'Nonaktif',
                    $kurikulum->is_active ? 'badge badge-green' : 'badge badge-red'
                ),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKurikulumRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $kurikulum = Kurikulum::create($data);

        return $this->jsonSuccess('Kurikulum berhasil ditambahkan.', $kurikulum, 201);
    }

    public function update(UpdateKurikulumRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active');

        $kurikulum->update($data);

        return $this->jsonSuccess('Kurikulum berhasil diperbarui.', $kurikulum->fresh());
    }

    public function destroy(Kurikulum $kurikulum): JsonResponse
    {
        $this->authorize('akademik.delete');

        $name = $kurikulum->name;
        $kurikulum->delete();

        return $this->jsonSuccess('Kurikulum "'.$name.'" berhasil dihapus.');
    }

    public function show(Kurikulum $kurikulum): View
    {
        $this->authorize('akademik.view');

        $kurikulum->load([
            'tahunAkademik',
            'sekolah',
            'mapel.mataPelajaran',
            'mapel.kompetensiDasar',
        ]);

        $mapelOptions = MataPelajaran::query()
            ->where('is_active', true)
            ->orderBy('name');
        AdminSchoolScope::applyWithGlobal($mapelOptions);

        return view('admin.akademik.kurikulum-show', [
            'title' => 'Kurikulum · '.$kurikulum->name,
            'kurikulum' => $kurikulum,
            'mapelOptions' => $mapelOptions->get(),
            'semesters' => AkademikSemester::labels(),
        ]);
    }

    public function storeMapel(StoreKurikulumMapelRequest $request, Kurikulum $kurikulum): JsonResponse
    {
        $data = $request->validated();
        $data['kurikulum_id'] = $kurikulum->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $row = KurikulumMapel::create($data);

        return $this->jsonSuccess('Mata pelajaran ditambahkan ke kurikulum.', $row->load('mataPelajaran'), 201);
    }

    public function destroyMapel(Kurikulum $kurikulum, KurikulumMapel $kurikulumMapel): JsonResponse
    {
        $this->authorize('akademik.delete');

        abort_unless((int) $kurikulumMapel->kurikulum_id === (int) $kurikulum->id, 404);

        $kurikulumMapel->delete();

        return $this->jsonSuccess('Mata pelajaran dihapus dari kurikulum.');
    }

    public function storeKd(StoreKompetensiDasarRequest $request, KurikulumMapel $kurikulumMapel): JsonResponse
    {
        $data = $request->validated();
        $data['kurikulum_mapel_id'] = $kurikulumMapel->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $kd = KompetensiDasar::create($data);

        return $this->jsonSuccess('Kompetensi dasar berhasil ditambahkan.', $kd, 201);
    }

    public function destroyKd(KurikulumMapel $kurikulumMapel, KompetensiDasar $kompetensiDasar): JsonResponse
    {
        $this->authorize('akademik.delete');

        abort_unless((int) $kompetensiDasar->kurikulum_mapel_id === (int) $kurikulumMapel->id, 404);

        $kompetensiDasar->delete();

        return $this->jsonSuccess('Kompetensi dasar berhasil dihapus.');
    }
}
