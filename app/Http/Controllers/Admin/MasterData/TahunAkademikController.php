<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreTahunAkademikRequest;
use App\Http\Requests\MasterData\UpdateTahunAkademikRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahunAkademik;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.tahun-akademik', [
            'title' => 'Master Data — Tahun Akademik',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = TahunAkademik::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name'],
            'orderable' => ['name', 'created_at', 'created_at'],
        ], function (TahunAkademik $tahunAkademik) {
            $blocked = MasterDataUsage::tahunAkademikBlockedReason($tahunAkademik);
            $fields = array_merge(
                $tahunAkademik->only(['name']),
                ['is_active' => $tahunAkademik->is_active ? '1' : '0']
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.tahun-akademik.update', $tahunAkademik),
                    'form_target' => 'tahun-akademik-form',
                    'modal_target' => 'tahun-akademik-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.tahun-akademik.destroy', $tahunAkademik),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus tahun akademik ini?',
                    'confirm_detail' => [
                        ['label' => 'Tahun Akademik', 'value' => $tahunAkademik->name],
                        ['label' => 'Status', 'value' => $tahunAkademik->is_active ? 'Aktif' : 'Nonaktif'],
                    ],
                ];
            }

            return [
                $tahunAkademik->name,
                $this->badgeCell($tahunAkademik->is_active ? 'Aktif' : 'Nonaktif', $tahunAkademik->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', $actions ? ['actions' => $actions] : null, 'action'),
            ];
        });
    }

    public function store(StoreTahunAkademikRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isActive = $request->boolean('is_active');

        $tahunAkademik = DB::transaction(function () use ($data, $isActive) {
            if ($isActive) {
                TahunAkademik::query()->update(['is_active' => false]);
            }

            return TahunAkademik::create([
                ...$data,
                'is_active' => $isActive,
            ]);
        });

        return $this->jsonSuccess('Tahun akademik berhasil ditambahkan.', $tahunAkademik, 201);
    }

    public function update(UpdateTahunAkademikRequest $request, TahunAkademik $tahunAkademik): JsonResponse
    {
        if ($reason = MasterDataUsage::tahunAkademikBlockedReason($tahunAkademik)) {
            return $this->jsonError('Tahun akademik tidak dapat diubah. '.$reason);
        }

        $data = $request->validated();
        $isActive = $request->boolean('is_active');

        DB::transaction(function () use ($tahunAkademik, $data, $isActive): void {
            if ($isActive) {
                TahunAkademik::query()
                    ->where('id', '!=', $tahunAkademik->id)
                    ->update(['is_active' => false]);
            }

            $tahunAkademik->update([
                ...$data,
                'is_active' => $isActive,
            ]);
        });

        return $this->jsonSuccess('Tahun akademik berhasil diperbarui.', $tahunAkademik);
    }

    public function destroy(TahunAkademik $tahunAkademik): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::tahunAkademikBlockedReason($tahunAkademik)) {
            return $this->jsonError('Tahun akademik tidak dapat dihapus. '.$reason);
        }

        $tahunAkademik->delete();

        return $this->jsonSuccess('Tahun akademik berhasil dihapus.');
    }
}
