@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'jenis-tagihan'])

<x-admin.datatable-page
    title="Daftar Jenis Tagihan"
    subtitle="Kelola jenis biaya/tagihan untuk modul keuangan"
    :ajax-url="route('admin.master-data.jenis-tagihan.data')"
    :columns="['Nama', 'Kode', 'Nominal Default', 'Generate SPP', 'Status', 'Aksi']"
    :column-options="[
        3 => ['html' => true],
        4 => ['html' => true],
        5 => ['html' => true],
    ]">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button"
                    class="btn-secondary flex-1 sm:flex-none"
                    data-fetch-post="{{ route('admin.master-data.jenis-tagihan.seed-spp-bulanan') }}"
                    data-confirm-title="Buat Jenis SPP Bulanan"
                    data-confirm-message="Buat atau sinkronkan jenis SPP JANUARI sampai SPP DESEMBER?"
                    data-confirm-text="Ya, Buat Jenis"
                    data-confirm-tone="warning"
                    data-confirm-icon="ti-calendar-plus"
                    data-confirm-header-icon="ti-calendar-plus"
                    data-confirm-no-footnote>
                <x-icon name="calendar-month" size="sm" class="mr-1" /> Buat Jenis SPP Bulanan
            </button>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="jenis-tagihan-modal"
                    data-form-reset="jenis-tagihan-form"
                    data-store-url="{{ route('admin.master-data.jenis-tagihan.store') }}"
                    data-modal-title="Tambah Jenis Tagihan">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jenis Tagihan
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
<x-modal id="jenis-tagihan-modal" title="Tambah / Ubah Jenis Tagihan">
    <form id="jenis-tagihan-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.jenis-tagihan.store') }}"
          data-partial-edit-notice="jenis-tagihan-partial-notice"
          data-reload-table
          data-close-modal="jenis-tagihan-modal"
          action="{{ route('admin.master-data.jenis-tagihan.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div id="jenis-tagihan-partial-notice"
             class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            Jenis tagihan sudah dipakai pada data tagihan. Hanya <strong>nominal default</strong> dan <strong>pengaturan SPP</strong> yang dapat diubah.
        </div>
        <section class="form-section">
            <h4 class="form-section__title">Informasi Jenis Tagihan</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="jenis-tagihan-name">Nama Jenis</label>
                    <input name="name" id="jenis-tagihan-name" class="form-input" required placeholder="mis. SPP, Uang Gedung">
                </div>
                <div>
                    <label class="form-label" for="jenis-tagihan-code">Kode <span class="text-muted text-xs font-normal">(opsional)</span></label>
                    <input name="code" id="jenis-tagihan-code" class="form-input" placeholder="mis. spp">
                </div>
                <div>
                    <label class="form-label" for="jenis-tagihan-description">Deskripsi</label>
                    <input name="description" id="jenis-tagihan-description" class="form-input" placeholder="Keterangan singkat">
                </div>
                <div>
                    <label class="form-label" for="jenis-tagihan-default-amount">Nominal Default (Rp)</label>
                    <x-form.amount name="default_amount" id="jenis-tagihan-default-amount" :min="0" placeholder="Kosongkan jika tidak ada default" />
                </div>
                <div>
                    <label class="form-label" for="jenis-tagihan-sort-order">Urutan</label>
                    <input type="number" name="sort_order" id="jenis-tagihan-sort-order" class="form-input" min="0" max="9999" value="0">
                </div>
                <div>
                    <x-form.checkbox name="is_spp" id="jenis-tagihan-is-spp" label="Jenis untuk generate SPP massal" />
                    <p class="mt-1 text-xs text-slate-500">Untuk skema bulanan, tandai jenis seperti <strong>SPP JANUARI</strong> sampai <strong>SPP DESEMBER</strong>.</p>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="jenis-tagihan-active" label="Jenis tagihan aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="jenis-tagihan-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
