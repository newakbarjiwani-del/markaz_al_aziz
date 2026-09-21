@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Halaqoh"
    subtitle="Kelompok setoran dan anggota santri"
    :ajax-url="route('admin.tahfidz.halaqoh.data')"
    :columns="['Halaqoh', 'Ustadzah', 'Anggota', 'Aksi']">
    @can('tahfidz.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahfidz-halaqoh-modal"
                    data-form-reset="tahfidz-halaqoh-form"
                    data-store-url="{{ route('admin.tahfidz.halaqoh.store') }}"
                    data-modal-title="Tambah Halaqoh">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Halaqoh
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-program_id">Program</label>
                <select name="program_id" id="filter-program_id" class="form-input">
                    <option value="">Semua</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-halaqoh-modal" title="Tambah / Ubah Halaqoh" size="lg">
    <form id="tahfidz-halaqoh-form"
          data-fetch-form
          data-default-action="{{ route('admin.tahfidz.halaqoh.store') }}"
          data-reload-table
          data-close-modal="tahfidz-halaqoh-modal"
          action="{{ route('admin.tahfidz.halaqoh.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Halaqoh</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="halaqoh-program_id">Program</label>
                    <select name="program_id" id="halaqoh-program_id" class="form-input" required>
                        <option value="">Pilih</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <x-guru-select name="guru_id" id="halaqoh-guru_id" label="Ustadzah" />
                <div>
                    <label class="form-label" for="halaqoh-name">Nama tampilan (opsional)</label>
                    <input type="text" name="name" id="halaqoh-name" class="form-input" placeholder="Kosongkan untuk Halaqoh Ustadzah {nama}">
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-halaqoh-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
