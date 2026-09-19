@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/id-card.css') }}?v=19">
@endpush

@section('content')
@php
    $extra = $guru->profil?->extra_fields ?? [];
    $portalUser = $guru->portalUser;
@endphp

<div class="page-toolbar mb-4">
    <a href="{{ route('admin.manajemen-guru.data-guru.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 dark:text-slate-400">
        <x-icon name="arrow-left" size="sm" /> Kembali ke Data Guru
    </a>
    <div class="page-toolbar__actions">
        <x-teacher-show-actions :guru="$guru" :has-account="$hasAccount" />
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card p-6 lg:col-span-1">
        <div class="mx-auto mb-4 flex h-32 w-32 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
            @if($guru->profil?->photoUrl())
                <img src="{{ $guru->profil->photoUrl() }}" alt="{{ $guru->name }}" class="h-full w-full object-cover">
            @else
                <x-icon name="user" size="lg" class="text-slate-400" />
            @endif
        </div>
        <p class="text-muted mt-2 text-center text-xs font-medium uppercase tracking-wide">Foto Profil</p>
        <h1 class="text-center text-xl font-semibold text-slate-900 dark:text-white">{{ $guru->name }}</h1>
        <p class="text-muted mt-1 text-center text-sm font-mono">NIP {{ $guru->nip }}</p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            <span class="badge badge-neutral">{{ $guru->jabatan ?? 'Guru' }}</span>
            <span class="badge badge-{{ $guru->status === 'aktif' ? 'success' : 'danger' }}">
                {{ ucfirst($guru->status) }}
            </span>
        </div>
        <p class="text-muted mt-4 text-center text-xs">
            Foto profil dapat diubah lewat <strong>Edit</strong>.
        </p>
    </div>

    <div class="card p-6 lg:col-span-2">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Informasi Guru</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Sekolah</dt>
                <dd class="mt-1 text-sm">{{ $guru->sekolah?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Jenis Guru</dt>
                <dd class="mt-1 text-sm">{{ $guru->jenis_guru ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Golongan</dt>
                <dd class="mt-1 text-sm">{{ $guru->golongan ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Telepon</dt>
                <dd class="mt-1 text-sm">{{ $guru->phone ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Kartu RFID</dt>
                <dd class="mt-1 font-mono text-sm">{{ $guru->rfidUid() ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-muted text-xs uppercase tracking-wide">Alamat</dt>
                <dd class="mt-1 text-sm">{{ $guru->profil?->address ?? '-' }}</dd>
            </div>
        </dl>
    </div>
</div>

<div class="card mt-6 p-6">
    @include('admin.partials.teacher-id-card-panel', [
        'guru' => $guru,
        'kartu' => $guru->kartuAktif,
    ])
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Akun Login Portal</h2>
        @if($portalUser)
            <dl class="grid gap-3">
                <div>
                    <dt class="text-muted text-xs uppercase tracking-wide">Username</dt>
                    <dd class="mt-1 font-mono text-sm">{{ $portalUser->username }}</dd>
                </div>
                <div>
                    <dt class="text-muted text-xs uppercase tracking-wide">Email</dt>
                    <dd class="mt-1 text-sm">{{ $portalUser->email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-muted text-xs uppercase tracking-wide">Status Akun</dt>
                    <dd class="mt-1 text-sm">{{ ucfirst($portalUser->status) }}</dd>
                </div>
            </dl>
            <p class="text-muted mt-4 text-xs">
                Kelola password lewat tombol <strong>Akun Login</strong> di atas.
            </p>
        @else
            <p class="text-muted text-sm">Belum ada akun login portal untuk guru ini.</p>
            <p class="text-muted mt-2 text-xs">
                Buat akun dari tombol <strong>Buat Akun</strong> di atas. Username dibuat otomatis dari NIP (contoh: <code class="font-mono">gr-ma-002</code>).
            </p>
        @endif
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Profil Tambahan</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Pendidikan Terakhir</dt>
                <dd class="mt-1 text-sm">{{ $extra['pendidikan'] ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Jurusan</dt>
                <dd class="mt-1 text-sm">{{ $extra['jurusan'] ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-muted text-xs uppercase tracking-wide">Catatan</dt>
                <dd class="mt-1 text-sm">{{ $extra['catatan'] ?? '-' }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection

@push('modals')
@include('admin.partials.profile-photo-assets')
<x-modal id="teacher-modal" title="Tambah / Ubah Guru">
    <form id="teacher-form"
          data-fetch-form
          data-default-action="{{ route('admin.manajemen-guru.data-guru.store') }}"
          data-reload-page
          data-close-modal="teacher-modal"
          action="{{ route('admin.manajemen-guru.data-guru.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Guru</h4>
            <div class="form-section__body space-y-4">
                @include('admin.manajemen-guru.partials.teacher-form-fields', ['schools' => $schools])
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="teacher-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="guru-account-modal" title="Akun Login Guru">
    <form id="guru-account-form"
          class="space-y-5"
          novalidate
          data-skip-dialog-validation="true"
          action="#"
          method="post"
          onsubmit="return false;">
        <section class="form-section">
            <h4 class="form-section__title">Informasi Akun</h4>
            <div class="form-section__body space-y-4">
                <p id="guru-account-intro" class="text-sm text-slate-500"></p>

                <div id="guru-account-existing" class="hidden space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/50">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Username</span>
                        <span id="guru-account-username" class="font-medium text-slate-900 dark:text-white"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Email</span>
                        <span id="guru-account-email" class="truncate font-medium text-slate-900 dark:text-white"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Status akun</span>
                        <span id="guru-account-status" class="font-medium text-slate-900 dark:text-white"></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pengaturan Password</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="guru-account-password">Password</label>
                    <input type="password" id="guru-account-password" name="password" class="form-input" autocomplete="new-password" required>
                </div>
                <div>
                    <label class="form-label" for="guru-account-password-confirmation">Konfirmasi Password</label>
                    <input type="password" id="guru-account-password-confirmation" name="password_confirmation" class="form-input" autocomplete="new-password" required>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="guru-account-modal" class="btn-secondary">Batal</button>
            <button type="button" class="btn-primary" id="guru-account-submit">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
    window.__idCardPrintAssets = {
        css: @json(asset('css/id-card.css') . '?v=19'),
        icons: @json('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css'),
        font: @json('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'),
        logo: @json(asset('logo.png')),
    };
</script>
<script src="{{ asset('js/id-card.js') }}?v=5"></script>
<script src="{{ asset('js/guru-account.js') }}?v=6"></script>
@endpush
