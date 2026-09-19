@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $statCards = [
        'total' => ['Total Siswa', 'blue'],
        'aktif' => ['Siswa Aktif', 'green'],
        'pending' => ['Menunggu', 'amber'],
        'nonaktif' => ['Nonaktif', 'red'],
    ];
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => false, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true, 'html' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp
{{-- Stat cards --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" id="stats-cards">
    @foreach($statCards as $key => [$statLabel, $accent])
        <div class="stat-card stat-card-{{ $accent }}">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $statLabel }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white" data-stat="{{ $key }}">-</p>
        </div>
    @endforeach
</div>

<x-admin.datatable-page
    title="Data Siswa"
    subtitle="Kelola data siswa sekolah"
    :ajax-url="route('admin.manajemen-siswa.data-siswa.data')"
    export-filename="Data Siswa"
    :columns="['NIS', 'No. VA', 'Nama', 'Kelas', 'Gender', 'Status', 'RFID', 'Rekam Wajah', 'Token Login', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :show-export="true">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="student-modal"
                data-form-reset="student-form"
                data-store-url="{{ route('admin.manajemen-siswa.data-siswa.store') }}"
                data-modal-title="Tambah Siswa">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Siswa
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            @include('admin.partials.filters.student-status-select', ['selected' => request('status')])
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @include('admin.partials.filters.gender-select', ['selected' => request('gender')])
            @include('admin.partials.filters.face-capture-select', ['selected' => request('has_foto_wajah')])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
@include('admin.manajemen-siswa.partials.student-form-modal', [
    'classes' => $classes,
    'kamarList' => $kamarList,
    'statusSantriList' => $statusSantriList,
])
@include('admin.manajemen-siswa.partials.siswa-orang-tua-modal')
<x-face-capture-modal />
@endpush

@push('scripts')
<script src="{{ asset('js/row-action-menu.js') }}?v=2"></script>
<script src="{{ asset('js/portal-access.js') }}?v=7"></script>
<script src="{{ asset('js/face-capture.js') }}?v=4"></script>
<script src="{{ asset('js/siswa-orang-tua.js') }}?v=3"></script>
@include('admin.manajemen-siswa.partials.student-form-scripts')
<script>
(function () {
    var sekolahFilter = document.querySelector('[data-filter-sekolah]');
    var kelasFilter = document.querySelector('#filter-form [name="kelas_id"]');

    function syncKelasFilterOptions() {
        if (!sekolahFilter || !kelasFilter) return;

        if (window.jQuery) {
            var $kelas = jQuery(kelasFilter);
            if ($kelas.data('select2')) {
                $kelas.select2('destroy');
            }
        }

        var sekolahId = sekolahFilter.value;
        var selectedKelas = kelasFilter.value;
        var hasVisibleSelection = false;

        Array.from(kelasFilter.options).forEach(function (option, index) {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            var matches = !sekolahId || option.dataset.sekolahId === sekolahId;
            option.hidden = !matches;

            if (matches && option.value === selectedKelas) {
                hasVisibleSelection = true;
            }
        });

        if (selectedKelas && !hasVisibleSelection) {
            kelasFilter.value = '';
        }

        window.initOfflineSelect2s?.(kelasFilter.closest('#filter-form') || document);
    }

    sekolahFilter?.addEventListener('change', syncKelasFilterOptions);
    syncKelasFilterOptions();
})();

fetch(@json(route('admin.manajemen-siswa.data-siswa.stats')))
    .then(function (r) { return r.json(); })
    .then(function (res) {
        if (res.data) {
            Object.entries(res.data).forEach(function ([key, val]) {
                const el = document.querySelector('[data-stat="' + key + '"]');
                if (el) el.textContent = val;
            });
        }
    });

window.initFaceCapture({
    csrfToken: @json(csrf_token()),
    storeUrl: @json(route('admin.manajemen-siswa.data-siswa.rekam-wajah.store', ['siswa' => '__SISWA__'])),
    deleteUrl: @json(route('admin.manajemen-siswa.data-siswa.rekam-wajah.destroy', ['siswa' => '__SISWA__'])),
    fotoUrl: @json(route('admin.manajemen-siswa.data-siswa.foto-wajah', ['siswa' => '__SISWA__'])),
    openTriggerSelector: '[data-open-face-capture]',
    onSaved: function () {
        window.reloadMainTable?.();
    },
    onDeleted: function () {
        window.reloadMainTable?.();
    },
});
</script>
@endpush
