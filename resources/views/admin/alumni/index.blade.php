@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Data Alumni"
    subtitle="Registri alumni dan tautan ke tracer study"
    :ajax-url="route('admin.alumni.alumni.data')"
    :columns="['Nama', 'NIS', 'Angkatan', 'Sekolah', 'Tracer', 'Status', 'Aksi']">
    @can('alumni.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="alumni-modal"
                    data-form-reset="alumni-form"
                    data-store-url="{{ route('admin.alumni.alumni.store') }}"
                    data-modal-title="Tambah Alumni">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Alumni
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-active">Status</label>
                <select name="is_active" id="filter-active" class="form-input">
                    <option value="">Semua</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-angkatan">Angkatan</label>
                <input type="text" name="angkatan" id="filter-angkatan" class="form-input" placeholder="mis. 2020">
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="alumni-modal" title="Tambah / Ubah Alumni" size="lg">
    <form id="alumni-form"
          data-fetch-form
          data-default-action="{{ route('admin.alumni.alumni.store') }}"
          data-reload-table
          data-close-modal="alumni-modal"
          action="{{ route('admin.alumni.alumni.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Identitas</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="alumni-name">Nama</label>
                    <input name="name" id="alumni-name" class="form-input" required maxlength="255">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="alumni-nis">NIS</label>
                        <input name="nis" id="alumni-nis" class="form-input" maxlength="50">
                    </div>
                    <div>
                        <label class="form-label" for="alumni-angkatan">Angkatan</label>
                        <input name="angkatan" id="alumni-angkatan" class="form-input" maxlength="20" placeholder="tahun lulus">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="alumni-phone">Telepon</label>
                        <input name="phone" id="alumni-phone" class="form-input" maxlength="30">
                    </div>
                    <div>
                        <label class="form-label" for="alumni-email">Email</label>
                        <input type="email" name="email" id="alumni-email" class="form-input" maxlength="255">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="alumni-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="alumni-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="alumni-address">Alamat</label>
                    <textarea name="address" id="alumni-address" class="form-input" rows="2"></textarea>
                </div>
                <div>
                    <label class="form-label" for="alumni-notes">Catatan</label>
                    <textarea name="notes" id="alumni-notes" class="form-input" rows="2"></textarea>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="alumni-is_active" label="Aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="alumni-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
