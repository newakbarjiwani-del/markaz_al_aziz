@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.master-data.partials.nav', ['active' => 'kelas'])

<x-admin.datatable-page
    title="Daftar Kelas"
    subtitle="Kelola kelas per sekolah"
    :ajax-url="route('admin.master-data.kelas.data')"
    :columns="['Sekolah', 'Unit', 'Kelas', 'Kelompok', 'Wali Kelas', 'Status', 'Aksi']">
    @can('master_data.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="kelas-modal"
                    data-form-reset="kelas-form"
                    data-store-url="{{ route('admin.master-data.kelas.store') }}"
                    data-modal-title="Tambah Kelas">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Kelas
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
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
<x-modal id="kelas-modal" title="Tambah / Ubah Kelas">
    <form id="kelas-form"
          data-fetch-form
          data-default-action="{{ route('admin.master-data.kelas.store') }}"
          data-reload-table
          data-close-modal="kelas-modal"
          action="{{ route('admin.master-data.kelas.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Kelas</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="kelas-sekolah">Sekolah</label>
                    <select name="sekolah_id" id="kelas-sekolah" class="form-input" required data-s2>
                        <option value="">Pilih sekolah</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="kelas-unit">Unit</label>
                    <input name="unit" id="kelas-unit" class="form-input" placeholder="mis. MA">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="kelas-level">Kelas</label>
                        <input name="kelas" id="kelas-level" class="form-input" placeholder="mis. IX, Tsaniyah" required>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tingkat kelas (angka, romawi, atau nama seperti Tsaniyah)</p>
                    </div>
                    <div>
                        <label class="form-label" for="kelas-kelompok">Kelompok</label>
                        <input name="kelompok" id="kelas-kelompok" class="form-input" placeholder="mis. B" required>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Huruf atau nama kelompok</p>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="kelas-name-preview">Nama Kelas</label>
                    <input id="kelas-name-preview" class="form-input bg-slate-50 dark:bg-slate-800/60" readonly placeholder="Pratinjau: 1B">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Otomatis dari Kelas + Kelompok (contoh: IX + 01-IBNU HAJAR, Tsaniyah + I)</p>
                </div>
                <div>
                    <label class="form-label" for="kelas-wali-kelas">Wali Kelas</label>
                    <input name="wali_kelas" id="kelas-wali-kelas" class="form-input">
                </div>
                <div>
                    <x-form.checkbox name="is_active" id="kelas-active" label="Kelas aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="kelas-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
(function () {
    const levelInput = document.getElementById('kelas-level');
    const kelompokInput = document.getElementById('kelas-kelompok');
    const previewInput = document.getElementById('kelas-name-preview');

    if (!levelInput || !kelompokInput || !previewInput) {
        return;
    }

    function buildShortLabel(level, kelompok) {
        if (level === '') {
            return kelompok;
        }

        if (kelompok === '') {
            return level;
        }

        if (/^\d+$/.test(level) && /^[A-Za-z]$/.test(kelompok)) {
            return level + kelompok;
        }

        return level + ' ' + kelompok;
    }

    function syncPreview() {
        const level = levelInput.value.trim();
        const kelompok = kelompokInput.value.trim();
        const parts = [];

        if (level !== '') {
            parts.push('Kelas ' + level);
        }

        if (kelompok !== '') {
            parts.push('Kelompok ' + kelompok);
        }

        const longLabel = parts.join(', ');
        const shortLabel = buildShortLabel(level, kelompok);

        previewInput.value = shortLabel;
        previewInput.title = longLabel || shortLabel;
    }

    levelInput.addEventListener('input', syncPreview);
    kelompokInput.addEventListener('input', syncPreview);
    document.getElementById('kelas-form')?.addEventListener('reset', function () {
        window.setTimeout(syncPreview, 0);
    });
    document.addEventListener('datatable:edit-record', function (event) {
        if (event.detail?.formTarget !== 'kelas-form') {
            return;
        }

        window.setTimeout(syncPreview, 0);
    });
    syncPreview();
})();
</script>
@endpush
