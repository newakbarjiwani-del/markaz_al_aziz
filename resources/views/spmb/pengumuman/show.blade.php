@extends('layouts.spmb', ['spmbNav' => 'pengumuman'])

@section('title', $title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-12">
    <a href="{{ route('spmb.pengumuman.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Semua pengumuman</a>
    <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-white">{{ $pengumuman->title }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ optional($pengumuman->published_at)->translatedFormat('d M Y H:i') }}</p>
    <div class="prose prose-slate mt-8 max-w-none dark:prose-invert whitespace-pre-wrap">{{ $pengumuman->body }}</div>
</article>
@endsection
