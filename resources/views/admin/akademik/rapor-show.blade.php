@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.akademik.rapor.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $rapor->siswa?->name ?? '-' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $rapor->siswa?->kelas?->name ?? '-' }}
                · {{ $rapor->tahunAkademik?->name ?? '-' }}
                · {{ \App\Support\AkademikSemester::label($rapor->semester) }}
            </p>
            <p class="mt-2">
                <span class="badge {{ $rapor->isFinal() ? 'badge-green' : 'badge-amber' }}">
                    {{ $rapor->isFinal() ? 'Final' : 'Draft' }}
                </span>
                @if($rapor->finalized_at)
                    <span class="ml-2 text-sm text-slate-500">{{ $rapor->finalized_at->translatedFormat('d M Y H:i') }}</span>
                @endif
            </p>
            @if($rapor->catatan_wali)
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $rapor->catatan_wali }}</p>
            @endif
        </div>
        @can('akademik.update')
            @unless($rapor->isFinal())
                <button type="button" class="btn-primary"
                        data-fetch-post="{{ route('admin.akademik.rapor.finalize', $rapor) }}"
                        data-confirm-title="Finalisasi Rapor"
                        data-confirm-message="Rapor yang sudah final tidak dapat dibangun ulang. Lanjutkan?"
                        data-confirm-text="Finalisasi"
                        data-reload-page>
                    Finalisasi
                </button>
            @endunless
        @endcan
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-scroll">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Mata Pelajaran</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-600">Nilai Akhir</th>
                    <th class="px-4 py-3 text-center font-medium text-slate-600">Predikat</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rapor->mapel as $line)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-3">{{ $line->mataPelajaran?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center font-mono">{{ $line->nilai_akhir ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">{{ $line->predikat ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $line->deskripsi ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada baris mapel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
