@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Progress hafalan anak (hanya baca)</p>
</div>

@if($children->isEmpty())
    <div class="card p-8 text-center text-slate-500">Belum ada data anak terhubung.</div>
@else
    <div class="grid gap-4 lg:grid-cols-2">
        <section class="card p-5">
            <h2 class="mb-3 text-lg font-semibold">Target</h2>
            @forelse($targets as $target)
                <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
                    <p class="font-medium">{{ $target->siswa?->name }} — {{ $target->rangeLabel() }}</p>
                    <p class="text-slate-500">{{ $target->period === 'daily' ? 'Harian' : 'Mingguan' }}
                        @if($target->due_date) · {{ $target->due_date->format('d/m/Y') }} @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada target.</p>
            @endforelse
        </section>
        <section class="card p-5">
            <h2 class="mb-3 text-lg font-semibold">Progress</h2>
            @forelse($progress as $row)
                <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
                    <p class="font-medium">{{ $row->siswa?->name }} — {{ $row->rangeLabel() }}</p>
                    <p class="text-slate-500">{{ $row->statusLabel() }}
                        @if($row->last_reviewed_at) · {{ $row->last_reviewed_at->format('d/m/Y H:i') }} @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada progress.</p>
            @endforelse
        </section>
    </div>

    <section class="mt-4 card p-5">
        <h2 class="mb-3 text-lg font-semibold">Rekap halaqoh</h2>
        @forelse($rekaps as $row)
            <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
                <p class="font-medium">{{ $row->siswa?->name }} · {{ $row->rekap?->periodLabel() }}</p>
                <p class="text-slate-500">{{ $row->halaqoh?->displayName() }} · Tatsbit {{ $row->tatsbitLabel() }} · Hadir {{ $row->hadir_hari }} hari · Total {{ $row->total_juz }} juz</p>
            </div>
        @empty
            <p class="text-sm text-slate-500">Belum ada rekap mingguan.</p>
        @endforelse
    </section>
@endif
@endsection
