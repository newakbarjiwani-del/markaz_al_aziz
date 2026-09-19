<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTemplatePesanTagihanRequest;
use App\Http\Requests\Finance\UpdateTemplatePesanTagihanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TemplatePesanTagihan;
use App\Support\ActionMessage;
use App\Support\TagihanPesanKategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplatePesanTagihanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('finance.view');

        return view('admin.keuangan.template-pesan-tagihan', [
            'title' => 'Template Pesan Tagihan WA',
            'kategoriOptions' => TagihanPesanKategori::labels(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $query = TemplatePesanTagihan::query()
            ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->input('kategori')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->is_active === '1'));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nama', 'isi_pesan'],
            'orderable' => ['sort_order', 'nama', 'kategori', null, 'created_at'],
        ], function (TemplatePesanTagihan $template) {
            $fields = array_merge(
                $template->only(['nama', 'kategori', 'isi_pesan', 'sort_order']),
                ['is_active' => $template->is_active ? '1' : '0']
            );

            $preview = mb_strlen($template->isi_pesan) > 120
                ? mb_substr($template->isi_pesan, 0, 120).'…'
                : $template->isi_pesan;

            $actions = [
                'edit' => [
                    'update_url' => route('admin.keuangan.template-pesan-tagihan.update', $template),
                    'form_target' => 'template-pesan-tagihan-form',
                    'modal_target' => 'template-pesan-tagihan-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.keuangan.template-pesan-tagihan.destroy', $template),
                    'confirm_title' => 'Hapus Template',
                    'confirm_message' => 'Template pesan ini akan dihapus.',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $template->nama],
                        ['label' => 'Kategori', 'value' => $template->kategoriLabel()],
                    ],
                ],
            ];

            return [
                $template->sort_order,
                $template->nama,
                $this->badgeCell(
                    $template->kategoriLabel(),
                    $template->kategori === TagihanPesanKategori::LEWAT_JATUH_TEMPO
                        ? 'badge badge-red'
                        : 'badge badge-blue'
                ),
                $this->cell(e($preview), $preview, 'html'),
                $this->badgeCell($template->is_active ? 'Aktif' : 'Nonaktif', $template->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreTemplatePesanTagihanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $template = TemplatePesanTagihan::create([
            ...$data,
            'kategori' => TagihanPesanKategori::normalize($data['kategori']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Template pesan berhasil ditambahkan', $template->nama),
            $template,
            201
        );
    }

    public function update(UpdateTemplatePesanTagihanRequest $request, TemplatePesanTagihan $templatePesanTagihan): JsonResponse
    {
        $data = $request->validated();

        $templatePesanTagihan->update([
            ...$data,
            'kategori' => TagihanPesanKategori::normalize($data['kategori']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Template pesan berhasil diperbarui', $templatePesanTagihan->nama),
            $templatePesanTagihan
        );
    }

    public function destroy(TemplatePesanTagihan $templatePesanTagihan): JsonResponse
    {
        $this->authorize('finance.delete');

        $name = $templatePesanTagihan->nama;
        $templatePesanTagihan->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Template pesan berhasil dihapus', $name));
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $kategori = TagihanPesanKategori::normalize($request->query('kategori'));

        $query = TemplatePesanTagihan::query()->active()->ordered();
        if ($kategori !== null) {
            $query->forKategori($kategori);
        }

        $items = $query->get(['id', 'nama', 'kategori'])->map(fn (TemplatePesanTagihan $row) => [
            'id' => $row->id,
            'nama' => $row->nama,
            'kategori' => $row->kategori,
            'kategori_label' => $row->kategoriLabel(),
        ])->values();

        return $this->jsonSuccess('OK', ['items' => $items]);
    }
}
