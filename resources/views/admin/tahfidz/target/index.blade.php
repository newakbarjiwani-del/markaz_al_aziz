@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Target Hafalan"
    subtitle="Target harian/mingguan per siswa"
    :ajax-url="route('admin.tahfidz.target.data')"
    :columns="['Siswa', 'Target', 'Periode', 'Jatuh tempo', 'Aksi']">
    @can('tahfidz.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="tahfidz-target-modal"
                    data-form-reset="tahfidz-target-form"
                    data-store-url="{{ route('admin.tahfidz.target.store') }}"
                    data-modal-title="Tambah Target">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Target
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-period">Periode</label>
                <select name="period" id="filter-period" class="form-input">
                    <option value="">Semua</option>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="tahfidz-target-modal" title="Tambah / Ubah Target" size="lg">
    <form id="tahfidz-target-form"
          data-fetch-form
          data-default-action="{{ route('admin.tahfidz.target.store') }}"
          data-reload-table
          data-close-modal="tahfidz-target-modal"
          action="{{ route('admin.tahfidz.target.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Target</h4>
            <div class="form-section__body space-y-4">
                <x-siswa-select name="siswa_id" id="target-siswa_id" />
                <div>
                    <label class="form-label" for="target-range_type">Jenis rentang</label>
                    <select name="range_type" id="target-range_type" class="form-input" required>
                        <option value="ayat">Surah / ayat</option>
                        <option value="juz">Juz</option>
                    </select>
                </div>
                <div id="target-juz-wrap" class="hidden">
                    <label class="form-label" for="target-juz">Juz</label>
                    <input type="number" name="juz" id="target-juz" class="form-input" min="1" max="30">
                </div>
                <div id="target-ayat-wrap" class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label class="form-label" for="target-surah_id">Surah</label>
                        <select name="surah_id" id="target-surah_id" class="form-input" data-s2>
                            <option value="">Pilih</option>
                            @foreach($surahs as $surah)
                                <option value="{{ $surah->id }}">{{ $surah->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="target-ayah_from">Ayat dari</label>
                        <input type="number" name="ayah_from" id="target-ayah_from" class="form-input" min="1">
                    </div>
                    <div>
                        <label class="form-label" for="target-ayah_to">Ayat sampai</label>
                        <input type="number" name="ayah_to" id="target-ayah_to" class="form-input" min="1">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="target-period">Periode</label>
                        <select name="period" id="target-period" class="form-input" required>
                            <option value="daily">Harian</option>
                            <option value="weekly">Mingguan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="target-due_date">Jatuh tempo</label>
                        <input type="date" name="due_date" id="target-due_date" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="target-note">Catatan</label>
                    <textarea name="note" id="target-note" class="form-input" rows="2"></textarea>
                </div>
            </div>
        </section>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-close-modal="tahfidz-target-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script>
(() => {
    const type = document.getElementById('target-range_type');
    const juzWrap = document.getElementById('target-juz-wrap');
    const ayatWrap = document.getElementById('target-ayat-wrap');
    const sync = () => {
        const isJuz = type?.value === 'juz';
        juzWrap?.classList.toggle('hidden', !isJuz);
        ayatWrap?.classList.toggle('hidden', isJuz);
    };
    type?.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
