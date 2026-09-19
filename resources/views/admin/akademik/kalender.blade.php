@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Kalender Pendidikan"
    subtitle="Hari efektif, libur, ujian, dan kegiatan"
    :ajax-url="route('admin.akademik.kalender.data')"
    :columns="['Nama', 'Jenis', 'Mulai', 'Selesai', 'Tahun Akademik', 'Sekolah', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-kalender-modal"
                    data-form-reset="akademik-kalender-form"
                    data-store-url="{{ route('admin.akademik.kalender.store') }}"
                    data-modal-title="Tambah Entri Kalender">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-jenis">Jenis</label>
                <select name="jenis" id="filter-jenis" class="form-input">
                    <option value="">Semua</option>
                    @foreach($jenisLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-tahun">Tahun Akademik</label>
                <select name="tahun_akademik_id" id="filter-tahun" class="form-input" data-s2>
                    <option value="">Semua</option>
                    @foreach($tahunAkademik as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="akademik-kalender-modal" title="Tambah / Ubah Kalender" size="lg">
    <form id="akademik-kalender-form"
          data-fetch-form
          data-default-action="{{ route('admin.akademik.kalender.store') }}"
          data-reload-table
          data-close-modal="akademik-kalender-modal"
          action="{{ route('admin.akademik.kalender.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Entri Kalender</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="akademik-kalender-name">Nama</label>
                    <input name="name" id="akademik-kalender-name" class="form-input" required maxlength="255">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-kalender-jenis">Jenis</label>
                        <select name="jenis" id="akademik-kalender-jenis" class="form-input" required>
                            @foreach($jenisLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-kalender-tahun_akademik_id">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="akademik-kalender-tahun_akademik_id" class="form-input" data-s2>
                            <option value="">—</option>
                            @foreach($tahunAkademik as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-kalender-starts_on">Mulai</label>
                        <input type="date" name="starts_on" id="akademik-kalender-starts_on" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-kalender-ends_on">Selesai</label>
                        <input type="date" name="ends_on" id="akademik-kalender-ends_on" class="form-input" required>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="akademik-kalender-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="akademik-kalender-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="akademik-kalender-notes">Catatan</label>
                    <textarea name="notes" id="akademik-kalender-notes" class="form-input" rows="3" maxlength="5000"></textarea>
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-kalender-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
