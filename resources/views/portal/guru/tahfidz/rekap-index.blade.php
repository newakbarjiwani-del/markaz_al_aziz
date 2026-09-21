@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Isi tatsbit, murojaah, dan absensi halaqoh Anda</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-6 card p-5">
    <h2 class="mb-3 text-lg font-semibold">Buat / buka rekap minggu</h2>
    <form method="POST" action="{{ route('portal.guru.tahfidz.rekap.store') }}" class="grid gap-4 sm:grid-cols-4">
        @csrf
        <div>
            <label class="form-label" for="guru-rekap-program_id">Program</label>
            <select name="program_id" id="guru-rekap-program_id" class="form-input" required>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="guru-rekap-starts_on">Mulai</label>
            <input type="date" name="starts_on" id="guru-rekap-starts_on" class="form-input" required>
        </div>
        <div>
            <label class="form-label" for="guru-rekap-ends_on">Selesai</label>
            <input type="date" name="ends_on" id="guru-rekap-ends_on" class="form-input" required>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">Buka</button>
        </div>
    </form>
</div>

<section class="card p-5">
    <h2 class="mb-3 text-lg font-semibold">Rekap sebelumnya</h2>
    @forelse($rekaps as $item)
        <a href="{{ route('portal.guru.tahfidz.rekap.show', $item) }}" class="block border-b border-slate-100 py-3 text-sm last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">
            <p class="font-medium">{{ $item->program?->label() }}</p>
            <p class="text-slate-500">{{ $item->periodLabel() }} · {{ $item->statusLabel() }}</p>
        </a>
    @empty
        <p class="text-sm text-slate-500">Belum ada rekap.</p>
    @endforelse
</section>
@endsection
