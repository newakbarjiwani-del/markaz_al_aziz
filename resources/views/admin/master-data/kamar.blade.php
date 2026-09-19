@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'kamar'])

<x-admin.datatable-page
    title="Daftar Kamar"
    subtitle="Katalog kamar pondok/asrama lintas sekolah"
    :ajax-url="route('admin.master-data.kamar.data')"
    :columns="['Kode', 'Nama', 'Blok', 'Kapasitas', 'Status', 'Aksi']"
    :column-options="[
        4 => ['html' => true],
        5 => ['html' => true],
    ]">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="kamar-modal"
                    data-form-reset="kamar-form"
                    data-store-url="{{ route('admin.master-data.kamar.store') }}"
                    data-modal-title="Tambah Kamar">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Kamar
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-kamar-status">Status</label>
            <select name="is_active" id="filter-kamar-status" class="form-input">
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
<x-modal id="kamar-modal" title="Tambah / Ubah Kamar">
    <form id="kamar-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.kamar.store') }}"
          data-reload-table
          data-close-modal="kamar-modal"
          action="{{ route('admin.master-data.kamar.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Kamar</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="kamar-kode">Kode <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <input name="kode" id="kamar-kode" class="form-input" placeholder="mis. A-01">
                </div>
                <div>
                    <label class="form-label" for="kamar-nama">Nama Kamar</label>
                    <input name="nama" id="kamar-nama" class="form-input" required placeholder="mis. A-01">
                </div>
                <div>
                    <label class="form-label" for="kamar-blok">Blok / Gedung <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <input name="blok" id="kamar-blok" class="form-input" placeholder="mis. Asrama A">
                </div>
                <div>
                    <label class="form-label" for="kamar-kapasitas">Kapasitas <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <input type="number" name="kapasitas" id="kamar-kapasitas" class="form-input" min="1" max="999" placeholder="Jumlah maks. santri">
                </div>
                <div>
                    <label class="form-label" for="kamar-sort-order">Urutan</label>
                    <input type="number" name="sort_order" id="kamar-sort-order" class="form-input" min="0" max="9999" value="0">
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="kamar-active" label="Kamar aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="kamar-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
