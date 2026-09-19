@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Kurikulum"
    subtitle="Kelola kurikulum dan mata pelajaran terkait"
    :ajax-url="route('admin.akademik.kurikulum.data')"
    :columns="['Nama', 'Tahun Akademik', 'Jenjang', 'Sekolah', 'Mapel', 'Status', 'Aksi']">
    @can('akademik.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="akademik-kurikulum-modal"
                    data-form-reset="akademik-kurikulum-form"
                    data-store-url="{{ route('admin.akademik.kurikulum.store') }}"
                    data-modal-title="Tambah Kurikulum">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Kurikulum
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
<x-modal id="akademik-kurikulum-modal" title="Tambah / Ubah Kurikulum" size="lg">
    <form id="akademik-kurikulum-form"
          data-fetch-form
          data-default-action="{{ route('admin.akademik.kurikulum.store') }}"
          data-reload-table
          data-close-modal="akademik-kurikulum-modal"
          action="{{ route('admin.akademik.kurikulum.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Kurikulum</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="akademik-kurikulum-name">Nama</label>
                    <input name="name" id="akademik-kurikulum-name" class="form-input" required maxlength="255">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="akademik-kurikulum-tahun_akademik_id">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="akademik-kurikulum-tahun_akademik_id" class="form-input" data-s2 required>
                            <option value="">—</option>
                            @foreach($tahunAkademik as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="akademik-kurikulum-jenjang">Jenjang</label>
                        <input name="jenjang" id="akademik-kurikulum-jenjang" class="form-input" maxlength="255" placeholder="mis. SMP">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="akademik-kurikulum-sekolah_id">Sekolah</label>
                    <select name="sekolah_id" id="akademik-kurikulum-sekolah_id" class="form-input" data-s2>
                        <option value="">Semua / opsional</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="akademik-kurikulum-description">Deskripsi</label>
                    <textarea name="description" id="akademik-kurikulum-description" class="form-input" rows="3" maxlength="5000"></textarea>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="akademik-kurikulum-active" label="Aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="akademik-kurikulum-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
