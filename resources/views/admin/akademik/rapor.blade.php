@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Rapor"
    subtitle="Bangun draft dari nilai dan finalisasi rapor"
    :ajax-url="route('admin.akademik.rapor.data')"
    :columns="['Siswa', 'Kelas', 'Tahun', 'Semester', 'Status', 'Mapel', 'Finalisasi', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-rapor-build-modal"
                    data-form-reset="akademik-rapor-build-form"
                    data-modal-title="Bangun Draft Rapor">
                <x-icon name="plus" size="sm" class="mr-1" /> Bangun Draft
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-tahun">Tahun</label>
                <select name="tahun_akademik_id" id="filter-tahun" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($tahunAkademik as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-semester">Semester</label>
                <select name="semester" id="filter-semester" class="form-input">
                    <option value="">Semua</option>
                    @foreach($semesters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    <option value="draft">Draft</option>
                    <option value="final">Final</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
@can('akademik.create')
<x-modal id="akademik-rapor-build-modal" title="Bangun Draft Rapor" size="lg">
    <form id="akademik-rapor-build-form"
          data-fetch-form
          data-reload-table
          data-close-modal="akademik-rapor-build-modal"
          action="{{ route('admin.akademik.rapor.build') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <div>
            <label class="form-label" for="rapor-build-siswa_id">Siswa</label>
            <select name="siswa_id" id="rapor-build-siswa_id" class="form-input" data-s2 required>
                <option value="">—</option>
                @foreach($siswaOptions as $siswa)
                    <option value="{{ $siswa->id }}">{{ $siswa->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="rapor-build-tahun_akademik_id">Tahun Akademik</label>
                <select name="tahun_akademik_id" id="rapor-build-tahun_akademik_id" class="form-input" data-s2 required>
                    <option value="">—</option>
                    @foreach($tahunAkademik as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="rapor-build-semester">Semester</label>
                <select name="semester" id="rapor-build-semester" class="form-input" required>
                    @foreach($semesters as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="form-label" for="rapor-build-catatan_wali">Catatan Wali</label>
            <textarea name="catatan_wali" id="rapor-build-catatan_wali" class="form-input" rows="3" maxlength="5000"></textarea>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-rapor-build-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">Bangun</button>
        </div>
    </form>
</x-modal>
@endcan
@endpush
