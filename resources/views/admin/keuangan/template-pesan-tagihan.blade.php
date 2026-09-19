@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Template Pesan Tagihan WA"
    subtitle="Kelola variasi pesan pengingat tagihan; gunakan placeholder agar pesan tidak identik (mengurangi risiko spam)."
    :ajax-url="route('admin.keuangan.template-pesan-tagihan.data')"
    export-filename="Template Pesan Tagihan WA"
    :columns="['Urutan', 'Nama', 'Kategori', 'Pratinjau', 'Status', 'Aksi']"
    :column-options="[
        2 => ['html' => true],
        3 => ['html' => true],
        4 => ['html' => true],
        5 => ['html' => true],
    ]">
    @can('finance.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="template-pesan-tagihan-modal"
                    data-form-reset="template-pesan-tagihan-form"
                    data-store-url="{{ route('admin.keuangan.template-pesan-tagihan.store') }}"
                    data-modal-title="Tambah Template Pesan">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Template
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-kategori">Kategori</label>
                <select name="kategori" id="filter-kategori" class="form-input">
                    <option value="">Semua</option>
                    @foreach($kategoriOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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

@include('admin.keuangan.partials.template-pesan-wa-page-guide')
@endsection

@push('modals')
<x-modal id="template-pesan-tagihan-modal" title="Tambah / Ubah Template Pesan" size="lg">
    <form id="template-pesan-tagihan-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.template-pesan-tagihan.store') }}"
          data-reload-table
          data-close-modal="template-pesan-tagihan-modal"
          action="{{ route('admin.keuangan.template-pesan-tagihan.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Template</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="template-pesan-nama">Nama Template</label>
                    <input name="nama" id="template-pesan-nama" class="form-input" required placeholder="mis. Formal Indonesia">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="template-pesan-kategori">Kategori</label>
                        <select name="kategori" id="template-pesan-kategori" class="form-input" required>
                            @foreach($kategoriOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="template-pesan-sort">Urutan</label>
                        <input type="number" name="sort_order" id="template-pesan-sort" class="form-input" min="0" max="9999" value="0">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="template-pesan-wa-editor">Isi Pesan</label>
                    <div id="template-pesan-wa-editor" class="template-pesan-wa-editor"></div>
                    <textarea name="isi_pesan" id="template-pesan-isi" class="hidden" aria-hidden="true" tabindex="-1"></textarea>
                    @include('admin.keuangan.partials.template-pesan-wa-editor-guide')
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="template-pesan-active" label="Template aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="template-pesan-tagihan-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<link rel="stylesheet" href="{{ asset('vendor/whatsapp-editor/prosemirror.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('vendor/whatsapp-editor/whatsapp-editor.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/whatsapp-editor-overrides.css') }}?v=2">
<script src="{{ asset('vendor/whatsapp-editor/whatsapp-editor.js') }}?v=1"></script>
<script src="{{ asset('js/template-pesan-wa-editor.js') }}?v=4"></script>
@endpush
