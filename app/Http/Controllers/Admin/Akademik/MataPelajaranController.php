<?php

namespace App\Http\Controllers\Admin\Akademik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreMataPelajaranRequest;
use App\Http\Requests\Akademik\UpdateMataPelajaranRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\MataPelajaran;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('akademik.view');

        return view('admin.akademik.mata-pelajaran', [
            'title' => 'Mata Pelajaran',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('akademik.view');

        $query = MataPelajaran::query()
            ->with('sekolah')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['code', 'name', 'kelompok'],
            'orderable' => ['code', 'name', 'kelompok', 'is_active'],
        ], function (MataPelajaran $mapel) {
            $fields = array_merge(
                $mapel->only(['code', 'name', 'kelompok', 'sekolah_id']),
                ['is_active' => $mapel->is_active ? '1' : '0']
            );

            $actions = [
                'edit' => [
                    'update_url' => route('admin.akademik.mata-pelajaran.update', $mapel),
                    'form_target' => 'akademik-mapel-form',
                    'modal_target' => 'akademik-mapel-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.akademik.mata-pelajaran.destroy', $mapel),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus mata pelajaran ini?',
                    'confirm_detail' => [
                        ['label' => 'Kode', 'value' => $mapel->code ?: '-'],
                        ['label' => 'Nama', 'value' => $mapel->name],
                    ],
                ],
            ];

            return [
                $mapel->code ?: '-',
                $mapel->name,
                $mapel->kelompok ?: '-',
                $mapel->sekolah?->name ?? 'Semua sekolah',
                $this->badgeCell(
                    $mapel->is_active ? 'Aktif' : 'Nonaktif',
                    $mapel->is_active ? 'badge badge-green' : 'badge badge-red'
                ),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreMataPelajaranRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $mapel = MataPelajaran::create($data);

        return $this->jsonSuccess('Mata pelajaran berhasil ditambahkan.', $mapel, 201);
    }

    public function update(UpdateMataPelajaranRequest $request, MataPelajaran $mataPelajaran): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active');

        $mataPelajaran->update($data);

        return $this->jsonSuccess('Mata pelajaran berhasil diperbarui.', $mataPelajaran->fresh());
    }

    public function destroy(MataPelajaran $mataPelajaran): JsonResponse
    {
        $this->authorize('akademik.delete');

        $name = $mataPelajaran->name;
        $mataPelajaran->delete();

        return $this->jsonSuccess('Mata pelajaran "'.$name.'" berhasil dihapus.');
    }
}
