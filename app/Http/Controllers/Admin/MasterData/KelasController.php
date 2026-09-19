<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreKelasRequest;
use App\Http\Requests\MasterData\UpdateKelasRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Support\AdminSchoolScope;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.kelas', [
            'title' => 'Master Data — Kelas',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = Kelas::query()
            ->with('sekolah')
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->sekolah_id))
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['kelas', 'kelompok', 'unit', 'wali_kelas'],
            'orderable' => ['created_at', 'unit', 'kelas', 'kelompok', 'created_at'],
        ], function (Kelas $kelas) {
            $blocked = MasterDataUsage::kelasBlockedReason($kelas);
            $fields = array_merge(
                $kelas->only(['sekolah_id', 'kelas', 'kelompok', 'unit', 'jenjang', 'wali_kelas']),
                ['is_active' => $kelas->is_active ? '1' : '0']
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.kelas.update', $kelas),
                    'form_target' => 'kelas-form',
                    'modal_target' => 'kelas-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.kelas.destroy', $kelas),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus kelas ini?',
                    'confirm_detail' => [
                        ['label' => 'Kelas', 'value' => $kelas->displayLabel()],
                        ['label' => 'Sekolah', 'value' => $kelas->sekolah?->name ?? '-'],
                        ['label' => 'Unit', 'value' => $kelas->unit ?? '-'],
                        ['label' => 'Wali Kelas', 'value' => $kelas->wali_kelas ?? '-'],
                    ],
                ];
            }

            return [
                $kelas->sekolah?->name ?? '-',
                $kelas->unit ?? '-',
                $kelas->kelas !== null ? (string) $kelas->kelas : '-',
                $kelas->kelompok ?? '-',
                $kelas->wali_kelas ?? '-',
                $this->badgeCell($kelas->is_active ? 'Aktif' : 'Nonaktif', $kelas->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', $actions ? ['actions' => $actions] : null, 'action'),
            ];
        });
    }

    public function store(StoreKelasRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveForStore($request);

        $kelas = Kelas::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Kelas berhasil ditambahkan.', $kelas, 201);
    }

    public function update(UpdateKelasRequest $request, Kelas $kelas): JsonResponse
    {
        if ($reason = MasterDataUsage::kelasBlockedReason($kelas)) {
            return $this->jsonError('Kelas tidak dapat diubah. '.$reason);
        }

        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveForStore($request);

        $kelas->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess('Kelas berhasil diperbarui.', $kelas);
    }

    public function destroy(Kelas $kelas): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::kelasBlockedReason($kelas)) {
            return $this->jsonError('Kelas tidak dapat dihapus. '.$reason);
        }

        $kelas->delete();

        return $this->jsonSuccess('Kelas berhasil dihapus.');
    }
}
