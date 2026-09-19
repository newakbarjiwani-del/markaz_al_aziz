@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.alumni.alumni.index') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">← Kembali ke daftar</a>
</div>

<div class="card mb-6 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $alumni->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                NIS: {{ $alumni->nis ?: '-' }}
                · Angkatan: {{ $alumni->angkatan ?: '-' }}
                · {{ $alumni->sekolah?->name ?? 'Semua sekolah' }}
            </p>
            @if($alumni->email || $alumni->phone)
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ $alumni->email ?: '' }}
                    @if($alumni->email && $alumni->phone) · @endif
                    {{ $alumni->phone ?: '' }}
                </p>
            @endif
        </div>
        @can('alumni.create')
            <button type="button" class="btn-primary" data-open-modal="alumni-tracer-modal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Tracer
            </button>
        @endcan
    </div>
</div>

<div class="space-y-4">
    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Riwayat Tracer Study</h2>
    @forelse($alumni->tracers as $tracer)
        <div class="card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                        Tahun {{ $tracer->tahun_tracer }}
                        · {{ $statusLabels[$tracer->status_lulusan] ?? $tracer->status_lulusan }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $tracer->institusi ?: '-' }}
                        @if($tracer->jabatan) · {{ $tracer->jabatan }} @endif
                        @if($tracer->kota) · {{ $tracer->kota }} @endif
                    </p>
                    @if($tracer->catatan)
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $tracer->catatan }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">
                        {{ $tracer->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}
                        · {{ $tracer->source === 'public' ? 'Publik' : 'Admin' }}
                    </p>
                </div>
                @can('alumni.delete')
                    <button type="button" class="btn-secondary text-sm text-red-600"
                            data-fetch-delete="{{ route('admin.alumni.alumni.tracers.destroy', [$alumni, $tracer]) }}"
                            data-confirm-title="Hapus Tracer"
                            data-confirm-message="Hapus respons tracer ini?"
                            data-reload-page>
                        Hapus
                    </button>
                @endcan
            </div>
        </div>
    @empty
        <div class="card p-8 text-center text-slate-500">Belum ada respons tracer.</div>
    @endforelse
</div>
@endsection

@push('modals')
<x-modal id="alumni-tracer-modal" title="Tambah Tracer Study" size="lg">
    <form data-fetch-form
          data-reload-page
          data-close-modal="alumni-tracer-modal"
          action="{{ route('admin.alumni.alumni.tracers.store', $alumni) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="tracer-tahun">Tahun tracer</label>
                <input name="tahun_tracer" id="tracer-tahun" class="form-input" value="{{ now()->year }}" required>
            </div>
            <div>
                <label class="form-label" for="tracer-status">Status lulusan</label>
                <select name="status_lulusan" id="tracer-status" class="form-input" required>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="tracer-institusi">Institusi / perusahaan</label>
                <input name="institusi" id="tracer-institusi" class="form-input">
            </div>
            <div>
                <label class="form-label" for="tracer-jabatan">Jabatan / program studi</label>
                <input name="jabatan" id="tracer-jabatan" class="form-input">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="tracer-bidang">Bidang</label>
                <input name="bidang" id="tracer-bidang" class="form-input">
            </div>
            <div>
                <label class="form-label" for="tracer-kota">Kota</label>
                <input name="kota" id="tracer-kota" class="form-input">
            </div>
        </div>
        <div>
            <label class="form-label" for="tracer-catatan">Catatan</label>
            <textarea name="catatan" id="tracer-catatan" class="form-input" rows="3"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="alumni-tracer-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
