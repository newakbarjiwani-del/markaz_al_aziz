@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Data Guru"
    :ajax-url="route('admin.manajemen-guru.data-guru.data')"
    :columns="['NIP', 'Nama', 'Sekolah', 'Jabatan', 'Jenis', 'RFID', 'Status', 'Akun Login', 'Aksi']">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="teacher-modal"
                data-form-reset="teacher-form"
                data-store-url="{{ route('admin.manajemen-guru.data-guru.store') }}"
                data-modal-title="Tambah Guru">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Guru
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('scripts')
<script src="{{ asset('js/guru-account.js') }}?v=6"></script>
@endpush

@push('modals')
@include('admin.partials.profile-photo-assets')
<x-modal id="teacher-modal" title="Tambah / Ubah Guru">
    <form id="teacher-form"
          data-fetch-form
          data-default-action="{{ route('admin.manajemen-guru.data-guru.store') }}"
          data-reload-table
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
