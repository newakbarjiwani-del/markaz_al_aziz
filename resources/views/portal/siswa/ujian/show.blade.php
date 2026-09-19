@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route('portal.siswa.ujian.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Daftar ujian</a>
</div>

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $ujian->title }}</h1>
    <p class="mt-1 text-sm text-slate-500">
        {{ $ujian->tahunAkademik?->name ?? '-' }}
        · {{ $semesters[$ujian->semester] ?? $ujian->semester }}
        @if($ujian->mataPelajaran) · {{ $ujian->mataPelajaran->name }} @endif
    </p>
</div>

@if(! $attempt)
    <div class="card p-6 text-center">
        <p class="mb-4 text-slate-600 dark:text-slate-300">Mulai ujian untuk mengisi jawaban.</p>
        <form method="POST" action="{{ route('portal.siswa.ujian.start', $ujian) }}">
            @csrf
            <button type="submit" class="btn-primary">Mulai Ujian</button>
        </form>
    </div>
@else
    <form method="POST" action="{{ route('portal.siswa.ujian.submit', $ujian) }}" class="space-y-4">
        @csrf
        @foreach($ujian->soal as $index => $soal)
            <div class="card p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Soal {{ $index + 1 }} · {{ $soal->poin }} poin
                </p>
                <p class="mt-2 whitespace-pre-wrap text-slate-900 dark:text-white">{{ $soal->pertanyaan }}</p>

                @if($soal->isPilihanGanda())
                    <div class="mt-4 space-y-2">
                        @foreach($soal->opsi ?? [] as $opsi)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                <input type="radio"
                                       name="answers[{{ $soal->id }}]"
                                       value="{{ $opsi['key'] ?? '' }}"
                                       class="mt-1"
                                       @checked(old('answers.'.$soal->id) === ($opsi['key'] ?? ''))>
                                <span>
                                    <span class="font-mono font-semibold">{{ $opsi['key'] ?? '?' }}.</span>
                                    {{ $opsi['label'] ?? '' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea name="answers[{{ $soal->id }}]"
                              class="form-input mt-4"
                              rows="4"
                              placeholder="Tulis jawaban essay...">{{ old('answers.'.$soal->id) }}</textarea>
                @endif
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="btn-primary"
                    onclick="return confirm('Kumpulkan jawaban sekarang?')">
                Kumpulkan Jawaban
            </button>
        </div>
    </form>
@endif
@endsection
