@extends('layouts.app')
@section('title', $title)
@section('content')
<x-admin.datatable-page
    title="Absensi Siswa"
    :ajax-url="route('admin.absensi.absensi-siswa.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'Jadwal', 'Pelajaran', 'Tanggal', 'Status', 'Jam & Metode']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            <div>
                <label for="filter-jadwal_absen" class="form-label">Jadwal Absen</label>
                <select name="jadwal_absen_id" id="filter-jadwal_absen" class="form-input" data-s2>
                    <option value="">Semua Jadwal</option>
                    @foreach($jadwals as $jadwal)
                        <option value="{{ $jadwal->id }}"
                                data-sekolah-id="{{ $jadwal->sekolah_id }}"
                                @selected((string) request('jadwal_absen_id') === (string) $jadwal->id)>
                            {{ $jadwal->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-pelajaran" class="form-label">Mata Pelajaran</label>
                <select name="pelajaran_id" id="filter-pelajaran" class="form-input" data-s2>
                    <option value="">Semua Pelajaran</option>
                    @foreach($pelajarans as $pelajaran)
                        <option value="{{ $pelajaran->id }}"
                                data-sekolah-id="{{ $pelajaran->sekolah_id }}"
                                @selected((string) request('pelajaran_id') === (string) $pelajaran->id)>
                            {{ $pelajaran->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-status" class="form-label">Status Kehadiran</label>
                <select name="status" id="filter-status" class="form-input" data-s2>
                    <option value="">Semua Status</option>
                    @foreach(\App\Support\AttendanceStatus::siswaManual() as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>
                            {{ \App\Support\AttendanceStatus::label($st) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-method" class="form-label">Metode</label>
                <select name="method" id="filter-method" class="form-input" data-s2>
                    <option value="">Semua Metode</option>
                    <option value="rfid" @selected(request('method') === 'rfid')>RFID</option>
                    <option value="face" @selected(request('method') === 'face')>Face Recognition</option>
                    <option value="manual" @selected(request('method') === 'manual')>Manual</option>
                    <option value="qr" @selected(request('method') === 'qr')>QR Code</option>
                </select>
            </div>
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date_from', 'toId' => 'filter-date_to'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('scripts')
<script>
(function () {
    var sekolahFilter = document.querySelector('[data-filter-sekolah]');
    var kelasFilter = document.querySelector('#filter-form [name="kelas_id"]');
    var jadwalFilter = document.querySelector('#filter-form [name="jadwal_absen_id"]');
    var pelajaranFilter = document.querySelector('#filter-form [name="pelajaran_id"]');

    function filterOptionsBySekolah(selectEl, sekolahId) {
        if (!selectEl) return;
        var selectedVal = selectEl.value;
        var hasVisibleSelection = false;

        Array.from(selectEl.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            var matches = !sekolahId || !option.dataset.sekolahId || option.dataset.sekolahId === sekolahId;
            option.hidden = !matches;
            if (matches && option.value === selectedVal) {
                hasVisibleSelection = true;
            }
        });

        if (selectedVal && !hasVisibleSelection) {
            selectEl.value = '';
        }
    }

    function syncFilters() {
        if (!sekolahFilter) return;
        var sekolahId = sekolahFilter.value;

        [kelasFilter, jadwalFilter, pelajaranFilter].forEach(function (el) {
            if (!el) return;
            if (window.jQuery) {
                var $el = jQuery(el);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
            }
            filterOptionsBySekolah(el, sekolahId);
        });

        window.initOfflineSelect2s?.(document.querySelector('#filter-form') || document);
    }

    if (sekolahFilter) {
        sekolahFilter.addEventListener('change', syncFilters);
        syncFilters();
    }
})();
</script>
@endpush

