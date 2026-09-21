@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">Verifikasi progress hafalan siswa</p>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('success') }}
    </div>
@endif

<section class="mb-6 card p-5">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Halaqoh saya</h2>
        <a href="{{ route('portal.guru.tahfidz.rekap.index') }}" class="btn-secondary text-sm">Isi rekap mingguan</a>
    </div>
    @forelse($halaqoh as $item)
        <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
            <p class="font-medium">{{ $item->displayName() }}</p>
            <p class="text-slate-500">{{ $item->program?->label() }} · {{ $item->anggota->count() }} anggota</p>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada halaqoh yang diampu.</p>
    @endforelse
</section>

<div class="mb-6 card p-5">
    <h2 class="mb-3 text-lg font-semibold">Catat / verifikasi</h2>
    <form method="POST" action="{{ route('portal.guru.tahfidz.progress.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        <div>
            <label class="form-label" for="guru-siswa_id">ID Siswa</label>
            <input type="number" name="siswa_id" id="guru-siswa_id" class="form-input" required placeholder="siswa_id">
        </div>
        <div>
            <label class="form-label" for="guru-surah_id">Surah</label>
            <select name="surah_id" id="guru-surah_id" class="form-input" required>
                <option value="">Pilih</option>
                @foreach($surahs as $surah)
                    <option value="{{ $surah->id }}">{{ $surah->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="guru-ayah_from">Ayat dari</label>
            <input type="number" name="ayah_from" id="guru-ayah_from" class="form-input" min="1" required>
        </div>
        <div>
            <label class="form-label" for="guru-ayah_to">Ayat sampai</label>
            <input type="number" name="ayah_to" id="guru-ayah_to" class="form-input" min="1" required>
        </div>
        <div>
            <label class="form-label" for="guru-status">Status</label>
            <select name="status" id="guru-status" class="form-input" required>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="guru-note">Catatan</label>
            <input type="text" name="note" id="guru-note" class="form-input">
        </div>
        <label class="inline-flex items-center gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="verified" value="1" class="form-checkbox" checked>
            Tandai terverifikasi
        </label>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>

<section class="card p-5">
    <h2 class="mb-3 text-lg font-semibold">Progress terbaru</h2>
    @forelse($progress as $row)
        <div class="border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-800">
            <p class="font-medium">{{ $row->siswa?->name }} — {{ $row->rangeLabel() }}</p>
            <p class="text-slate-500">{{ $row->statusLabel() }}
                @if($row->last_reviewed_at) · {{ $row->last_reviewed_at->format('d/m/Y H:i') }} @endif
            </p>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada data progress.</p>
    @endforelse
</section>
@endsection
