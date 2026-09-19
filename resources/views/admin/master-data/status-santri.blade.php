@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'status-santri'])

<x-admin.datatable-page
    title="Daftar Status Santri"
    subtitle="Katalog status santri pondok (lintas sekolah)"
    :ajax-url="route('admin.master-data.status-santri.data')"
    :columns="['Nama', 'Urutan', 'Status', 'Aksi']"
    :column-options="[
        2 => ['html' => true],
        3 => ['html' => true],
    ]">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="status-santri-modal"
                    data-form-reset="status-santri-form"
                    data-store-url="{{ route('admin.master-data.status-santri.store') }}"
                    data-modal-title="Tambah Status Santri">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Status
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-status-santri-active">Status</label>
            <select name="is_active" id="filter-status-santri-active" class="form-input">
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
<x-modal id="status-santri-modal" title="Tambah / Ubah Status Santri">
    <form id="status-santri-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.status-santri.store') }}"
          data-reload-table
          data-close-modal="status-santri-modal"
          action="{{ route('admin.master-data.status-santri.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Status</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="status-santri-nama">Nama Status</label>
                    <input name="nama" id="status-santri-nama" class="form-input" required placeholder="mis. Santri, Pengurus">
                </div>
                <div>
                    <label class="form-label" for="status-santri-sort-order">Urutan</label>
                    <input type="number" name="sort_order" id="status-santri-sort-order" class="form-input" min="0" max="9999" value="0">
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="status-santri-active" label="Status aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="status-santri-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
