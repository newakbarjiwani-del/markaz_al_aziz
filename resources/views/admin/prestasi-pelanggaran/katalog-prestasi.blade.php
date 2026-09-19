@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Katalog Prestasi"
    subtitle="Katalog master jenis prestasi beserta point default (kosong hingga diisi admin)"
    :ajax-url="route('admin.prestasi-pelanggaran.katalog-prestasi.data')"
    export-filename="Katalog Prestasi"
    :columns="['Bidang', 'Nama', 'Point', 'Status', 'Aksi']"
    :column-options="$mainTableColumnOptions">
    @can('katalog-prestasi.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="katalog-prestasi-modal"
                    data-form-reset="katalog-prestasi-form"
                    data-store-url="{{ route('admin.prestasi-pelanggaran.katalog-prestasi.store') }}"
                    data-modal-title="Tambah Jenis Prestasi">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jenis Prestasi
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
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
<x-modal id="katalog-prestasi-modal" title="Tambah / Ubah Jenis Prestasi" size="lg">
    <form id="katalog-prestasi-form"
          data-fetch-form
          data-default-action="{{ route('admin.prestasi-pelanggaran.katalog-prestasi.store') }}"
          data-partial-edit-notice="katalog-prestasi-partial-notice"
          data-reload-table
          data-close-modal="katalog-prestasi-modal"
          action="{{ route('admin.prestasi-pelanggaran.katalog-prestasi.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div id="katalog-prestasi-partial-notice"
             class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Jenis prestasi ini sudah dipakai pada data prestasi. <strong>Nama prestasi tidak dapat diubah</strong>; pengaturan lain tetap bisa dimodifikasi.
        </div>

        <section class="form-section">
            <h4 class="form-section__title">Informasi Prestasi</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="katalog-prestasi-nama">Nama Prestasi</label>
                    <input type="text" name="nama" id="katalog-prestasi-nama" class="form-input" maxlength="255" required placeholder="mis. Juara 1 Lomba Matematika">
                    <p class="mt-1 text-xs text-slate-500">Nama tidak dapat diubah setelah dipakai pada data prestasi.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-prestasi-bidang">Bidang <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <input type="text" name="bidang" id="katalog-prestasi-bidang" class="form-input" maxlength="100" placeholder="mis. Akademik, Olahraga">
                    </div>
                    <div>
                        <label class="form-label" for="katalog-prestasi-point">Point <span class="text-muted text-xs font-normal">(default)</span></label>
                        <input type="number" name="point" id="katalog-prestasi-point" class="form-input" min="0" value="0">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="katalog-prestasi-kode">Kode <span class="text-muted text-xs font-normal">(opsional)</span></label>
                        <input type="text" name="kode" id="katalog-prestasi-kode" class="form-input" maxlength="50" placeholder="mis. PRS-001">
                    </div>
                    <div>
                        <label class="form-label" for="katalog-prestasi-sort-order">Urutan</label>
                        <input type="number" name="sort_order" id="katalog-prestasi-sort-order" class="form-input" min="0" max="9999" value="0">
                    </div>
                </div>

                <div>
                    <label class="form-label" for="katalog-prestasi-keterangan">Keterangan</label>
                    <textarea name="keterangan" id="katalog-prestasi-keterangan" class="form-input" rows="3" maxlength="1000" placeholder="Keterangan singkat (opsional)"></textarea>
                </div>

                <div>
                    <label class="form-label" for="katalog-prestasi-sekolah">Sekolah <span class="text-muted text-xs font-normal">(opsional — kosong = katalog semua sekolah)</span></label>
                    <select name="sekolah_id" id="katalog-prestasi-sekolah" class="form-input">
                        <option value="">Semua sekolah (universal)</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-form.checkbox name="is_active" id="katalog-prestasi-active" label="Jenis prestasi aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="katalog-prestasi-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
