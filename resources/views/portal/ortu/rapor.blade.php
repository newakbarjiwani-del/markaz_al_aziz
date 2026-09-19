@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Rapor final anak yang terhubung</p>
</div>

@include('portal.partials.child-selector', ['children' => $children])

<div class="mt-6 space-y-4">
    @forelse($raporList as $rapor)
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ $rapor->siswa?->name ?? '-' }}
                        · {{ $rapor->tahunAkademik?->name ?? '-' }}
                        · {{ $semesters[$rapor->semester] ?? $rapor->semester }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $rapor->siswa?->kelas?->name ?? '-' }}
                        · Difinalisasi {{ $rapor->finalized_at?->translatedFormat('d M Y') ?? '-' }}
                    </p>
                </div>
                <span class="badge badge-green">Final</span>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2 text-left font-medium text-slate-600">Mapel</th>
                            <th class="py-2 text-center font-medium text-slate-600">Nilai</th>
                            <th class="py-2 text-center font-medium text-slate-600">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rapor->mapel as $line)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-2">{{ $line->mataPelajaran?->name ?? '-' }}</td>
                                <td class="py-2 text-center font-mono">{{ $line->nilai_akhir ?? '-' }}</td>
                                <td class="py-2 text-center">{{ $line->predikat ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Belum ada rapor final.</div>
    @endforelse
</div>
@endsection
