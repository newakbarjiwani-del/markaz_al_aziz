@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar Ujian"
    subtitle="Kelola ujian online per kelas / sekolah"
    :ajax-url="route('admin.ujian.ujian.data')"
    :columns="['Judul', 'Tahun', 'Semester', 'Kelas', 'Soal', 'Status', 'Aksi']">
    @can('ujian.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="ujian-modal"
                    data-form-reset="ujian-form"
                    data-store-url="{{ route('admin.ujian.ujian.store') }}"
                    data-modal-title="Tambah Ujian">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Ujian
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
<x-modal id="ujian-modal" title="Tambah / Ubah Ujian" size="lg">
    <form id="ujian-form"
          data-fetch-form
          data-default-action="{{ route('admin.ujian.ujian.store') }}"
          data-reload-table
          data-close-modal="ujian-modal"
          action="{{ route('admin.ujian.ujian.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Ujian</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="ujian-title">Judul</label>
                    <input name="title" id="ujian-title" class="form-input" required maxlength="255">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="ujian-tahun_akademik_id">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="ujian-tahun_akademik_id" class="form-input" data-s2 required>
                            <option value="">Pilih</option>
                            @foreach($tahunAkademik as $tahun)
                                <option value="{{ $tahun->id }}">{{ $tahun->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="ujian-semester">Semester</label>
                        <select name="semester" id="ujian-semester" class="form-input" required>
                            @foreach($semesters as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="ujian-mata_pelajaran_id">Mata Pelajaran</label>
                        <select name="mata_pelajaran_id" id="ujian-mata_pelajaran_id" class="form-input" data-s2>
                            <option value="">Opsional</option>
                            @foreach($mapel as $row)
                                <option value="{{ $row->id }}">{{ $row->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="ujian-kelas_id">Kelas</label>
                        <select name="kelas_id" id="ujian-kelas_id" class="form-input" data-s2>
                            <option value="">Semua kelas</option>
                            @foreach($kelas as $row)
                                <option value="{{ $row->id }}">{{ $row->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="ujian-guru_id">Guru</label>
                        <select name="guru_id" id="ujian-guru_id" class="form-input" data-s2>
                            <option value="">Opsional</option>
                            @foreach($guru as $row)
                                <option value="{{ $row->id }}">{{ $row->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="ujian-sekolah_id">Sekolah</label>
                        <select name="sekolah_id" id="ujian-sekolah_id" class="form-input" data-s2>
                            <option value="">Semua / opsional</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="ujian-starts_at">Mulai</label>
                        <input type="datetime-local" name="starts_at" id="ujian-starts_at" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="ujian-ends_at">Selesai</label>
                        <input type="datetime-local" name="ends_at" id="ujian-ends_at" class="form-input">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="ujian-duration_minutes">Durasi (menit)</label>
                        <input type="number" name="duration_minutes" id="ujian-duration_minutes" class="form-input" min="1" max="600">
                    </div>
                    <div>
                        <label class="form-label" for="ujian-max_attempts">Maks. percobaan</label>
                        <input type="number" name="max_attempts" id="ujian-max_attempts" class="form-input" min="1" max="10" value="1">
                    </div>
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="ujian-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush
