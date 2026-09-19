<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreSekolahRequest;
use App\Http\Requests\MasterData\UpdateSekolahRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Sekolah;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SekolahController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.sekolah', [
            'title' => 'Master Data — Sekolah',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = Sekolah::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['code', 'name', 'address', 'phone'],
            'orderable' => ['code', 'name', 'created_at'],
        ], function (Sekolah $sekolah) {
            $blocked = MasterDataUsage::sekolahBlockedReason($sekolah);
            $fields = array_merge(
                $sekolah->only(['code', 'name', 'address', 'phone']),
                ['is_active' => $sekolah->is_active ? '1' : '0']
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.sekolah.update', $sekolah),
                    'form_target' => 'sekolah-form',
                    'modal_target' => 'sekolah-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.sekolah.destroy', $sekolah),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus sekolah ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $sekolah->name],
                        ['label' => 'Kode', 'value' => $sekolah->code],
                        ['label' => 'Alamat', 'value' => $sekolah->address ?? '-'],
                    ],
                ];
            }

            return [
                $sekolah->code,
                $sekolah->name,
                $sekolah->address ?? '-',
                $sekolah->phone ?? '-',
                $this->badgeCell($sekolah->is_active ? 'Aktif' : 'Nonaktif', $sekolah->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', $actions ? ['actions' => $actions] : null, 'action'),
            ];
        });
    }

    public function store(StoreSekolahRequest $request): JsonResponse
    {
        $sekolah = Sekolah::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess('Sekolah berhasil ditambahkan.', $sekolah, 201);
    }

    public function update(UpdateSekolahRequest $request, Sekolah $sekolah): JsonResponse
    {
        if ($reason = MasterDataUsage::sekolahBlockedReason($sekolah)) {
            return $this->jsonError('Sekolah tidak dapat diubah. '.$reason);
        }

        $sekolah->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess('Sekolah berhasil diperbarui.', $sekolah);
    }

    public function destroy(Sekolah $sekolah): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::sekolahBlockedReason($sekolah)) {
            return $this->jsonError('Sekolah tidak dapat dihapus. '.$reason);
        }

        $sekolah->delete();

        return $this->jsonSuccess('Sekolah berhasil dihapus.');
    }
}
