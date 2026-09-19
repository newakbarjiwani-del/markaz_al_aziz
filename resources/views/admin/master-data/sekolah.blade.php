@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'sekolah'])

<x-admin.datatable-page
    title="Daftar Sekolah"
    subtitle="Kelola unit sekolah (PAUD, MTs, MA, Takhasus, dll.)"
    :ajax-url="route('admin.master-data.sekolah.data')"
    :columns="['Kode', 'Nama', 'Alamat', 'Telepon', 'Status', 'Aksi']">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="sekolah-modal"
                    data-form-reset="sekolah-form"
                    data-store-url="{{ route('admin.master-data.sekolah.store') }}"
                    data-modal-title="Tambah Sekolah">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Sekolah
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-status">Status</label>
            <select name="is_active" id="filter-status" class="form-input">
                <option value="">Semua</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="sekolah-modal" title="Tambah / Ubah Sekolah">
    <form id="sekolah-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.sekolah.store') }}"
          data-reload-table
          data-close-modal="sekolah-modal"
          action="{{ route('admin.master-data.sekolah.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Sekolah</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="sekolah-code">Kode</label>
                    <input name="code" id="sekolah-code" class="form-input" required placeholder="mis. ma, mts, paud">
                </div>
                <div>
                    <label class="form-label" for="sekolah-name">Nama Sekolah</label>
                    <input name="name" id="sekolah-name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="sekolah-address">Alamat</label>
                    <textarea name="address" id="sekolah-address" class="form-input" rows="2"></textarea>
                </div>
                <div>
                    <label class="form-label" for="sekolah-phone">Telepon</label>
                    <input name="phone" id="sekolah-phone" class="form-input">
                </div>
                <div>
                    <x-form.checkbox name="is_active" label="Sekolah aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="sekolah-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
