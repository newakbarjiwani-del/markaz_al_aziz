@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route('portal.siswa.ujian.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Daftar ujian</a>
</div>

<div class="card mb-6 p-5">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $ujian->title }}</h1>
    <p class="mt-1 text-sm text-slate-500">
        Dikumpulkan {{ $attempt->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}
    </p>
    <p class="mt-3 text-lg font-semibold text-emerald-700 dark:text-emerald-300">
        Skor pilihan ganda: {{ $attempt->skor_mcq }} / {{ $attempt->skor_max_mcq }}
    </p>
    <p class="mt-1 text-sm text-slate-500">Jawaban essay disimpan tanpa skor otomatis.</p>
</div>

<div class="space-y-4">
    @foreach($ujian->soal as $index => $soal)
        @php $jawab = $attempt->jawaban->firstWhere('ujian_soal_id', $soal->id); @endphp
        <div class="card p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Soal {{ $index + 1 }}</p>
            <p class="mt-2 whitespace-pre-wrap">{{ $soal->pertanyaan }}</p>
            <p class="mt-3 text-sm">
                Jawaban:
                <span class="font-medium">{{ $jawab?->jawaban ?: '-' }}</span>
                @if($soal->isPilihanGanda())
                    @if($jawab?->is_benar)
                        <span class="badge badge-green ml-2">Benar (+{{ $jawab->poin_didapat }})</span>
                    @else
                        <span class="badge badge-red ml-2">Salah</span>
                    @endif
                @else
                    <span class="badge badge-slate ml-2">Essay</span>
                @endif
            </p>
        </div>
    @endforeach
</div>
@endsection
