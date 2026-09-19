@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Katalog Pelanggaran"
    subtitle="Katalog master jenis pelanggaran (ringan, sedang, berat) beserta point dan sanksi"
    :ajax-url="route('admin.prestasi-pelanggaran.katalog-pelanggaran.data')"
    export-filename="Katalog Pelanggaran"
    :columns="['Level', 'Bidang', 'Nama', 'Point', 'Sanksi', 'Status', 'Aksi']"
    :column-options="$mainTableColumnOptions">
    @can('katalog-pelanggaran.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="katalog-pelanggaran-modal"
                    data-form-reset="katalog-pelanggaran-form"
                    data-store-url="{{ route('admin.prestasi-pelanggaran.katalog-pelanggaran.store') }}"
                    data-modal-title="Tambah Jenis Pelanggaran">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jenis Pelanggaran
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-level">Level</label>
                <select name="level" id="filter-level" class="form-input">
                    <option value="">Semua Level</option>
                    <option value="ringan" @selected(request('level') === 'ringan')>Ringan</option>
                    <option value="sedang" @selected(request('level') === 'sedang')>Sedang</option>
                    <option value="berat" @selected(request('level') === 'berat')>Berat</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="is_active" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                    <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="katalog-pelanggaran-modal" title="Tambah / Ubah Jenis Pelanggaran" size="lg">
    <form id="katalog-pelanggaran-form"
          data-fetch-form
          data-default-action="{{ route('admin.prestasi-pelanggaran.katalog-pelanggaran.store') }}"
          data-partial-edit-notice="katalog-pelanggaran-partial-notice"
          data-reload-table
          data-close-modal="katalog-pelanggaran-modal"
          action="{{ route('admin.prestasi-pelanggaran.katalog-pelanggaran.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div id="katalog-pelanggaran-partial-notice"
             class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Jenis pelanggaran ini sudah dipakai pada data pelanggaran. <strong>Nama pelanggaran tidak dapat diubah</strong>; pengaturan lain tetap bisa dimodifikasi.
        </div>

        <section class="form-section">
            <h4 class="form-section__title">Informasi Pelanggaran</h4>
            <div class="form-section__body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-level">Level</label>
                        <select name="level" id="katalog-pelanggaran-level" class="form-input" required>
                            <option value="">Pilih level</option>
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-bidang">Bidang</label>
                        <input type="text" name="bidang" id="katalog-pelanggaran-bidang" class="form-input" maxlength="100" required placeholder="mis. Kebersihan, Kedisiplinan">
                    </div>
                </div>

                <div>
                    <label class="form-label" for="katalog-pelanggaran-nama">Nama Pelanggaran</label>
                    <input type="text" name="nama" id="katalog-pelanggaran-nama" class="form-input" maxlength="255" required placeholder="mis. Terlambat salat berjamaah">
                    <p class="mt-1 text-xs text-slate-500">Nama tidak dapat diubah setelah dipakai pada data pelanggaran.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-point">Point <span class="text-muted text-xs font-normal">(default)</span></label>
                        <input type="number" name="point" id="katalog-pelanggaran-point" class="form-input" min="0" value="0">
                    </div>
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-sanction">Sanksi <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <select name="sanction" id="katalog-pelanggaran-sanction" class="form-input">
                            <option value="">— Tidak ada —</option>
                            @foreach(\App\Support\PelanggaranSanction::labels() as $code => $label)
                                <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-kode">Kode <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <input type="text" name="kode" id="katalog-pelanggaran-kode" class="form-input" maxlength="50" placeholder="mis. PLG-001">
                    </div>
                    <div>
                        <label class="form-label" for="katalog-pelanggaran-sort-order">Urutan</label>
                        <input type="number" name="sort_order" id="katalog-pelanggaran-sort-order" class="form-input" min="0" max="9999" value="0">
                    </div>
                </div>

                <div>
                    <label class="form-label" for="katalog-pelanggaran-keterangan">Keterangan</label>
                    <textarea name="keterangan" id="katalog-pelanggaran-keterangan" class="form-input" rows="3" maxlength="1000" placeholder="Keterangan singkat (opsional)"></textarea>
                </div>

                <div>
                    <label class="form-label" for="katalog-pelanggaran-sekolah">Sekolah <span class="text-muted text-xs font-normal">(opsional — kosong = katalog semua sekolah)</span></label>
                    <select name="sekolah_id" id="katalog-pelanggaran-sekolah" class="form-input">
                        <option value="">Semua sekolah (universal)</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-form.checkbox name="is_active" id="katalog-pelanggaran-active" label="Jenis pelanggaran aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="katalog-pelanggaran-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
