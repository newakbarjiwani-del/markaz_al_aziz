@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Program Tahfidz"
    subtitle="Program dan angkatan halaqoh"
    :ajax-url="route('admin.tahfidz.program.data')"
    :columns="['Nama', 'Angkatan', 'Peserta', 'Status', 'Aksi']">
    @can('tahfidz.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahfidz-program-modal"
                    data-form-reset="tahfidz-program-form"
                    data-store-url="{{ route('admin.tahfidz.program.store') }}"
                    data-modal-title="Tambah Program">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Program
            </button>
        </x-slot:actions>
    @endcan
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-program-modal" title="Tambah / Ubah Program" size="lg">
    <form id="tahfidz-program-form"
          data-fetch-form
          data-default-action="{{ route('admin.tahfidz.program.store') }}"
          data-reload-table
          data-close-modal="tahfidz-program-modal"
          action="{{ route('admin.tahfidz.program.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Program</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="program-name">Nama program</label>
                    <input type="text" name="name" id="program-name" class="form-input" required placeholder="ITQON">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="program-angkatan">Angkatan</label>
                        <input type="number" name="angkatan" id="program-angkatan" class="form-input" min="1" required>
                    </div>
                    <div>
                        <label class="form-label" for="program-peserta_label">Label peserta</label>
                        <input type="text" name="peserta_label" id="program-peserta_label" class="form-input" required value="SANTRIWATI">
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="program-is_active" value="1" class="form-checkbox" checked>
                    Aktif
                </label>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-program-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
