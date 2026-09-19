<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\StoreSpmbBeritaRequest;
use App\Http\Requests\Spmb\UpdateSpmbBeritaRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\SpmbBerita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeritaController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('spmb.view');

        return view('admin.spmb.berita', [
            'title' => 'Berita SPMB',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('spmb.view');

        $query = SpmbBerita::query()
            ->when($request->filled('is_published'), fn ($q) => $q->where('is_published', $request->boolean('is_published')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title', 'slug', 'body'],
            'orderable' => ['title', 'published_at', 'is_published'],
        ], function (SpmbBerita $berita) {
            $fields = array_merge(
                $berita->only(['title', 'body', 'slug']),
                [
                    'published_at' => $berita->published_at?->format('Y-m-d\TH:i'),
                    'is_published' => $berita->is_published ? '1' : '0',
                ]
            );

            return [
                $berita->title,
                $berita->slug,
                $this->dateCell($berita->published_at),
                $this->badgeCell(
                    $berita->is_published ? 'Terbit' : 'Draf',
                    $berita->is_published ? 'badge badge-green' : 'badge badge-amber'
                ),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.spmb.berita.update', $berita),
                            'form_target' => 'spmb-berita-form',
                            'modal_target' => 'spmb-berita-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.spmb.berita.destroy', $berita),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus berita ini?',
                            'confirm_detail' => [
                                ['label' => 'Judul', 'value' => $berita->title],
                                ['label' => 'Slug', 'value' => $berita->slug],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreSpmbBeritaRequest $request): JsonResponse
    {
        $data = $request->safe()->except('cover');
        $coverPath = null;

        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('spmb/berita', 'public');
        }

        $berita = SpmbBerita::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['title']),
            'cover_path' => $coverPath,
            'is_published' => $request->boolean('is_published'),
        ]);

        return $this->jsonSuccess('Berita berhasil ditambahkan.', $berita, 201);
    }

    public function update(UpdateSpmbBeritaRequest $request, SpmbBerita $berita): JsonResponse
    {
        $data = $request->safe()->except('cover');
        $payload = [
            ...$data,
            'slug' => $this->uniqueSlug($data['title'], $berita->id),
            'is_published' => $request->boolean('is_published'),
        ];

        if ($request->hasFile('cover')) {
            if ($berita->cover_path && Storage::disk('public')->exists($berita->cover_path)) {
                Storage::disk('public')->delete($berita->cover_path);
            }
            $payload['cover_path'] = $request->file('cover')->store('spmb/berita', 'public');
        }

        $berita->update($payload);

        return $this->jsonSuccess('Berita berhasil diperbarui.', $berita->fresh());
    }

    public function destroy(SpmbBerita $berita): JsonResponse
    {
        $this->authorize('spmb.delete');

        if ($berita->cover_path && Storage::disk('public')->exists($berita->cover_path)) {
            Storage::disk('public')->delete($berita->cover_path);
        }

        $title = $berita->title;
        $berita->delete();

        return $this->jsonSuccess('Berita "'.$title.'" berhasil dihapus.');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $suffix = 1;

        while (
            SpmbBerita::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
