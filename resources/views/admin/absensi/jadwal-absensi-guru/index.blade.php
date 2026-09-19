@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar Jadwal"
    subtitle="Buat jadwal jam kerja guru, lalu tugaskan guru yang menggunakan jadwal tersebut."
    :ajax-url="route('admin.absensi.jadwal-absensi-guru.data')"
    :columns="['Sekolah', 'Nama Jadwal', 'Jam Masuk', 'Jam Pulang', 'Toleransi', 'Jumlah Guru', 'Status', 'Aksi']">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="jadwal-absensi-guru-modal"
                data-form-reset="jadwal-absensi-guru-form"
                data-store-url="{{ route('admin.absensi.jadwal-absensi-guru.store') }}"
                data-modal-title="Tambah Jadwal Absensi Guru">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jadwal
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
        <div>
            <label class="form-label">Status</label>
            <select name="is_active" id="filter-is_active" class="form-input">
                <option value="">Semua</option>
                <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="jadwal-absensi-guru-modal" title="Tambah / Ubah Jadwal Absensi Guru">
    <form id="jadwal-absensi-guru-form"
          data-fetch-form
          data-default-action="{{ route('admin.absensi.jadwal-absensi-guru.store') }}"
          data-reload-table
          data-close-modal="jadwal-absensi-guru-modal"
          action="{{ route('admin.absensi.jadwal-absensi-guru.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Jadwal</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label for="jadwal-absensi-guru-sekolah_id" class="form-label">Sekolah</label>
                    <select name="sekolah_id" id="jadwal-absensi-guru-sekolah_id" class="form-input" required data-s2>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="jadwal-absensi-guru-name" class="form-label">Nama Jadwal</label>
                    <input name="name" id="jadwal-absensi-guru-name" class="form-input" placeholder="Mis. Reguler Pagi" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="jadwal-absensi-guru-jam_masuk" class="form-label">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="jadwal-absensi-guru-jam_masuk" class="form-input" value="07:00" required>
                    </div>
                    <div>
                        <label for="jadwal-absensi-guru-jam_pulang" class="form-label">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="jadwal-absensi-guru-jam_pulang" class="form-input" value="15:00" required>
                    </div>
                </div>
                <div>
                    <label for="jadwal-absensi-guru-toleransi_menit" class="form-label">Toleransi Waktu (menit)</label>
                    <input type="number" name="toleransi_menit" id="jadwal-absensi-guru-toleransi_menit" class="form-input" value="15" min="0" max="180" required>
                    <p class="mt-1 text-xs text-slate-500">Batas keterlambatan setelah jam masuk.</p>
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="jadwal-absensi-guru-is_active" label="Jadwal aktif" :checked="true" />
                </div>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="jadwal-absensi-guru-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="jadwal-absensi-guru-assign-modal" title="Atur Penugasan Guru">
    <form id="jadwal-absensi-guru-assign-form"
          data-fetch-form
          data-reset-on-success="false"
          data-reload-table
          data-close-modal="jadwal-absensi-guru-assign-modal"
          action="#"
          method="POST"
          class="space-y-5">
        @csrf
        @method('PUT')
        <div id="jadwal-absensi-guru-assign-hidden-inputs" hidden></div>

        <section class="form-section">
            <h4 class="form-section__title">Ringkasan Jadwal</h4>
            <div class="form-section__body space-y-4">
                <div id="jadwal-absensi-guru-assign-meta" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-900/50 dark:text-slate-300"></div>

                <div id="jadwal-absensi-guru-assign-selected" class="hidden rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-900/40 dark:bg-primary-950/30">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">
                            Guru terpilih (<span id="jadwal-absensi-guru-assign-selected-count">0</span>)
                        </p>
                    </div>
                    <div id="jadwal-absensi-guru-assign-selected-list" class="flex max-h-24 flex-wrap gap-1.5 overflow-y-auto"></div>
                    <p id="jadwal-absensi-guru-assign-selected-empty" class="text-sm text-slate-500">Belum ada guru dipilih.</p>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pilih Guru</h4>
            <div class="form-section__body space-y-4">
                <div class="space-y-2">
                    <label class="form-label" for="jadwal-absensi-guru-assign-search">Cari Guru</label>
                    <input type="search"
                           id="jadwal-absensi-guru-assign-search"
                           class="form-input"
                           placeholder="Cari nama, NIP, atau jabatan..."
                           autocomplete="off">
                </div>

                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">
                    <input type="checkbox" id="jadwal-absensi-guru-assign-select-all" class="rounded border-slate-300">
                    <span>Pilih semua <span id="jadwal-absensi-guru-assign-visible-count" class="font-normal text-slate-500"></span></span>
                </label>

                <div id="jadwal-absensi-guru-assign-list" class="max-h-72 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3 dark:border-slate-700"></div>
                <p id="jadwal-absensi-guru-assign-filter-empty" class="hidden text-sm text-slate-500">Tidak ada guru yang cocok dengan pencarian.</p>
                <p id="jadwal-absensi-guru-assign-empty" class="hidden text-sm text-slate-500">Belum ada guru di sekolah ini.</p>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="jadwal-absensi-guru-assign-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Penugasan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/jadwal-absensi-guru.js') }}?v=2"></script>
@endpush
