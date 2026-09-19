@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ $backUrl }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $heading }}</h1>
        @if($subheading)
            <p class="mt-1 text-lg text-slate-500" dir="rtl">{{ $subheading }}</p>
        @endif
    </div>
    <div class="flex gap-2">
        @if($nav['prev'] ?? null)
            <a href="{{ $nav['prev'] }}" class="btn-secondary text-sm">Sebelumnya</a>
        @endif
        @if($nav['next'] ?? null)
            <a href="{{ $nav['next'] }}" class="btn-secondary text-sm">Berikutnya</a>
        @endif
    </div>
</div>

<div class="tahfidz-mushaf card space-y-5 p-5 sm:p-6">
    @foreach($ayahs as $ayat)
        <article class="tahfidz-mushaf__ayah">
            <div class="tahfidz-mushaf__meta">
                @if($ayat->relationLoaded('surah') && $ayat->surah)
                    {{ $ayat->surah->number }}:{{ $ayat->ayah_number }}
                @else
                    {{ $ayat->ayah_number }}
                @endif
                <span class="text-slate-400">· Juz {{ $ayat->juz }} · hlm. {{ $ayat->page }}</span>
            </div>
            <p class="tahfidz-mushaf__ar" dir="rtl" lang="ar">{{ $ayat->text_ar }}</p>
            @if($ayat->text_id)
                <p class="tahfidz-mushaf__id">{{ $ayat->text_id }}</p>
            @endif
        </article>
    @endforeach
</div>
@endsection
