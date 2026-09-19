@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Ujian tersedia untuk {{ $siswa->name }}</p>
</div>

<div class="space-y-4">
    @forelse($exams as $ujian)
        @php
            $ujianAttempts = $attempts->get($ujian->id, collect());
            $submitted = $ujianAttempts->firstWhere('status', 'submitted');
            $inProgress = $ujianAttempts->firstWhere('status', 'in_progress');
        @endphp
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $ujian->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $ujian->tahunAkademik?->name ?? '-' }}
                        · {{ $semesters[$ujian->semester] ?? $ujian->semester }}
                        @if($ujian->mataPelajaran) · {{ $ujian->mataPelajaran->name }} @endif
                    </p>
                    @if($submitted)
                        <p class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">
                            Skor MCQ: {{ $submitted->skor_mcq }} / {{ $submitted->skor_max_mcq }}
                        </p>
                    @endif
                </div>
                <div>
                    @if($submitted)
                        <a href="{{ route('portal.siswa.ujian.show', $ujian) }}" class="btn-secondary">Lihat hasil</a>
                    @elseif($inProgress)
                        <a href="{{ route('portal.siswa.ujian.show', $ujian) }}" class="btn-primary">Lanjutkan</a>
                    @else
                        <form method="POST" action="{{ route('portal.siswa.ujian.start', $ujian) }}">
                            @csrf
                            <button type="submit" class="btn-primary">Mulai</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Tidak ada ujian aktif saat ini.</div>
    @endforelse
</div>
@endsection
