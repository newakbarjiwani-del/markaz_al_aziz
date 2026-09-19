@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Berita SPMB"
    subtitle="Berita publik landing SPMB (slug dibuat otomatis dari judul)"
    :ajax-url="route('admin.spmb.berita.data')"
    :columns="['Judul', 'Slug', 'Terbit', 'Status', 'Aksi']">
    @can('spmb.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="spmb-berita-modal"
                    data-form-reset="spmb-berita-form"
                    data-store-url="{{ route('admin.spmb.berita.store') }}"
                    data-modal-title="Tambah Berita">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Berita
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
<x-modal id="spmb-berita-modal" title="Tambah / Ubah Berita" size="lg">
    <form id="spmb-berita-form"
          data-fetch-form
          data-default-action="{{ route('admin.spmb.berita.store') }}"
          data-reload-table
          data-close-modal="spmb-berita-modal"
          action="{{ route('admin.spmb.berita.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Konten</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-berita-title">Judul</label>
                    <input name="title" id="spmb-berita-title" class="form-input" required maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="spmb-berita-body">Isi</label>
                    <textarea name="body" id="spmb-berita-body" class="form-input" rows="8" required></textarea>
                </div>
                <div>
                    <label class="form-label" for="spmb-berita-cover">Cover (opsional)</label>
                    <input type="file" name="cover" id="spmb-berita-cover" class="form-input" accept="image/jpeg,image/png,image/webp">
                </div>
                <div>
                    <label class="form-label" for="spmb-berita-published_at">Tanggal Terbit</label>
                    <input type="datetime-local" name="published_at" id="spmb-berita-published_at" class="form-input">
                </div>
                <div>
                    <x-form.checkbox name="is_published" id="spmb-berita-published" label="Terbitkan" :checked="false" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-berita-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
