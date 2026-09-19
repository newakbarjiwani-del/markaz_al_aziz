@extends('layouts.spmb', ['spmbNav' => 'berita'])

@section('title', $title)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-12">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Berita SPMB</h1>
    <p class="mt-1 text-sm text-slate-500">Kabarkabar seputar penerimaan dan kegiatan sekolah.</p>

    <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($items as $item)
            <a href="{{ route('spmb.berita.show', $item) }}" class="group block">
                @if($item->cover_path)
                    <img src="{{ asset('storage/'.$item->cover_path) }}" alt="" class="mb-3 aspect-[16/10] w-full object-cover">
                @else
                    <div class="mb-3 aspect-[16/10] w-full bg-primary-100 dark:bg-primary-950"></div>
                @endif
                <h2 class="font-semibold text-slate-900 group-hover:text-primary-700 dark:text-white">{{ $item->title }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ optional($item->published_at)->translatedFormat('d M Y') }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada berita.</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $items->links() }}</div>
</div>
@endsection
