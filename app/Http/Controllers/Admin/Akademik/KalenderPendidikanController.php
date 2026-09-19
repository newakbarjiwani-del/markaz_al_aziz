<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreKalenderPendidikanRequest;
use App\Http\Requests\Akademik\UpdateKalenderPendidikanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\KalenderPendidikan;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KalenderPendidikanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        return view('admin.akademik.kalender', [
            'title' => 'Kalender Pendidikan',
            'schools' => AdminSchoolScope::schools(),
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
            'jenisLabels' => KalenderPendidikan::jenisLabels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = KalenderPendidikan::query()
            ->with(['sekolah', 'tahunAkademik'])
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->string('jenis')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')))
            ->when($request->filled('tahun_akademik_id'), fn ($q) => $q->where('tahun_akademik_id', $request->integer('tahun_akademik_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'notes'],
            'orderable' => ['name', 'starts_on', 'ends_on', 'jenis'],
        ], function (KalenderPendidikan $row) {
            $fields = array_merge(
                $row->only(['name', 'jenis', 'notes', 'sekolah_id', 'tahun_akademik_id']),
                [
                    'starts_on' => $row->starts_on?->format('Y-m-d'),
                    'ends_on' => $row->ends_on?->format('Y-m-d'),
                ]
            );

            $actions = [
                'edit' => [
                    'update_url' => route('admin.akademik.kalender.update', $row),
                    'form_target' => 'akademik-kalender-form',
                    'modal_target' => 'akademik-kalender-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.akademik.kalender.destroy', $row),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus entri kalender ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $row->name],
                        ['label' => 'Jenis', 'value' => $row->jenisLabel()],
                    ],
                ],
            ];

            return [
                $row->name,
                $this->badgeCell($row->jenisLabel(), 'badge badge-blue'),
                $this->dateCell($row->starts_on),
                $this->dateCell($row->ends_on),
                $row->tahunAkademik?->name ?? '-',
                $row->sekolah?->name ?? 'Semua sekolah',
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKalenderPendidikanRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['tahun_akademik_id'] = $request->filled('tahun_akademik_id')
            ? $request->integer('tahun_akademik_id')
            : null;

        $row = KalenderPendidikan::create($data);

        return $this->jsonSuccess('Entri kalender berhasil ditambahkan.', $row, 201);
    }

    public function update(UpdateKalenderPendidikanRequest $request, KalenderPendidikan $kalender): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['tahun_akademik_id'] = $request->filled('tahun_akademik_id')
            ? $request->integer('tahun_akademik_id')
            : null;

        $kalender->update($data);

        return $this->jsonSuccess('Entri kalender berhasil diperbarui.', $kalender->fresh());
    }

    public function destroy(KalenderPendidikan $kalender): JsonResponse
    {
        $this->authorize('akademik.delete');

        $name = $kalender->name;
        $kalender->delete();

        return $this->jsonSuccess('Entri kalender "'.$name.'" berhasil dihapus.');
    }
}
