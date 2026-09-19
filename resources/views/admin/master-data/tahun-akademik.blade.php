@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'tahun-akademik'])

<x-admin.datatable-page
    title="Daftar Tahun Akademik"
    subtitle="Kelola tahun ajaran (universal untuk semua sekolah)"
    :ajax-url="route('admin.master-data.tahun-akademik.data')"
    :columns="['Tahun', 'Status', 'Aksi']">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahun-akademik-modal"
                    data-form-reset="tahun-akademik-form"
                    data-store-url="{{ route('admin.master-data.tahun-akademik.store') }}"
                    data-modal-title="Tambah Tahun Akademik">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Tahun Akademik
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
<x-modal id="tahun-akademik-modal" title="Tambah / Ubah Tahun Akademik">
    <form id="tahun-akademik-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.tahun-akademik.store') }}"
          data-reload-table
          data-close-modal="tahun-akademik-modal"
          action="{{ route('admin.master-data.tahun-akademik.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Tahun Akademik</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="tahun-akademik-name">Nama Tahun</label>
                    <input name="name" id="tahun-akademik-name" class="form-input" required placeholder="mis. 2025/2026">
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="tahun-akademik-active" label="Tahun akademik aktif" :checked="false" />
                </div>
                <p class="text-xs text-slate-500">Menandai aktif akan menonaktifkan tahun akademik lain (katalog universal).</p>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="tahun-akademik-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
