<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StoreKatalogPrestasiRequest;
use App\Http\Requests\PrestasiPelanggaran\UpdateKatalogPrestasiRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\JenisPrestasi;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\AjaxSelect;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogPrestasiController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.katalog-prestasi', [
            'title' => 'Katalog Prestasi',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('katalog-prestasi.view');

        $query = JenisPrestasi::query()
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama', 'bidang', 'kode'],
            'orderable' => ['bidang', 'nama', 'point', null, null],
        ], function (JenisPrestasi $jenis) {
            $blocked = MasterDataUsage::jenisPrestasiBlockedReason($jenis);

            $fields = array_merge(
                $jenis->only(['sekolah_id', 'kode', 'bidang', 'nama', 'point', 'keterangan', 'sort_order']),
                ['is_active' => $jenis->is_active ? '1' : '0']
            );

            $actions = [];
            $actions['edit'] = [
                'update_url' => route('admin.prestasi-pelanggaran.katalog-prestasi.update', $jenis),
                'form_target' => 'katalog-prestasi-form',
                'modal_target' => 'katalog-prestasi-modal',
                'record' => $fields,
            ];

            if ($blocked === null) {
                $actions['delete'] = [
                    'url' => route('admin.prestasi-pelanggaran.katalog-prestasi.destroy', $jenis),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus jenis prestasi ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $jenis->nama],
                        ['label' => 'Bidang', 'value' => $jenis->bidang ?: '-'],
                        ['label' => 'Point', 'value' => (string) $jenis->point],
                    ],
                ];
            } else {
                $actions['edit']['partial_edit'] = true;
                $actions['edit']['editable_fields'] = 'sekolah_id,kode,bidang,point,keterangan,sort_order,is_active';
            }

            return [
                $jenis->bidang ?: '-',
                $this->cell($jenis->nama, $jenis->nama),
                $jenis->point > 0 ? $jenis->point : '-',
                $this->badgeCell($jenis->is_active ? 'Aktif' : 'Nonaktif', $jenis->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKatalogPrestasiRequest $request): JsonResponse
    {
        $data = $request->validated();

        $jenis = JenisPrestasi::create([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis prestasi berhasil ditambahkan', $jenis->nama),
            $jenis,
            201
        );
    }

    public function update(Request $request, JenisPrestasi $jenisPrestasi): JsonResponse
    {
        if (MasterDataUsage::jenisPrestasiBlockedReason($jenisPrestasi)) {
            $this->authorize('katalog-prestasi.update');
            $request->validate(UpdateKatalogPrestasiRequest::limitedRules());

            return $this->updateInUse($request, $jenisPrestasi);
        }

        $data = $this->validateWithFormRequest($request, UpdateKatalogPrestasiRequest::class);

        $jenisPrestasi->update([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis prestasi berhasil diperbarui', $jenisPrestasi->nama),
            $jenisPrestasi
        );
    }

    private function updateInUse(Request $request, JenisPrestasi $jenisPrestasi): JsonResponse
    {
        $jenisPrestasi->update([
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'kode' => $request->input('kode'),
            'bidang' => $request->input('bidang'),
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'keterangan' => $request->input('keterangan'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Katalog prestasi diperbarui (nama tidak dapat diubah karena sudah dipakai)', $jenisPrestasi->nama),
            $jenisPrestasi
        );
    }

    public function destroy(JenisPrestasi $jenisPrestasi): JsonResponse
    {
        $this->authorize('katalog-prestasi.delete');

        if ($reason = MasterDataUsage::jenisPrestasiBlockedReason($jenisPrestasi)) {
            return $this->jsonError('Jenis prestasi tidak dapat dihapus. '.$reason);
        }

        $name = $jenisPrestasi->nama;
        $jenisPrestasi->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Jenis prestasi berhasil dihapus', $name));
    }

    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        $query = JenisPrestasi::query()
            ->active()
            ->ordered();

        if (mb_strlen($q) >= 1) {
            $query->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('bidang', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate(20);

        return AjaxSelect::fromPaginator($paginator, function (JenisPrestasi $jenis) {
            return [
                'id' => $jenis->id,
                'text' => $jenis->nama.($jenis->bidang ? ' — '.$jenis->bidang : ''),
                'nama' => $jenis->nama,
                'point' => $jenis->point,
            ];
        });
    }

    public function lookupShow(JenisPrestasi $jenisPrestasi): JsonResponse
    {
        return response()->json([
            'id' => $jenisPrestasi->id,
            'text' => $jenisPrestasi->nama.($jenisPrestasi->bidang ? ' — '.$jenisPrestasi->bidang : ''),
            'nama' => $jenisPrestasi->nama,
            'point' => $jenisPrestasi->point,
        ]);
    }
}
