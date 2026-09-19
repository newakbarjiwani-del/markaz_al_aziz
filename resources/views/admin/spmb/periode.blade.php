@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Periode SPMB"
    subtitle="Kelola periode penerimaan; hanya satu periode aktif sekaligus"
    :ajax-url="route('admin.spmb.periode.data')"
    :columns="['Nama', 'Sekolah', 'Tahun Akademik', 'Buka', 'Tutup', 'Status', 'Aksi']">
    @can('spmb.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="spmb-periode-modal"
                    data-form-reset="spmb-periode-form"
                    data-store-url="{{ route('admin.spmb.periode.store') }}"
                    data-modal-title="Tambah Periode SPMB">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Periode
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-status">Status Aktif</label>
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
<x-modal id="spmb-periode-modal" title="Tambah / Ubah Periode SPMB" size="lg">
    <form id="spmb-periode-form"
          data-fetch-form
          data-default-action="{{ route('admin.spmb.periode.store') }}"
          data-reload-table
          data-close-modal="spmb-periode-modal"
          action="{{ route('admin.spmb.periode.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Periode</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="spmb-periode-name">Nama Periode</label>
                    <input name="name" id="spmb-periode-name" class="form-input" required maxlength="255" placeholder="mis. SPMB 2026/2027 Gelombang 1">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="spmb-periode-sekolah_id">Sekolah</label>
                        <select name="sekolah_id" id="spmb-periode-sekolah_id" class="form-input" data-s2>
                            <option value="">Semua / opsional</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="spmb-periode-tahun_akademik_id">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="spmb-periode-tahun_akademik_id" class="form-input" data-s2>
                            <option value="">—</option>
                            @foreach($tahunAkademik as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="spmb-periode-opens_at">Dibuka</label>
                        <input type="datetime-local" name="opens_at" id="spmb-periode-opens_at" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="spmb-periode-closes_at">Ditutup</label>
                        <input type="datetime-local" name="closes_at" id="spmb-periode-closes_at" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="spmb-periode-description">Deskripsi</label>
                    <textarea name="description" id="spmb-periode-description" class="form-input" rows="3" maxlength="5000"></textarea>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="spmb-periode-active" label="Periode aktif" :checked="false" />
                    <p class="mt-1 text-xs text-slate-500">Menandai aktif akan menonaktifkan periode lain.</p>
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="spmb-periode-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
