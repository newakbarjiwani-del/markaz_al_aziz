<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreKatalogPotonganRequest;
use App\Http\Requests\Finance\UpdateKatalogPotonganRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\JenisPotongan;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\MasterDataUsage;
use App\Support\PotonganTipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogPotonganController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('katalog-potongan.view');

        return view('admin.keuangan.katalog-potongan', [
            'title' => 'Katalog Potongan',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('katalog-potongan.view');

        $query = JenisPotongan::query()
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama', 'kode'],
            'orderable' => ['sort_order', 'nama', 'tipe_default', 'nilai_default', null, null],
        ], function (JenisPotongan $jenis) {
            $blocked = MasterDataUsage::jenisPotonganBlockedReason($jenis);

            $fields = array_merge(
                $jenis->only(['sekolah_id', 'kode', 'nama', 'tipe_default', 'nilai_default', 'keterangan', 'sort_order']),
                ['is_active' => $jenis->is_active ? '1' : '0']
            );

            $actions = [];
            $actions['edit'] = [
                'update_url' => route('admin.keuangan.katalog-potongan.update', $jenis),
                'form_target' => 'katalog-potongan-form',
                'modal_target' => 'katalog-potongan-modal',
                'record' => $fields,
            ];

            if ($blocked === null) {
                $actions['delete'] = [
                    'url' => route('admin.keuangan.katalog-potongan.destroy', $jenis),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus jenis potongan ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $jenis->nama],
                        ['label' => 'Urutan', 'value' => (string) $jenis->sort_order],
                    ],
                ];
            } else {
                $actions['edit']['partial_edit'] = true;
                $actions['edit']['editable_fields'] = 'sekolah_id,kode,tipe_default,nilai_default,keterangan,sort_order,is_active';
            }

            $nilaiLabel = PotonganTipe::normalize($jenis->tipe_default) === PotonganTipe::PERCENT
                ? $jenis->nilai_default.'%'
                : 'Rp '.number_format($jenis->nilai_default, 0, ',', '.');

            return [
                $jenis->sort_order,
                $this->cell($jenis->nama, $jenis->nama),
                PotonganTipe::label($jenis->tipe_default),
                $nilaiLabel,
                $this->badgeCell($jenis->is_active ? 'Aktif' : 'Nonaktif', $jenis->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreKatalogPotonganRequest $request): JsonResponse
    {
        $data = $request->validated();

        $jenis = JenisPotongan::create([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'tipe_default' => PotonganTipe::normalize($data['tipe_default']),
            'nilai_default' => (int) $data['nilai_default'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis potongan berhasil ditambahkan', $jenis->nama),
            $jenis,
            201
        );
    }

    public function update(Request $request, JenisPotongan $jenisPotongan): JsonResponse
    {
        if (MasterDataUsage::jenisPotonganBlockedReason($jenisPotongan)) {
            $this->authorize('katalog-potongan.update');
            $request->validate(UpdateKatalogPotonganRequest::limitedRules());

            return $this->updateInUse($request, $jenisPotongan);
        }

        $data = $this->validateWithFormRequest($request, UpdateKatalogPotonganRequest::class);

        $jenisPotongan->update([
            ...$data,
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'tipe_default' => PotonganTipe::normalize($data['tipe_default']),
            'nilai_default' => (int) $data['nilai_default'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis potongan berhasil diperbarui', $jenisPotongan->nama),
            $jenisPotongan
        );
    }

    private function updateInUse(Request $request, JenisPotongan $jenisPotongan): JsonResponse
    {
        $jenisPotongan->update([
            'sekolah_id' => $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null,
            'kode' => $request->input('kode'),
            'tipe_default' => PotonganTipe::normalize($request->input('tipe_default')),
            'nilai_default' => (int) $request->input('nilai_default'),
            'keterangan' => $request->input('keterangan'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Katalog potongan diperbarui (nama tidak dapat diubah karena sudah dipakai)', $jenisPotongan->nama),
            $jenisPotongan
        );
    }

    public function destroy(JenisPotongan $jenisPotongan): JsonResponse
    {
        $this->authorize('katalog-potongan.delete');

        if ($reason = MasterDataUsage::jenisPotonganBlockedReason($jenisPotongan)) {
            return $this->jsonError('Jenis potongan tidak dapat dihapus. '.$reason);
        }

        $name = $jenisPotongan->nama;
        $jenisPotongan->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Jenis potongan berhasil dihapus', $name));
    }
}
