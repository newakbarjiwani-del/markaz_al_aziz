@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Mata Pelajaran"
    subtitle="Katalog mata pelajaran per sekolah"
    :ajax-url="route('admin.akademik.mata-pelajaran.data')"
    :columns="['Kode', 'Nama', 'Kelompok', 'Sekolah', 'Status', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-mapel-modal"
                    data-form-reset="akademik-mapel-form"
                    data-store-url="{{ route('admin.akademik.mata-pelajaran.store') }}"
                    data-modal-title="Tambah Mata Pelajaran">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Mapel
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
<x-modal id="akademik-mapel-modal" title="Tambah / Ubah Mata Pelajaran" size="lg">
    <form id="akademik-mapel-form"
          data-fetch-form
          data-default-action="{{ route('admin.akademik.mata-pelajaran.store') }}"
          data-reload-table
          data-close-modal="akademik-mapel-modal"
          action="{{ route('admin.akademik.mata-pelajaran.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Mapel</h4>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-mapel-code">Kode</label>
                        <input name="code" id="akademik-mapel-code" class="form-input" maxlength="50" placeholder="mis. MTK">
                    </div>
                    <div>
                        <label class="form-label" for="akademik-mapel-kelompok">Kelompok</label>
                        <input name="kelompok" id="akademik-mapel-kelompok" class="form-input" maxlength="255" placeholder="mis. Umum">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="akademik-mapel-name">Nama</label>
                    <input name="name" id="akademik-mapel-name" class="form-input" required maxlength="255">
                </div>
                <div>
                    <label class="form-label" for="akademik-mapel-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="akademik-mapel-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="akademik-mapel-active" label="Aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-mapel-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
