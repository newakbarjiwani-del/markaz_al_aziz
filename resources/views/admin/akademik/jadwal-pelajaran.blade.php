@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Jadwal Pelajaran"
    subtitle="Jadwal pelajaran per kelas dan tahun akademik"
    :ajax-url="route('admin.akademik.jadwal-pelajaran.data')"
    :columns="['Nama', 'Kelas', 'Tahun Akademik', 'Sekolah', 'Slot', 'Status', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-jadwal-modal"
                    data-form-reset="akademik-jadwal-form"
                    data-store-url="{{ route('admin.akademik.jadwal-pelajaran.store') }}"
                    data-modal-title="Tambah Jadwal">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jadwal
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
            <div>
                <label class="form-label" for="filter-kelas">Kelas</label>
                <select name="kelas_id" id="filter-kelas" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($classes as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="akademik-jadwal-modal" title="Tambah / Ubah Jadwal" size="lg">
    <form id="akademik-jadwal-form"
          data-fetch-form
          data-default-action="{{ route('admin.akademik.jadwal-pelajaran.store') }}"
          data-reload-table
          data-close-modal="akademik-jadwal-modal"
          action="{{ route('admin.akademik.jadwal-pelajaran.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Jadwal</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="akademik-jadwal-name">Nama</label>
                    <input name="name" id="akademik-jadwal-name" class="form-input" maxlength="255" placeholder="opsional">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-jadwal-tahun_akademik_id">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="akademik-jadwal-tahun_akademik_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($tahunAkademik as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-jadwal-kelas_id">Kelas</label>
                        <select name="kelas_id" id="akademik-jadwal-kelas_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($classes as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="akademik-jadwal-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="akademik-jadwal-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="akademik-jadwal-active" label="Aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-jadwal-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
