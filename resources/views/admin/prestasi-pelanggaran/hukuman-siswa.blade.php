@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
    $eligibleColumnOptions = [
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<div class="card card--datatable mb-8">
    <div class="card-divider flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="card-title">Siswa Eligible (≥{{ $hukumanMinPoints }} poin)</h2>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Hanya siswa dengan total poin pelanggaran minimal {{ $hukumanMinPoints }} yang dapat diterbitkan hukuman.</p>
        </div>
    </div>
    <div class="filter-bar">
        <form id="eligible-filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'eligible-filter-sekolah'])
            @include('admin.partials.filters.class-select', ['classes' => \App\Support\AdminSchoolScope::kelasList(), 'selected' => request('kelas_id'), 'id' => 'eligible-filter-kelas'])
            <x-filter-actions />
        </form>
    </div>
    <div class="card--datatable__body p-4 pt-0">
        <table id="eligible_table"
               class="datatable-main w-full display"
               data-ajax-url="{{ route('admin.prestasi-pelanggaran.hukuman-siswa.eligible.data') }}"
               data-column-options="{{ json_encode($eligibleColumnOptions) }}"
               data-default-order="{{ json_encode([[3, 'desc']]) }}">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th>Total Point</th>
                    <th>Syarat</th>
                    <th class="dt-col-actions">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<x-admin.datatable-page
    title="Riwayat Hukuman Siswa"
    subtitle="Catatan hukuman yang sudah diterbitkan"
    :ajax-url="route('admin.prestasi-pelanggaran.hukuman-siswa.data')"
    export-filename="Hukuman Siswa"
    :columns="['Nama Siswa', 'NIS', 'Kelas', 'Total Point', 'Rekomendasi', 'Hukuman', 'Status', 'Bukti', 'Tanggal', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :default-order="[[3, 'desc']]"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <x-siswa-select name="siswa_id" id="filter-siswa" label="Siswa" :required="false" :status="null" placeholder="Semua siswa" />
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@include('admin.partials.hukuman-siswa-form-modal', [
    'sanctionOptions' => $sanctionOptions,
    'statusOptions' => $statusOptions,
    'prefillSiswaId' => $prefillSiswaId,
    'openCreate' => $openCreate,
    'hukumanMinPoints' => $hukumanMinPoints,
])

<x-modal id="hukuman-siswa-detail-modal" title="Detail Hukuman Siswa" size="lg">
    <div class="space-y-4 text-sm">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Siswa</p>
            <p class="font-medium detail-siswa-name">-</p>
            <p class="text-slate-500 detail-siswa-sub">-</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Total Point</p>
                <p class="font-medium detail-total-point">0</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Rekomendasi</p>
                <p class="font-medium detail-recommended">-</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Hukuman Diterapkan</p>
                <p class="font-medium detail-sanction">-</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                <p class="font-medium detail-status">-</p>
            </div>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Keterangan</p>
            <p class="detail-keterangan">-</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Pelanggaran Terkait</p>
            <div class="detail-violations-list space-y-2"></div>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Bukti</p>
            <div class="detail-bukti-list"></div>
        </div>
    </div>
</x-modal>

@push('scripts')
<script src="{{ asset('js/bukti-upload.js') }}?v=1"></script>
<script>
window.hukumanRecommendUrlBase = @json(url('admin/prestasi-pelanggaran/hukuman-siswa/recommend'));
window.hukumanMinPoints = {{ (int) $hukumanMinPoints }};
</script>
<script src="{{ asset('js/hukuman-siswa.js') }}?v=5"></script>
<script>
(function () {
    document.addEventListener('edit-record-populated', function (e) {
        var record = e.detail?.record;
        var form = e.detail?.form;
        if (!record?.show_url || !form || form.id !== 'hukuman-siswa-form') return;

        fetch(record.show_url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.json(); })
            .then(function (payload) {
                if (!payload.success) return;
                if (typeof window.showExistingBukti === 'function') {
                    window.showExistingBukti('hukuman-siswa-modal', payload.data.bukti || []);
                }
            });
    });
})();
</script>
@if($openCreate && $prefillSiswaId)
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.openHukumanCreateForSiswa === 'function') {
        window.openHukumanCreateForSiswa({{ (int) $prefillSiswaId }});
    }
});
</script>
@endif
@endpush
