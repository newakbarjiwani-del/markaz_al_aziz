@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Galeri SPMB"
    subtitle="Foto fasilitas/kampus untuk landing publik"
    :ajax-url="route('admin.spmb.galeri.data')"
    :columns="['Preview', 'Judul', 'Caption', 'Urutan', 'Status', 'Aksi']">
    @can('spmb.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="spmb-galeri-modal"
                    data-form-reset="spmb-galeri-form"
                    data-store-url="{{ route('admin.spmb.galeri.store') }}"
                    data-modal-title="Tambah Foto Galeri">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Foto
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-published">Status</label>
                <select name="is_published" id="filter-published" class="form-input">
                    <option value="">Semua</option>
                    <option value="1">Terbit</option>
                    <option value="0">Draf</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="spmb-galeri-modal" title="Tambah / Ubah Galeri" size="lg">
    <form id="spmb-galeri-form"
          data-fetch-form
          data-default-action="{{ route('admin.spmb.galeri.store') }}"
          data-reload-table
          data-close-modal="spmb-galeri-modal"
          action="{{ route('admin.spmb.galeri.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Item Galeri</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-galeri-title">Judul</label>
                    <input name="title" id="spmb-galeri-title" class="form-input" required maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="spmb-galeri-caption">Caption</label>
                    <input name="caption" id="spmb-galeri-caption" class="form-input" maxlength="1000">
                </div>
                <div>
                    <label class="form-label" for="spmb-galeri-image">Gambar</label>
                    <input type="file" name="image" id="spmb-galeri-image" class="form-input" accept="image/jpeg,image/png,image/webp">
                    <p class="mt-1 text-xs text-slate-500">Wajib saat menambah. Kosongkan saat ubah jika tidak mengganti gambar.</p>
                </div>
                <div>
                    <label class="form-label" for="spmb-galeri-sort_order">Urutan</label>
                    <input type="number" name="sort_order" id="spmb-galeri-sort_order" class="form-input" min="0" value="0">
                </div>
                <div>
                    <x-form.checkbox name="is_published" id="spmb-galeri-published" label="Terbitkan" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-galeri-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
