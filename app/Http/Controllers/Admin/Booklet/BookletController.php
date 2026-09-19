<?php

namespace App\Http\Controllers\Admin\Booklet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booklet\StoreBookletPageRequest;
use App\Http\Requests\Booklet\StoreBookletRequest;
use App\Http\Requests\Booklet\UpdateBookletRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Booklet;
use App\Models\BookletPage;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookletController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('booklet.view');

        return view('admin.booklet.index', [
            'title' => 'Booklet Sekolah',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('booklet.view');

        $query = Booklet::query()
            ->with('sekolah')
            ->withCount('pages')
            ->when($request->filled('is_published'), fn ($q) => $q->where('is_published', $request->boolean('is_published')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['title', 'slug', 'summary'],
            'orderable' => ['title', 'published_at', 'is_published'],
        ], function (Booklet $booklet) {
            $fields = array_merge(
                $booklet->only(['title', 'summary', 'sekolah_id']),
                [
                    'published_at' => $booklet->published_at?->format('Y-m-d\TH:i'),
                    'is_published' => $booklet->is_published ? '1' : '0',
                ]
            );

            $showUrl = route('admin.booklet.booklet.show', $booklet);

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($booklet->title).'</a>',
                    $booklet->title,
                    'text'
                ),
                $booklet->sekolah?->name ?? 'Semua sekolah',
                $booklet->pages_count,
                $this->dateCell($booklet->published_at),
                $this->badgeCell(
                    $booklet->is_published ? 'Terbit' : 'Draf',
                    $booklet->is_published ? 'badge badge-green' : 'badge badge-amber'
                ),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.booklet.booklet.update', $booklet),
                            'form_target' => 'booklet-form',
                            'modal_target' => 'booklet-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.booklet.booklet.destroy', $booklet),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus booklet ini?',
                            'confirm_detail' => [
                                ['label' => 'Judul', 'value' => $booklet->title],
                                ['label' => 'Halaman', 'value' => (string) $booklet->pages_count],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreBookletRequest $request): JsonResponse
    {
        $data = $request->safe()->except('cover');
        $coverPath = null;

        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('booklet/covers', 'public');
        }

        $booklet = Booklet::create([
            ...$data,
            'sekolah_id' => AdminSchoolScope::resolveOptionalFromRequest($request),
            'slug' => $this->uniqueSlug($data['title']),
            'cover_path' => $coverPath,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->filled('published_at')
                ? $request->date('published_at')
                : ($request->boolean('is_published') ? now() : null),
        ]);

        return $this->jsonSuccess('Booklet berhasil ditambahkan.', $booklet, 201);
    }

    public function update(UpdateBookletRequest $request, Booklet $booklet): JsonResponse
    {
        $data = $request->safe()->except('cover');
        $payload = [
            ...$data,
            'sekolah_id' => AdminSchoolScope::resolveOptionalFromRequest($request),
            'slug' => $this->uniqueSlug($data['title'], $booklet->id),
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->filled('published_at')
                ? $request->date('published_at')
                : ($request->boolean('is_published') ? ($booklet->published_at ?? now()) : null),
        ];

        if ($request->hasFile('cover')) {
            if ($booklet->cover_path && Storage::disk('public')->exists($booklet->cover_path)) {
                Storage::disk('public')->delete($booklet->cover_path);
            }
            $payload['cover_path'] = $request->file('cover')->store('booklet/covers', 'public');
        }

        $booklet->update($payload);

        return $this->jsonSuccess('Booklet berhasil diperbarui.', $booklet->fresh());
    }

    public function destroy(Booklet $booklet): JsonResponse
    {
        $this->authorize('booklet.delete');

        $booklet->load('pages');
        foreach ($booklet->pages as $page) {
            $this->deletePageFile($page);
        }

        if ($booklet->cover_path && Storage::disk('public')->exists($booklet->cover_path)) {
            Storage::disk('public')->delete($booklet->cover_path);
        }

        $title = $booklet->title;
        $booklet->delete();

        return $this->jsonSuccess('Booklet "'.$title.'" berhasil dihapus.');
    }

    public function show(Booklet $booklet): View
    {
        $this->authorize('booklet.view');

        $booklet->load(['sekolah', 'pages']);

        return view('admin.booklet.show', [
            'title' => $booklet->title,
            'booklet' => $booklet,
        ]);
    }

    public function storePage(StoreBookletPageRequest $request, Booklet $booklet): JsonResponse
    {
        $data = $request->safe()->except('file');
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('booklet/pages', 'public');
        }

        $page = $booklet->pages()->create([
            ...$data,
            'sort_order' => (int) ($data['sort_order'] ?? (($booklet->pages()->max('sort_order') ?? 0) + 1)),
            'file_path' => $filePath,
        ]);

        return $this->jsonSuccess('Halaman ditambahkan.', $page, 201);
    }

    public function destroyPage(Booklet $booklet, BookletPage $page): JsonResponse
    {
        $this->authorize('booklet.delete');

        if ((int) $page->booklet_id !== (int) $booklet->id) {
            abort(404);
        }

        $this->deletePageFile($page);
        $page->delete();

        return $this->jsonSuccess('Halaman dihapus.');
    }

    private function deletePageFile(BookletPage $page): void
    {
        if ($page->file_path && Storage::disk('public')->exists($page->file_path)) {
            Storage::disk('public')->delete($page->file_path);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'booklet';
        $slug = $base;
        $suffix = 1;

        while (
            Booklet::query()
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
