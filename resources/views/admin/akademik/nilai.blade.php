@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Nilai"
    subtitle="Entri nilai siswa per mapel dan semester"
    :ajax-url="route('admin.akademik.nilai.data')"
    :columns="['Siswa', 'Mapel', 'Tahun', 'Semester', 'Jenis', 'Skor', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-nilai-modal"
                    data-form-reset="akademik-nilai-form"
                    data-store-url="{{ route('admin.akademik.nilai.store') }}"
                    data-modal-title="Tambah Nilai">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Nilai
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-siswa">Siswa</label>
                <select name="siswa_id" id="filter-siswa" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($siswaOptions as $siswa)
                        <option value="{{ $siswa->id }}">{{ $siswa->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-mapel">Mapel</label>
                <select name="mata_pelajaran_id" id="filter-mapel" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($mapelOptions as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->name }}</option>
                    @endforeach
                </select>
            </div>
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
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="akademik-nilai-modal" title="Tambah / Ubah Nilai" size="lg">
    <form id="akademik-nilai-form"
          data-fetch-form
          data-default-action="{{ route('admin.akademik.nilai.store') }}"
          data-reload-table
          data-close-modal="akademik-nilai-modal"
          action="{{ route('admin.akademik.nilai.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Entri Nilai</h4>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-nilai-siswa_id">Siswa</label>
                        <select name="siswa_id" id="akademik-nilai-siswa_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($siswaOptions as $siswa)
                                <option value="{{ $siswa->id }}">{{ $siswa->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-nilai-mata_pelajaran_id">Mapel</label>
                        <select name="mata_pelajaran_id" id="akademik-nilai-mata_pelajaran_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($mapelOptions as $mapel)
                                <option value="{{ $mapel->id }}">{{ $mapel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="akademik-nilai-tahun_akademik_id">Tahun</label>
                        <select name="tahun_akademik_id" id="akademik-nilai-tahun_akademik_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($tahunAkademik as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-nilai-semester">Semester</label>
                        <select name="semester" id="akademik-nilai-semester" class="form-input" required>
                            @foreach($semesters as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-nilai-jenis">Jenis</label>
                        <select name="jenis" id="akademik-nilai-jenis" class="form-input" required>
                            @foreach($jenisLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="akademik-nilai-skor">Skor</label>
                    <input type="number" step="0.01" min="0" max="100" name="skor" id="akademik-nilai-skor" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="akademik-nilai-catatan">Catatan</label>
                    <textarea name="catatan" id="akademik-nilai-catatan" class="form-input" rows="2" maxlength="5000"></textarea>
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-nilai-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
