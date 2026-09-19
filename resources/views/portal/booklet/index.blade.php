@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Informasi dan majalah digital sekolah</p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($booklets as $booklet)
        <a href="{{ route($showRouteName, $booklet) }}" class="card block overflow-hidden transition hover:shadow-md">
            @if($booklet->cover_path)
                <img src="{{ asset('storage/'.$booklet->cover_path) }}" alt="" class="h-40 w-full object-cover">
            @else
                <div class="flex h-40 items-center justify-center bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <x-icon name="book" size="lg" />
                </div>
            @endif
            <div class="p-4">
                <h2 class="font-semibold text-slate-900 dark:text-white">{{ $booklet->title }}</h2>
                @if($booklet->summary)
                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $booklet->summary }}</p>
                @endif
                <p class="mt-3 text-xs text-slate-400">{{ $booklet->pages_count }} halaman</p>
            </div>
        </a>
    @empty
        <div class="card col-span-full p-8 text-center text-slate-500">Belum ada booklet terbit.</div>
    @endforelse
</div>
@endsection
