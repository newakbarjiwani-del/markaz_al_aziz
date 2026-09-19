@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar Hari Libur"
    subtitle="Tanggal libur tidak dihitung sebagai hari aktif sekolah, sehingga tidak tercampur dengan alpha."
    :ajax-url="route('admin.absensi.hari-libur.data')"
    :columns="['Tanggal', 'Sekolah', 'Nama Libur', 'Berlaku', 'Catatan', 'Aksi']">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="hari-libur-modal"
                data-form-reset="hari-libur-form"
                data-store-url="{{ route('admin.absensi.hari-libur.store') }}"
                data-modal-title="Tambah Hari Libur">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Hari Libur
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
        <div>
            <label class="form-label">Berlaku Untuk</label>
            <select name="applies_to" id="filter-applies_to" class="form-input">
                <option value="">Semua</option>
                <option value="both">Siswa & Guru</option>
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
            </select>
        </div>
        @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date_from', 'toId' => 'filter-date_to'])
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="hari-libur-modal" title="Tambah / Ubah Hari Libur">
    <form id="hari-libur-form"
          data-fetch-form
          data-default-action="{{ route('admin.absensi.hari-libur.store') }}"
          data-reload-table
          data-close-modal="hari-libur-modal"
          action="{{ route('admin.absensi.hari-libur.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Hari Libur</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label for="hari-libur-sekolah_id" class="form-label">Sekolah</label>
                    <select name="sekolah_id" id="hari-libur-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua sekolah</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-muted mt-1 text-xs">Kosongkan untuk libur nasional / bersama semua sekolah.</p>
                </div>

                <div>
                    <label for="hari-libur-applies_to" class="form-label">Berlaku Untuk</label>
                    <select name="applies_to" id="hari-libur-applies_to" class="form-input" required>
                        <option value="both">Siswa & Guru</option>
                        <option value="siswa">Siswa saja</option>
                        <option value="guru">Guru saja</option>
                    </select>
                </div>
            </div>
        </section>

        <section id="hari-libur-bulk-panel" class="form-section">
            <h4 class="form-section__title">Pengaturan Tanggal Libur</h4>
            <div class="form-section__body space-y-3">
                <div>
                    <label for="hari-libur-bulk-notes" class="form-label">Catatan Umum</label>
                    <textarea id="hari-libur-bulk-notes" name="notes" class="form-input" rows="2" maxlength="500" placeholder="Opsional — berlaku untuk semua tanggal di bawah"></textarea>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <label class="form-label mb-0">Daftar Tanggal Libur</label>
                        <button type="button" id="hari-libur-add-row" class="btn-secondary text-xs">
                            <x-icon name="plus" size="sm" class="mr-1" /> Tambah baris
                        </button>
                    </div>
                    <div class="mb-1 hidden grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] gap-2 px-1 text-xs font-medium text-slate-500 dark:text-slate-400 sm:grid">
                        <span>Tanggal</span>
                        <span>Nama Libur</span>
                        <span class="sr-only">Hapus</span>
                    </div>
                    <div id="hari-libur-entries" class="space-y-2"></div>
                </div>

                <details class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700 dark:text-slate-200">
                        Isi cepat rentang tanggal
                    </summary>
                    <div class="mt-3 space-y-3">
                        <p class="text-muted text-xs">Buat beberapa baris sekaligus untuk rentang tanggal dengan nama libur yang sama.</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="hari-libur-range-from" class="form-label">Dari Tanggal</label>
                                <input type="date" id="hari-libur-range-from" class="form-input">
                            </div>
                            <div>
                                <label for="hari-libur-range-to" class="form-label">Sampai Tanggal</label>
                                <input type="date" id="hari-libur-range-to" class="form-input">
                            </div>
                        </div>
                        <div>
                            <label for="hari-libur-range-name" class="form-label">Nama Libur</label>
                            <input type="text" id="hari-libur-range-name" class="form-input" placeholder="Mis. Libur Semester">
                        </div>
                        <button type="button" id="hari-libur-fill-range" class="btn-secondary w-full text-sm">
                            Buat baris dari rentang
                        </button>
                    </div>
                </details>
            </div>
        </section>

        <section id="hari-libur-edit-panel" class="hidden form-section">
            <h4 class="form-section__title">Pengaturan Hari Libur</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label for="hari-libur-edit-date" class="form-label">Tanggal</label>
                    <input type="date" name="date" id="hari-libur-edit-date" class="form-input" disabled>
                </div>
                <div>
                    <label for="hari-libur-edit-name" class="form-label">Nama Libur</label>
                    <input name="name" id="hari-libur-edit-name" class="form-input" placeholder="Mis. Idul Fitri" disabled>
                </div>
                <div>
                    <label for="hari-libur-edit-notes" class="form-label">Catatan</label>
                    <textarea id="hari-libur-edit-notes" class="form-input" rows="2" maxlength="500" disabled></textarea>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="hari-libur-modal" class="btn-secondary">Batal</button>
            <button type="submit" id="hari-libur-submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>

<template id="hari-libur-entry-template">
    <div class="hari-libur-entry-row grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_auto] sm:items-start" data-entry-row>
        <div>
            <label class="form-label sm:sr-only">Tanggal</label>
            <input type="date" data-entry-date class="form-input" required>
        </div>
        <div>
            <label class="form-label sm:sr-only">Nama Libur</label>
            <input type="text" data-entry-name class="form-input" placeholder="Mis. Idul Fitri" required>
        </div>
        <button type="button" class="btn-secondary flex h-10 w-full items-center justify-center sm:w-10 sm:shrink-0" data-remove-entry title="Hapus baris" aria-label="Hapus baris">
            <x-icon name="trash" size="sm" />
        </button>
    </div>
</template>
@endpush

@push('styles')
<style>
    #hari-libur-modal .modal-panel {
        max-width: 42rem;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/hari-libur-form.js') }}?v=3"></script>
@endpush
