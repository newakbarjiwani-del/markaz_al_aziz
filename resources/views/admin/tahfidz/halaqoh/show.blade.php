@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $halaqoh->displayName() }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $halaqoh->program?->label() }} · {{ $halaqoh->guru?->name }}</p>
    </div>
    <a href="{{ route('admin.tahfidz.halaqoh.index') }}" class="btn-secondary">Kembali</a>
</div>

@if(session('success'))
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div class="mb-6 card p-5">
    <h2 class="mb-3 text-lg font-semibold">Tambah anggota</h2>
    <form id="tahfidz-anggota-form"
          action="{{ route('admin.tahfidz.halaqoh.anggota.store', $halaqoh) }}"
          method="POST"
          class="grid gap-4 sm:grid-cols-3">
        @csrf
        <div class="sm:col-span-2">
            <x-siswa-select name="siswa_id" id="anggota-siswa_id" />
        </div>
        <div>
            <label class="form-label" for="anggota-total_juz">Total juz</label>
            <input type="number" name="total_juz" id="anggota-total_juz" class="form-input" min="0" max="30" value="0">
        </div>
        <div class="sm:col-span-3">
            <button type="submit" class="btn-primary">Simpan anggota</button>
        </div>
    </form>
</div>

<section class="card p-5">
    <h2 class="mb-3 text-lg font-semibold">Anggota</h2>
    @forelse($halaqoh->anggota as $anggota)
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-3 last:border-0 dark:border-slate-800">
            <div>
                <p class="font-medium">{{ $anggota->siswa?->name }}</p>
                <p class="text-xs text-slate-500">{{ $anggota->siswa?->nis }} · {{ $anggota->total_juz }} juz</p>
            </div>
            @can('tahfidz.delete')
                <form method="POST" action="{{ route('admin.tahfidz.halaqoh.anggota.destroy', [$halaqoh, $anggota]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-secondary text-sm">Hapus</button>
                </form>
            @endcan
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada anggota.</p>
    @endforelse
</section>
@endsection
