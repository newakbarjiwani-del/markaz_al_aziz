@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Pengumuman SPMB"
    subtitle="Kelola pengumuman publik untuk landing SPMB"
    :ajax-url="route('admin.spmb.pengumuman.data')"
    :columns="['Judul', 'Ringkasan', 'Terbit', 'Status', 'Aksi']">
    @can('spmb.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="spmb-pengumuman-modal"
                    data-form-reset="spmb-pengumuman-form"
                    data-store-url="{{ route('admin.spmb.pengumuman.store') }}"
                    data-modal-title="Tambah Pengumuman">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Pengumuman
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
<x-modal id="spmb-pengumuman-modal" title="Tambah / Ubah Pengumuman" size="lg">
    <form id="spmb-pengumuman-form"
          data-fetch-form
          data-default-action="{{ route('admin.spmb.pengumuman.store') }}"
          data-reload-table
          data-close-modal="spmb-pengumuman-modal"
          action="{{ route('admin.spmb.pengumuman.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Konten</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-pengumuman-title">Judul</label>
                    <input name="title" id="spmb-pengumuman-title" class="form-input" required maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="spmb-pengumuman-body">Isi</label>
                    <textarea name="body" id="spmb-pengumuman-body" class="form-input" rows="8" required></textarea>
                </div>
                <div>
                    <label class="form-label" for="spmb-pengumuman-published_at">Tanggal Terbit</label>
                    <input type="datetime-local" name="published_at" id="spmb-pengumuman-published_at" class="form-input">
                </div>
                <div>
                    <x-form.checkbox name="is_published" id="spmb-pengumuman-published" label="Terbitkan" :checked="false" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-pengumuman-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
