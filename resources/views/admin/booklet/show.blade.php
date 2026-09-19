@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.booklet.booklet.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex flex-wrap gap-4">
            @if($booklet->cover_path)
                <img src="{{ asset('storage/'.$booklet->cover_path) }}" alt="" class="h-28 w-20 rounded object-cover">
            @endif
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $booklet->title }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $booklet->sekolah?->name ?? 'Semua sekolah' }}
                    · {{ $booklet->is_published ? 'Terbit' : 'Draf' }}
                </p>
                @if($booklet->summary)
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $booklet->summary }}</p>
                @endif
            </div>
        </div>
        @can('booklet.create')
            <button type="button" class="btn-primary" data-open-modal="booklet-page-modal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Halaman
            </button>
        @endcan
    </div>
</div>

<div class="space-y-4">
    @forelse($booklet->pages as $index => $page)
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Halaman {{ $index + 1 }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $page->title ?: 'Tanpa judul' }}</h2>
                    @if($page->body)
                        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $page->body }}</p>
                    @endif
                    @if($page->file_path)
                        <a href="{{ asset('storage/'.$page->file_path) }}" target="_blank" rel="noopener"
                           class="mt-3 inline-flex text-sm text-primary-700 hover:underline dark:text-primary-300">
                            Buka lampiran
                        </a>
                    @endif
                </div>
                @can('booklet.delete')
                    <button type="button" class="btn-secondary text-sm text-red-600"
                            data-fetch-delete="{{ route('admin.booklet.booklet.pages.destroy', [$booklet, $page]) }}"
                            data-confirm-title="Hapus Halaman"
                            data-confirm-message="Hapus halaman ini?"
                            data-reload-page>
                        Hapus
                    </button>
                @endcan
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Belum ada halaman.</div>
    @endforelse
</div>
@endsection

@push('modals')
<x-modal id="booklet-page-modal" title="Tambah Halaman" size="lg">
    <form data-fetch-form
          data-reload-page
          data-close-modal="booklet-page-modal"
          action="{{ route('admin.booklet.booklet.pages.store', $booklet) }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="booklet-page-title">Judul halaman</label>
            <input name="title" id="booklet-page-title" class="form-input" maxlength="255">
        </div>
        <div>
            <label class="form-label" for="booklet-page-body">Isi</label>
            <textarea name="body" id="booklet-page-body" class="form-input" rows="5"></textarea>
        </div>
        <div>
            <label class="form-label" for="booklet-page-file">Lampiran (PDF/gambar)</label>
            <input type="file" name="file" id="booklet-page-file" class="form-input" accept=".pdf,image/*">
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="booklet-page-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
