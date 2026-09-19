@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Booklet Sekolah"
    subtitle="Majalah dan buku digital sekolah"
    :ajax-url="route('admin.booklet.booklet.data')"
    :columns="['Judul', 'Sekolah', 'Halaman', 'Terbit', 'Status', 'Aksi']">
    @can('booklet.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="booklet-modal"
                    data-form-reset="booklet-form"
                    data-store-url="{{ route('admin.booklet.booklet.store') }}"
                    data-modal-title="Tambah Booklet">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Booklet
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
<x-modal id="booklet-modal" title="Tambah / Ubah Booklet" size="lg">
    <form id="booklet-form"
          data-fetch-form
          data-default-action="{{ route('admin.booklet.booklet.store') }}"
          data-reload-table
          data-close-modal="booklet-modal"
          action="{{ route('admin.booklet.booklet.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Booklet</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="booklet-title">Judul</label>
                    <input name="title" id="booklet-title" class="form-input" required maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="booklet-summary">Ringkasan</label>
                    <textarea name="summary" id="booklet-summary" class="form-input" rows="3"></textarea>
                </div>
                <div>
                    <label class="form-label" for="booklet-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="booklet-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="booklet-published_at">Tanggal terbit</label>
                        <input type="datetime-local" name="published_at" id="booklet-published_at" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="booklet-cover">Cover</label>
                        <input type="file" name="cover" id="booklet-cover" class="form-input" accept="image/*">
                    </div>
                </div>
                <div>
                    <x-form.checkbox name="is_published" id="booklet-is_published" label="Terbitkan" />
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="booklet-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
