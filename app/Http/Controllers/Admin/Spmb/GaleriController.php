<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\StoreSpmbGaleriRequest;
use App\Http\Requests\Spmb\UpdateSpmbGaleriRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\SpmbGaleriItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GaleriController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('spmb.view');

        return view('admin.spmb.galeri', [
            'title' => 'Galeri SPMB',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('spmb.view');

        $query = SpmbGaleriItem::query()
            ->when($request->filled('is_published'), fn ($q) => $q->where('is_published', $request->boolean('is_published')))
            ->orderBy('sort_order')
            ->orderByDesc('id');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title', 'caption'],
            'orderable' => ['sort_order', 'title', 'is_published'],
        ], function (SpmbGaleriItem $item) {
            $fields = array_merge(
                $item->only(['title', 'caption', 'sort_order']),
                ['is_published' => $item->is_published ? '1' : '0']
            );

            $thumb = $item->image_path
                ? '<img src="'.e(asset('storage/'.$item->image_path)).'" alt="" class="h-12 w-16 rounded object-cover">'
                : '-';

            return [
                $this->cell($thumb, $item->title, 'text'),
                $item->title,
                $item->caption ? Str::limit($item->caption, 60) : '-',
                $item->sort_order,
                $this->badgeCell(
                    $item->is_published ? 'Terbit' : 'Draf',
                    $item->is_published ? 'badge badge-green' : 'badge badge-amber'
                ),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.spmb.galeri.update', $item),
                            'form_target' => 'spmb-galeri-form',
                            'modal_target' => 'spmb-galeri-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.spmb.galeri.destroy', $item),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus item galeri ini?',
                            'confirm_detail' => [
                                ['label' => 'Judul', 'value' => $item->title],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreSpmbGaleriRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $path = $request->file('image')->store('spmb/galeri', 'public');

        $item = SpmbGaleriItem::create([
            ...$data,
            'image_path' => $path,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published', true),
        ]);

        return $this->jsonSuccess('Item galeri berhasil ditambahkan.', $item, 201);
    }

    public function update(UpdateSpmbGaleriRequest $request, SpmbGaleriItem $galeri): JsonResponse
    {
        $data = $request->safe()->except('image');
        $payload = [
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
        ];

        if ($request->hasFile('image')) {
            if ($galeri->image_path && Storage::disk('public')->exists($galeri->image_path)) {
                Storage::disk('public')->delete($galeri->image_path);
            }
            $payload['image_path'] = $request->file('image')->store('spmb/galeri', 'public');
        }

        $galeri->update($payload);

        return $this->jsonSuccess('Item galeri berhasil diperbarui.', $galeri->fresh());
    }

    public function destroy(SpmbGaleriItem $galeri): JsonResponse
    {
        $this->authorize('spmb.delete');

        if ($galeri->image_path && Storage::disk('public')->exists($galeri->image_path)) {
            Storage::disk('public')->delete($galeri->image_path);
        }

        $title = $galeri->title;
        $galeri->delete();

        return $this->jsonSuccess('Item galeri "'.$title.'" berhasil dihapus.');
    }
}
