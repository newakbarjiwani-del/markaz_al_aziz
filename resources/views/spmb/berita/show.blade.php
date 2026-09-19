@extends('layouts.spmb', ['spmbNav' => 'berita'])

@section('title', $title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-12">
    <a href="{{ route('spmb.berita.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Semua berita</a>
    <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-white">{{ $berita->title }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ optional($berita->published_at)->translatedFormat('d M Y H:i') }}</p>
    @if($berita->cover_path)
        <img src="{{ asset('storage/'.$berita->cover_path) }}" alt="" class="mt-6 w-full object-cover">
    @endif
    <div class="prose prose-slate mt-8 max-w-none dark:prose-invert whitespace-pre-wrap">{{ $berita->body }}</div>
</article>
@endsection
