@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="page-toolbar mb-4">
    <a href="{{ route('admin.manajemen-siswa.orang-tua.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400">
        <x-icon name="arrow-left" size="sm" /> Kembali ke Data Orang Tua
    </a>
    <div class="page-toolbar__actions">
        @include('components.orang-tua-portal-actions', [
            'orangTua' => $orangTua,
            'showViewLink' => false,
            'portalTokenActive' => $portalTokenActive,
        ])
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card p-6 lg:col-span-1">
        <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
            <x-icon name="users" size="lg" />
        </div>
        <h1 class="text-center text-xl font-semibold text-slate-900 dark:text-white">{{ $orangTua->displayName() }}</h1>
        <div class="mt-4 flex justify-center">
            <span class="badge badge-{{ $orangTua->status === 'aktif' ? 'success' : 'neutral' }}">
                {{ ucfirst($orangTua->status) }}
            </span>
        </div>
        <p class="text-muted mt-4 text-center text-sm">{{ $orangTua->sekolah?->name ?? 'Semua sekolah' }}</p>
    </div>

    <div class="card p-6 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Data Ayah</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Nama Ayah</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->nama_ayah ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Telepon Ayah</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->telepon_ayah ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Email Ayah</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->email_ayah ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Pekerjaan Ayah</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->pekerjaan_ayah ?? '-' }}</dd>
            </div>
        </dl>

        <h2 class="mb-4 mt-8 text-lg font-semibold text-slate-900 dark:text-white">Data Ibu</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Nama Ibu</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->nama_ibu ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Telepon Ibu</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->telepon_ibu ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Email Ibu</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->email_ibu ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Pekerjaan Ibu</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->pekerjaan_ibu ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-muted text-xs uppercase tracking-wide">Alamat</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->alamat ?? '-' }}</dd>
            </div>
        </dl>

        <h2 class="mb-4 mt-8 text-lg font-semibold text-slate-900 dark:text-white">Data Wali</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Nama Wali</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->nama_wali ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Telepon Wali</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->telepon_wali ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Email Wali</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->email_wali ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Pekerjaan Wali</dt>
                <dd class="mt-1 text-sm">{{ $orangTua->pekerjaan_wali ?? '-' }}</dd>
            </div>
        </dl>
    </div>
</div>

<div class="card mt-6 p-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Anak Terdaftar</h2>
        <button type="button" class="btn-primary btn-sm"
                data-open-modal="assign-siswa-modal"
                data-form-reset="assign-siswa-form"
                data-store-url="{{ route('admin.manajemen-siswa.orang-tua.assign-siswa', $orangTua) }}">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Siswa
        </button>
    </div>
    @forelse($orangTua->siswa as $siswa)
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 py-4 last:border-0 dark:border-slate-800">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">{{ $siswa->name }}</p>
                <p class="text-muted mt-1 text-sm font-mono">NIS {{ $siswa->nis }} · {{ $siswa->kelas?->name ?? 'Tanpa kelas' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.manajemen-siswa.data-siswa.show', $siswa) }}" class="btn-secondary btn-sm">
                    <x-icon name="eye" size="sm" class="mr-1" /> Detail
                </a>
                @php
                    $unlinkConfirmDetail = [
                        ['label' => 'Siswa', 'value' => $siswa->name],
                        ['label' => 'NIS', 'value' => $siswa->nis],
                    ];
                @endphp
                <button type="button"
                        class="btn-danger btn-sm"
                        data-fetch-delete="{{ route('admin.manajemen-siswa.orang-tua.remove-siswa', [$orangTua, $siswa]) }}"
                        data-confirm-title="Hapus Keterkaitan Siswa"
                        data-confirm-message="Siswa ini akan dilepas dari daftar anak orang tua."
                        data-confirm-detail="{{ \App\Support\ConfirmDetail::attr($unlinkConfirmDetail) }}"
                        data-confirm-text="Ya, Hapus"
                        data-confirm-icon="ti-unlink"
                        data-confirm-header-icon="ti-unlink"
                        data-reload-page
                        title="Hapus">
                    <x-icon name="trash" size="sm" />
                </button>
            </div>
        </div>
    @empty
        <p class="text-muted text-sm">Belum ada siswa terhubung.</p>
    @endforelse
</div>

<x-modal id="assign-siswa-modal" title="Atur Keterkaitan Siswa">
    <form id="assign-siswa-form"
          data-fetch-form
          data-reload-page
          data-close-modal="assign-siswa-modal"
          data-default-action="{{ route('admin.manajemen-siswa.orang-tua.assign-siswa', $orangTua) }}"
          action="{{ route('admin.manajemen-siswa.orang-tua.assign-siswa', $orangTua) }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Pilih Siswa</h4>
            <div class="form-section__body">
                <x-siswa-select />
                <div>
                    <label class="form-label" for="assign-siswa-hint">Catatan</label>
                    <p id="assign-siswa-hint" class="text-muted text-xs">
                        Setiap siswa hanya boleh punya satu data orang tua. Jika siswa sudah terhubung ke orang tua lain, sistem akan menolak penautan.
                    </p>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="assign-siswa-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Keterkaitan
            </button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/portal-access.js') }}?v=7"></script>
<script src="{{ asset('js/ajax-select.js') }}?v=7"></script>
@endpush
