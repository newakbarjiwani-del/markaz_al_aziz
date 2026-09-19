<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreStatusSantriRequest;
use App\Http\Requests\MasterData\UpdateStatusSantriRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\StatusSantri;
use App\Support\ActionMessage;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusSantriController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.status-santri', [
            'title' => 'Master Data — Status Santri',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = StatusSantri::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama'],
            'orderable' => ['nama', 'sort_order', 'created_at'],
        ], function (StatusSantri $statusSantri) {
            $blocked = MasterDataUsage::statusSantriBlockedReason($statusSantri);
            $fields = array_merge(
                $statusSantri->only(['nama', 'sort_order']),
                ['is_active' => $statusSantri->is_active ? '1' : '0']
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.status-santri.update', $statusSantri),
                    'form_target' => 'status-santri-form',
                    'modal_target' => 'status-santri-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.status-santri.destroy', $statusSantri),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus status santri ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $statusSantri->nama],
                    ],
                ];
            } else {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.status-santri.update', $statusSantri),
                    'form_target' => 'status-santri-form',
                    'modal_target' => 'status-santri-modal',
                    'record' => $fields,
                ];
            }

            return [
                $statusSantri->nama,
                $statusSantri->sort_order,
                $this->badgeCell($statusSantri->is_active ? 'Aktif' : 'Nonaktif', $statusSantri->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreStatusSantriRequest $request): JsonResponse
    {
        $data = $request->validated();

        $statusSantri = StatusSantri::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Status santri berhasil ditambahkan', $statusSantri->nama),
            $statusSantri,
            201
        );
    }

    public function update(UpdateStatusSantriRequest $request, StatusSantri $statusSantri): JsonResponse
    {
        $data = $request->validated();

        $statusSantri->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Status santri berhasil diperbarui', $statusSantri->nama),
            $statusSantri
        );
    }

    public function destroy(StatusSantri $statusSantri): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::statusSantriBlockedReason($statusSantri)) {
            return $this->jsonError('Status santri tidak dapat dihapus. '.$reason);
        }

        $name = $statusSantri->nama;
        $statusSantri->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Status santri berhasil dihapus', $name));
    }
}
