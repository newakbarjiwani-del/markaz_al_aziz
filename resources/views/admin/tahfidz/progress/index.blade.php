@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Progress Hafalan"
    subtitle="Status hafalan dan riwayat murajaah"
    :ajax-url="route('admin.tahfidz.progress.data')"
    :columns="['Siswa', 'Rentang', 'Status', 'Terakhir', 'Aksi']">
    @can('tahfidz.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahfidz-progress-modal"
                    data-form-reset="tahfidz-progress-form"
                    data-store-url="{{ route('admin.tahfidz.progress.store') }}"
                    data-modal-title="Tambah Progress">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Progress
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-progress-modal" title="Tambah Progress" size="lg">
    <form id="tahfidz-progress-form"
          data-fetch-form
          data-default-action="{{ route('admin.tahfidz.progress.store') }}"
          data-reload-table
          data-close-modal="tahfidz-progress-modal"
          action="{{ route('admin.tahfidz.progress.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <div class="form-section__body space-y-4">
                <x-siswa-select name="siswa_id" id="progress-siswa_id" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="progress-surah_id">Surah</label>
                        <select name="surah_id" id="progress-surah_id" class="form-input" data-s2 required>
                            <option value="">Pilih</option>
                            @foreach($surahs as $surah)
                                <option value="{{ $surah->id }}">{{ $surah->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="progress-ayah_from">Ayat dari</label>
                        <input type="number" name="ayah_from" id="progress-ayah_from" class="form-input" min="1" required>
                    </div>
                    <div>
                        <label class="form-label" for="progress-ayah_to">Ayat sampai</label>
                        <input type="number" name="ayah_to" id="progress-ayah_to" class="form-input" min="1" required>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="progress-status">Status</label>
                    <select name="status" id="progress-status" class="form-input" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="progress-note">Catatan murajaah</label>
                    <textarea name="note" id="progress-note" class="form-input" rows="2"></textarea>
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-progress-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>

<x-modal id="tahfidz-progress-update-modal" title="Perbarui Progress" size="md">
    <form id="tahfidz-progress-update-form"
          data-fetch-form
          data-reload-table
          data-close-modal="tahfidz-progress-update-modal"
          method="POST"
          class="space-y-5">
        @csrf
        @method('PUT')
        <div>
            <label class="form-label" for="progress-update-status">Status</label>
            <select name="status" id="progress-update-status" class="form-input" required>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="progress-update-note">Catatan</label>
            <textarea name="note" id="progress-update-note" class="form-input" rows="2"></textarea>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="verified" value="1" id="progress-update-verified" class="form-checkbox">
            Verifikasi oleh guru/admin
        </label>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-progress-update-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
