@extends('layouts.spmb', ['spmbNav' => 'pengumuman'])

@section('title', $title)

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12">
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pengumuman</h1>
    <p class="mt-1 text-sm text-slate-500">Informasi resmi terkait SPMB.</p>

    <div class="mt-8 space-y-4">
        @forelse($items as $item)
            <a href="{{ route('spmb.pengumuman.show', $item) }}" class="block border-b border-slate-200 py-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 hover:text-primary-700 dark:text-white">{{ $item->title }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ optional($item->published_at)->translatedFormat('d M Y H:i') }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit(strip_tags($item->body), 160) }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500">Belum ada pengumuman.</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $items->links() }}</div>
</div>
@endsection
