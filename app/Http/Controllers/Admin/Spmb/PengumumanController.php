<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\StoreSpmbPengumumanRequest;
use App\Http\Requests\Spmb\UpdateSpmbPengumumanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\SpmbPengumuman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('spmb.view');

        return view('admin.spmb.pengumuman', [
            'title' => 'Pengumuman SPMB',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('spmb.view');

        $query = SpmbPengumuman::query()
            ->when($request->filled('is_published'), fn ($q) => $q->where('is_published', $request->boolean('is_published')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title', 'body'],
            'orderable' => ['title', 'published_at', 'is_published'],
        ], function (SpmbPengumuman $pengumuman) {
            $fields = array_merge(
                $pengumuman->only(['title', 'body']),
                [
                    'published_at' => $pengumuman->published_at?->format('Y-m-d\TH:i'),
                    'is_published' => $pengumuman->is_published ? '1' : '0',
                ]
            );

            return [
                $pengumuman->title,
                Str::limit(strip_tags($pengumuman->body), 80),
                $this->dateCell($pengumuman->published_at),
                $this->badgeCell(
                    $pengumuman->is_published ? 'Terbit' : 'Draf',
                    $pengumuman->is_published ? 'badge badge-green' : 'badge badge-amber'
                ),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.spmb.pengumuman.update', $pengumuman),
                            'form_target' => 'spmb-pengumuman-form',
                            'modal_target' => 'spmb-pengumuman-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.spmb.pengumuman.destroy', $pengumuman),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus pengumuman ini?',
                            'confirm_detail' => [
                                ['label' => 'Judul', 'value' => $pengumuman->title],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreSpmbPengumumanRequest $request): JsonResponse
    {
        $data = $request->validated();

        $pengumuman = SpmbPengumuman::create([
            ...$data,
            'is_published' => $request->boolean('is_published'),
        ]);

        return $this->jsonSuccess('Pengumuman berhasil ditambahkan.', $pengumuman, 201);
    }

    public function update(UpdateSpmbPengumumanRequest $request, SpmbPengumuman $pengumuman): JsonResponse
    {
        $data = $request->validated();

        $pengumuman->update([
            ...$data,
            'is_published' => $request->boolean('is_published'),
        ]);

        return $this->jsonSuccess('Pengumuman berhasil diperbarui.', $pengumuman);
    }

    public function destroy(SpmbPengumuman $pengumuman): JsonResponse
    {
        $this->authorize('spmb.delete');

        $title = $pengumuman->title;
        $pengumuman->delete();

        return $this->jsonSuccess('Pengumuman "'.$title.'" berhasil dihapus.');
    }
}
