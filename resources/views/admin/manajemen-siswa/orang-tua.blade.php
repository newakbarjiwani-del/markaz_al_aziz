@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],
        ['orderable' => true,  'searchable' => true],  // Jumlah Anak (sort by siswa_count)
        ['orderable' => false, 'searchable' => true],  // Nama Anak (search still works)
        ['orderable' => true,  'searchable' => true],
        ['orderable' => false, 'searchable' => false], // Token Login
        ['orderable' => false, 'searchable' => false, 'html' => true], // Aksi
    ];
@endphp
<x-admin.datatable-page
    title="Daftar Orang Tua"
    subtitle="Kelola data orang tua / wali murid"
    :ajax-url="route('admin.manajemen-siswa.orang-tua.data')"
    :columns="['Nama Ayah', 'Telepon Ayah', 'Nama Ibu', 'Telepon Ibu', 'Nama Wali', 'Telepon Wali', 'Jumlah Anak', 'Nama Anak', 'Status', 'Token Login', 'Aksi']"
    :column-options="$mainTableColumnOptions">
    @can('students.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="orang-tua-modal"
                    data-form-reset="orang-tua-form"
                    data-store-url="{{ route('admin.manajemen-siswa.orang-tua.store') }}"
                    data-modal-title="Tambah Orang Tua">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Orang Tua
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-nama-ayah">Nama Ayah</label>
                <input type="text" name="nama_ayah" id="filter-nama-ayah" class="form-input"
                       placeholder="Nama ayah" value="{{ request('nama_ayah') }}">
            </div>
            <div>
                <label class="form-label" for="filter-nama-ibu">Nama Ibu</label>
                <input type="text" name="nama_ibu" id="filter-nama-ibu" class="form-input"
                       placeholder="Nama ibu" value="{{ request('nama_ibu') }}">
            </div>
            <div>
                <label class="form-label" for="filter-nama-wali">Nama Wali</label>
                <input type="text" name="nama_wali" id="filter-nama-wali" class="form-input"
                       placeholder="Nama wali" value="{{ request('nama_wali') }}">
            </div>
            <div>
                <label class="form-label" for="filter-nama-siswa">Nama Siswa</label>
                <input type="text" name="nama_siswa" id="filter-nama-siswa" class="form-input"
                       placeholder="Nama siswa" value="{{ request('nama_siswa') }}">
            </div>
            <div>
                <label class="form-label" for="filter-nis-siswa">NIS Siswa</label>
                <input type="text" name="nis_siswa" id="filter-nis-siswa" class="form-input"
                       placeholder="NIS siswa" value="{{ request('nis_siswa') }}">
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="orang-tua-modal" title="Tambah / Ubah Orang Tua">
    <form id="orang-tua-form"
          data-fetch-form
          data-default-action="{{ route('admin.manajemen-siswa.orang-tua.store') }}"
          data-reload-table
          data-close-modal="orang-tua-modal"
          action="{{ route('admin.manajemen-siswa.orang-tua.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Umum</h4>
            <div class="form-section__body space-y-4">
                <x-sekolah-select
                    :schools="$schools"
                    id="ortu-sekolah"
                    :required="false"
                    placeholder="Semua sekolah (opsional)"
                    label="Sekolah (opsional)"
                />
                <p class="text-muted -mt-2 text-xs">
                    Kosongkan jika orang tua memiliki anak di lebih dari satu sekolah.
                </p>
                <div>
                    <label class="form-label" for="ortu-alamat">Alamat</label>
                    <textarea name="alamat" id="ortu-alamat" class="form-input" rows="2" placeholder="Alamat lengkap"></textarea>
                </div>
                <div>
                    <label class="form-label" for="ortu-status">Status</label>
                    <select name="status" id="ortu-status" class="form-input">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
        </section>

        <fieldset class="form-section">
            <legend class="px-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Data Ayah</legend>
            <div class="form-section__body grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="ortu-nama-ayah">Nama Ayah</label>
                    <input name="nama_ayah" id="ortu-nama-ayah" class="form-input" placeholder="Nama lengkap ayah">
                </div>
                <div>
                    <label class="form-label" for="ortu-telepon-ayah">Telepon Ayah</label>
                    <input name="telepon_ayah" id="ortu-telepon-ayah" class="form-input" placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="form-label" for="ortu-email-ayah">Email Ayah</label>
                    <input name="email_ayah" id="ortu-email-ayah" class="form-input" type="email" placeholder="ayah@example.com">
                </div>
                <div>
                    <label class="form-label" for="ortu-pekerjaan-ayah">Pekerjaan Ayah</label>
                    <input name="pekerjaan_ayah" id="ortu-pekerjaan-ayah" class="form-input" placeholder="Pekerjaan ayah">
                </div>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="px-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Data Ibu</legend>
            <div class="form-section__body grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="ortu-nama-ibu">Nama Ibu</label>
                    <input name="nama_ibu" id="ortu-nama-ibu" class="form-input" placeholder="Nama lengkap ibu">
                </div>
                <div>
                    <label class="form-label" for="ortu-telepon-ibu">Telepon Ibu</label>
                    <input name="telepon_ibu" id="ortu-telepon-ibu" class="form-input" placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="form-label" for="ortu-email-ibu">Email Ibu</label>
                    <input name="email_ibu" id="ortu-email-ibu" class="form-input" type="email" placeholder="ibu@example.com">
                </div>
                <div>
                    <label class="form-label" for="ortu-pekerjaan-ibu">Pekerjaan Ibu</label>
                    <input name="pekerjaan_ibu" id="ortu-pekerjaan-ibu" class="form-input" placeholder="Pekerjaan ibu">
                </div>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend class="px-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Data Wali</legend>
            <p class="text-muted mb-3 px-2 text-xs">Opsional — untuk siswa yang diasuh wali (bukan/orang tua kandung). Minimal satu nama ayah, ibu, atau wali wajib diisi.</p>
            <div class="form-section__body grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="ortu-nama-wali">Nama Wali</label>
                    <input name="nama_wali" id="ortu-nama-wali" class="form-input" placeholder="Nama lengkap wali">
                </div>
                <div>
                    <label class="form-label" for="ortu-telepon-wali">Telepon Wali</label>
                    <input name="telepon_wali" id="ortu-telepon-wali" class="form-input" placeholder="08xxxxxxxxxx">
                </div>
                <div>
                    <label class="form-label" for="ortu-email-wali">Email Wali</label>
                    <input name="email_wali" id="ortu-email-wali" class="form-input" type="email" placeholder="wali@example.com">
                </div>
                <div>
                    <label class="form-label" for="ortu-pekerjaan-wali">Pekerjaan Wali</label>
                    <input name="pekerjaan_wali" id="ortu-pekerjaan-wali" class="form-input" placeholder="Pekerjaan wali">
                </div>
            </div>
        </fieldset>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="orang-tua-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/portal-access.js') }}?v=7"></script>
@endpush
