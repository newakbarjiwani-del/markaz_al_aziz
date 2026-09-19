@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route($indexRouteName) }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Daftar booklet</a>
</div>

<div class="card mb-6 overflow-hidden">
    @if($booklet->cover_path)
        <img src="{{ asset('storage/'.$booklet->cover_path) }}" alt="" class="max-h-64 w-full object-cover">
    @endif
    <div class="p-5">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $booklet->title }}</h1>
        @if($booklet->summary)
            <p class="mt-2 text-slate-600 dark:text-slate-300">{{ $booklet->summary }}</p>
        @endif
        @if($booklet->published_at)
            <p class="mt-2 text-sm text-slate-500">Terbit {{ $booklet->published_at->translatedFormat('d M Y') }}</p>
        @endif
    </div>
</div>

<div class="space-y-4">
    @forelse($booklet->pages as $index => $page)
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Halaman {{ $index + 1 }}</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $page->title ?: 'Tanpa judul' }}</h2>
            @if($page->body)
                <p class="mt-3 whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $page->body }}</p>
            @endif
            @if($page->file_path)
                <a href="{{ asset('storage/'.$page->file_path) }}" target="_blank" rel="noopener"
                   class="btn-secondary mt-4 inline-flex">
                    Buka lampiran
                </a>
            @endif
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Booklet ini belum memiliki halaman.</div>
    @endforelse
</div>
@endsection
