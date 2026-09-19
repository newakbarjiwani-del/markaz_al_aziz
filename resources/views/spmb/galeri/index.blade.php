@extends('layouts.spmb', ['spmbNav' => 'galeri'])

@section('title', $title)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Galeri Sekolah</h1>
    <p class="mt-1 text-sm text-slate-500">Fasilitas dan suasana Yayasan Ittihad Pekanbaru.</p>

    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($items as $item)
            <figure>
                <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->title }}" class="aspect-square w-full object-cover">
                <figcaption class="mt-2 text-sm font-medium text-slate-800 dark:text-slate-200">{{ $item->title }}</figcaption>
                @if($item->caption)
                    <p class="text-xs text-slate-500">{{ $item->caption }}</p>
                @endif
            </figure>
        @empty
            <p class="col-span-full text-sm text-slate-500">Belum ada foto.</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $items->links() }}</div>
</div>
@endsection
