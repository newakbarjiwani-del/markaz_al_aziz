<?php

namespace App\Http\Controllers\Admin\AchievementViolation;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestasiPelanggaran\StoreKatalogPelanggaranRequest;
use App\Http\Requests\PrestasiPelanggaran\UpdateKatalogPelanggaranRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\JenisPelanggaran;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\AjaxSelect;
use App\Support\MasterDataUsage;
use App\Support\PelanggaranLevel;
use App\Support\PelanggaranSanction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KatalogPelanggaranController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.prestasi-pelanggaran.katalog-pelanggaran', [
            'title' => 'Katalog Pelanggaran',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('katalog-pelanggaran.view');

        $query = JenisPelanggaran::query()
            ->when($request->filled('level'), fn ($q) => $q->where('level', PelanggaranLevel::normalize($request->input('level'))))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama', 'bidang', 'kode'],
            // Positionally aligned with the DataTable columns:
            // [Level, Bidang, Nama, Point, Sanksi, Status, Aksi].
            // Level sorts semantically (ringan -> sedang -> berat), not alphabetically.
            'orderable' => [
                DB::raw("CASE level WHEN 'ringan' THEN 1 WHEN 'sedang' THEN 2 WHEN 'berat' THEN 3 ELSE 0 END"),
                'bidang',
                'nama',
                'point',
            ],
        ], function (JenisPelanggaran $jenis) {
            $blocked = MasterDataUsage::jenisPelanggaranBlockedReason($jenis);

            $fields = array_merge(
                $jenis->only(['sekolah_id', 'kode', 'level', 'bidang', 'nama', 'point', 'sanction', 'keterangan', 'sort_order']),
                ['is_active' => $jenis->is_active ? '1' : '0']
            );

            $actions = [];
            $actions['edit'] = [
                'update_url' => route('admin.prestasi-pelanggaran.katalog-pelanggaran.update', $jenis),
                'form_target' => 'katalog-pelanggaran-form',
                'modal_target' => 'katalog-pelanggaran-modal',
                'record' => $fields,
            ];

            if ($blocked === null) {
                $actions['delete'] = [
                    'url' => route('admin.prestasi-pelanggaran.katalog-pelanggaran.destroy', $jenis),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus jenis pelanggaran ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $jenis->nama],
                        ['label' => 'Bidang', 'value' => $jenis->bidang],
                        ['label' => 'Point', 'value' => (string) $jenis->point],
                    ],
                ];
            } else {
                $actions['edit']['partial_edit'] = true;
                $actions['edit']['editable_fields'] = 'sekolah_id,kode,level,bidang,point,sanction,keterangan,sort_order,is_active';
            }

            return [
                $this->badgeCell($jenis->levelLabel(), match ($jenis->level) {
                    PelanggaranLevel::BERAT => 'badge badge-red',
                    PelanggaranLevel::SEDANG => 'badge badge-amber',
                    default => 'badge badge-green',
                }),
                $jenis->bidang,
                $this->cell($jenis->nama, $jenis->nama),
                $jenis->point > 0 ? $jenis->point : '-',
                $jenis->sanction !== null
                    ? $this->badgeCell($jenis->sanction, 'badge badge-blue')
                    : '-',
                $this->badgeCell($jenis->is_active ? 'Aktif' : 'Nonaktif', $jenis->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKatalogPelanggaranRequest $request): JsonResponse
    {
        $data = $request->validated();

        $jenis = JenisPelanggaran::create([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'sanction' => $request->filled('sanction') ? PelanggaranSanction::normalize($request->input('sanction')) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis pelanggaran berhasil ditambahkan', $jenis->nama),
            $jenis,
            201
        );
    }

    public function update(Request $request, JenisPelanggaran $jenisPelanggaran): JsonResponse
    {
        if (MasterDataUsage::jenisPelanggaranBlockedReason($jenisPelanggaran)) {
            $this->authorize('katalog-pelanggaran.update');
            $request->validate(UpdateKatalogPelanggaranRequest::limitedRules());

            return $this->updateInUse($request, $jenisPelanggaran);
        }

        $data = $this->validateWithFormRequest($request, UpdateKatalogPelanggaranRequest::class);

        $jenisPelanggaran->update([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'sanction' => $request->filled('sanction') ? PelanggaranSanction::normalize($request->input('sanction')) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis pelanggaran berhasil diperbarui', $jenisPelanggaran->nama),
            $jenisPelanggaran
        );
    }

    private function updateInUse(Request $request, JenisPelanggaran $jenisPelanggaran): JsonResponse
    {
        $jenisPelanggaran->update([
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'kode' => $request->input('kode'),
            'level' => PelanggaranLevel::normalize($request->input('level')),
            'bidang' => $request->input('bidang'),
            'point' => $request->filled('point') ? $request->integer('point') : 0,
            'sanction' => $request->filled('sanction') ? PelanggaranSanction::normalize($request->input('sanction')) : null,
            'keterangan' => $request->input('keterangan'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Katalog pelanggaran diperbarui (nama tidak dapat diubah karena sudah dipakai)', $jenisPelanggaran->nama),
            $jenisPelanggaran
        );
    }

    public function destroy(JenisPelanggaran $jenisPelanggaran): JsonResponse
    {
        $this->authorize('katalog-pelanggaran.delete');

        if ($reason = MasterDataUsage::jenisPelanggaranBlockedReason($jenisPelanggaran)) {
            return $this->jsonError('Jenis pelanggaran tidak dapat dihapus. '.$reason);
        }

        $name = $jenisPelanggaran->nama;
        $jenisPelanggaran->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Jenis pelanggaran berhasil dihapus', $name));
    }

    /**
     * Select2 AJAX lookup for the pelanggaran forms. Extra fields (nama, point)
     * ride along so the client can auto-fill judul + point on selection.
     */
    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) ($request->get('term') ?? $request->get('q', '')));

        $query = JenisPelanggaran::query()
            ->active()
            ->ordered();

        if (mb_strlen($q) >= 1) {
            $query->where(function ($query) use ($q) {
                $query->where('nama', 'like', "%{$q}%")
                    ->orWhere('bidang', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");

                $level = PelanggaranLevel::normalize($q);
                if ($level !== null) {
                    $query->orWhere('level', $level);
                }
            });
        }

        $rows = $query->limit(20)->get();

        $results = $rows->map(fn (JenisPelanggaran $jenis) => [
            'id' => $jenis->id,
            'text' => "{$jenis->nama} — {$jenis->bidang} ({$jenis->levelLabel()})",
            'nama' => $jenis->nama,
            'point' => (int) $jenis->point,
            'level' => $jenis->level,
        ])->all();

        return AjaxSelect::respond($results);
    }

    public function lookupShow(JenisPelanggaran $jenisPelanggaran): JsonResponse
    {
        return response()->json([
            'id' => $jenisPelanggaran->id,
            'text' => "{$jenisPelanggaran->nama} — {$jenisPelanggaran->bidang} ({$jenisPelanggaran->levelLabel()})",
            'nama' => $jenisPelanggaran->nama,
            'point' => (int) $jenisPelanggaran->point,
            'level' => $jenisPelanggaran->level,
        ]);
    }

    /**
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $formRequestClass
     * @return array<string, mixed>
     */
    private function validateWithFormRequest(Request $request, string $formRequestClass): array
    {
        /** @var \Illuminate\Foundation\Http\FormRequest $formRequest */
        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }
}
