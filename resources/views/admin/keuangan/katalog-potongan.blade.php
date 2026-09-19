@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Katalog Potongan"
    subtitle="Master jenis potongan tagihan; urutan menentukan prioritas penerapan (Beasiswa sebelum Kurang Mampu, dll.)"
    :ajax-url="route('admin.keuangan.katalog-potongan.data')"
    export-filename="Katalog Potongan"
    :columns="['Urutan', 'Nama', 'Tipe', 'Nilai Default', 'Status', 'Aksi']"
    :column-options="$mainTableColumnOptions">
    @can('katalog-potongan.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="katalog-potongan-modal"
                    data-form-reset="katalog-potongan-form"
                    data-store-url="{{ route('admin.keuangan.katalog-potongan.store') }}"
                    data-modal-title="Tambah Jenis Potongan">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jenis Potongan
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
<x-modal id="katalog-potongan-modal" title="Tambah / Ubah Jenis Potongan" size="lg">
    <form id="katalog-potongan-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.katalog-potongan.store') }}"
          data-partial-edit-notice="katalog-potongan-partial-notice"
          data-reload-table
          data-close-modal="katalog-potongan-modal"
          action="{{ route('admin.keuangan.katalog-potongan.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div id="katalog-potongan-partial-notice"
             class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Jenis potongan ini sudah dipakai pada penugasan siswa. <strong>Nama tidak dapat diubah</strong>; pengaturan lain tetap bisa dimodifikasi.
        </div>

        <section class="form-section">
            <h4 class="form-section__title">Informasi Potongan</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="katalog-potongan-nama">Nama</label>
                    <input type="text" name="nama" id="katalog-potongan-nama" class="form-input" maxlength="255" required placeholder="mis. Beasiswa">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-potongan-tipe">Tipe Default</label>
                        <select name="tipe_default" id="katalog-potongan-tipe" class="form-input" required>
                            <option value="percent">Persen</option>
                            <option value="fixed">Nominal tetap</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="katalog-potongan-nilai">Nilai Default</label>
                        <input type="text"
                               name="nilai_default"
                               id="katalog-potongan-nilai"
                               class="form-input onlyNumber"
                               inputmode="numeric"
                               min="1"
                               max="100"
                               value="50"
                               placeholder="1–100"
                               autocomplete="off"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-potongan-kode">Kode <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <input type="text" name="kode" id="katalog-potongan-kode" class="form-input" maxlength="50">
                    </div>
                    <div>
                        <label class="form-label" for="katalog-potongan-sort-order">Urutan Prioritas</label>
                        <input type="number" name="sort_order" id="katalog-potongan-sort-order" class="form-input" min="0" max="9999" value="0">
                        <p class="mt-1 text-xs text-slate-500">Angka lebih kecil diterapkan lebih dulu saat beberapa potongan aktif.</p>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="katalog-potongan-keterangan">Keterangan</label>
                    <textarea name="keterangan" id="katalog-potongan-keterangan" class="form-input" rows="3" maxlength="1000"></textarea>
                </div>

                <div>
                    <label class="form-label" for="katalog-potongan-sekolah">Sekolah <span class="text-muted text-xs font-normal">(kosong = universal)</span></label>
                    <select name="sekolah_id" id="katalog-potongan-sekolah" class="form-input">
                        <option value="">Semua sekolah (universal)</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-form.checkbox name="is_active" id="katalog-potongan-active" label="Jenis potongan aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="katalog-potongan-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/potongan-form.js') }}?v=8"></script>
@endpush
