@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar Pelajaran"
    subtitle="Kelola mata pelajaran untuk jadwal absensi"
    :ajax-url="route('admin.absensi.pelajaran.data')"
    :columns="['Sekolah', 'Kode', 'Nama Pelajaran', 'Status', 'Aksi']">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="pelajaran-modal"
                data-form-reset="pelajaran-form"
                data-store-url="{{ route('admin.absensi.pelajaran.store') }}"
                data-modal-title="Tambah Pelajaran">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Pelajaran
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
        <div>
            <label class="form-label">Status</label>
            <select name="is_active" id="filter-is_active" class="form-input">
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
<x-modal id="pelajaran-modal" title="Tambah / Ubah Pelajaran">
    <form id="pelajaran-form"
          data-fetch-form
          data-default-action="{{ route('admin.absensi.pelajaran.store') }}"
          data-reload-table
          data-close-modal="pelajaran-modal"
          action="{{ route('admin.absensi.pelajaran.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Pelajaran</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label for="pelajaran-sekolah_id" class="form-label">Sekolah</label>
                    <select name="sekolah_id" id="pelajaran-sekolah_id" class="form-input" required data-s2>
                        @if($schools->count() > 1)
                            <option value="all">Semua Sekolah</option>
                        @endif
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pelajaran-code" class="form-label">Kode</label>
                    <input name="code" id="pelajaran-code" class="form-input" placeholder="Opsional, mis. MAT">
                </div>
                <div>
                    <label for="pelajaran-name" class="form-label">Nama Pelajaran</label>
                    <input name="name" id="pelajaran-name" class="form-input" required>
                </div>
                <div>
                    <label for="pelajaran-description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="pelajaran-description" class="form-input" rows="2" placeholder="Opsional"></textarea>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="pelajaran-is_active" label="Pelajaran aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="pelajaran-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
