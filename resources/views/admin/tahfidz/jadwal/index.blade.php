@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Jadwal Halaqoh"
    subtitle="Hari dan jam setoran per halaqoh"
    :ajax-url="route('admin.tahfidz.jadwal.data')"
    :columns="['Halaqoh', 'Hari', 'Jam', 'Status', 'Aksi']">
    @can('tahfidz.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahfidz-jadwal-modal"
                    data-form-reset="tahfidz-jadwal-form"
                    data-store-url="{{ route('admin.tahfidz.jadwal.store') }}"
                    data-modal-title="Tambah Jadwal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jadwal
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-halaqoh_id">Halaqoh</label>
                <select name="halaqoh_id" id="filter-halaqoh_id" class="form-input">
                    <option value="">Semua</option>
                    @foreach($halaqoh as $item)
                        <option value="{{ $item->id }}">{{ $item->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-jadwal-modal" title="Tambah / Ubah Jadwal" size="lg">
    <form id="tahfidz-jadwal-form"
          data-fetch-form
          data-default-action="{{ route('admin.tahfidz.jadwal.store') }}"
          data-reload-table
          data-close-modal="tahfidz-jadwal-modal"
          action="{{ route('admin.tahfidz.jadwal.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Slot</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="jadwal-halaqoh_id">Halaqoh</label>
                    <select name="halaqoh_id" id="jadwal-halaqoh_id" class="form-input" required>
                        <option value="">Pilih</option>
                        @foreach($halaqoh as $item)
                            <option value="{{ $item->id }}">{{ $item->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="jadwal-day_of_week">Hari</label>
                    <select name="day_of_week" id="jadwal-day_of_week" class="form-input" required>
                        @foreach($days as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="jadwal-time_start">Mulai</label>
                        <input type="time" name="time_start" id="jadwal-time_start" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="jadwal-time_end">Selesai</label>
                        <input type="time" name="time_end" id="jadwal-time_end" class="form-input" required>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="jadwal-is_active" value="1" class="form-checkbox" checked>
                    Aktif
                </label>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-jadwal-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
