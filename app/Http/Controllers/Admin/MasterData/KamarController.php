<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreKamarRequest;
use App\Http\Requests\MasterData\UpdateKamarRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Kamar;
use App\Support\ActionMessage;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KamarController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.kamar', [
            'title' => 'Master Data — Kamar',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = Kamar::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['kode', 'nama', 'blok'],
            'orderable' => ['nama', 'blok', 'sort_order', 'created_at'],
        ], function (Kamar $kamar) {
            $blocked = MasterDataUsage::kamarBlockedReason($kamar);
            $fields = array_merge(
                $kamar->only(['kode', 'nama', 'blok', 'kapasitas', 'sort_order']),
                ['is_active' => $kamar->is_active ? '1' : '0']
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.kamar.update', $kamar),
                    'form_target' => 'kamar-form',
                    'modal_target' => 'kamar-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.kamar.destroy', $kamar),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus kamar ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $kamar->nama],
                        ['label' => 'Blok', 'value' => $kamar->blok ?? '-'],
                    ],
                ];
            } else {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.kamar.update', $kamar),
                    'form_target' => 'kamar-form',
                    'modal_target' => 'kamar-modal',
                    'record' => $fields,
                ];
            }

            return [
                $kamar->kode ?? '-',
                $kamar->nama,
                $kamar->blok ?? '-',
                $kamar->kapasitas ?? '-',
                $this->badgeCell($kamar->is_active ? 'Aktif' : 'Nonaktif', $kamar->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKamarRequest $request): JsonResponse
    {
        $data = $request->validated();

        $kamar = Kamar::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Kamar berhasil ditambahkan', $kamar->nama),
            $kamar,
            201
        );
    }

    public function update(UpdateKamarRequest $request, Kamar $kamar): JsonResponse
    {
        $data = $request->validated();

        $kamar->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Kamar berhasil diperbarui', $kamar->nama),
            $kamar
        );
    }

    public function destroy(Kamar $kamar): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::kamarBlockedReason($kamar)) {
            return $this->jsonError('Kamar tidak dapat dihapus. '.$reason);
        }

        $name = $kamar->nama;
        $kamar->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Kamar berhasil dihapus', $name));
    }
}
