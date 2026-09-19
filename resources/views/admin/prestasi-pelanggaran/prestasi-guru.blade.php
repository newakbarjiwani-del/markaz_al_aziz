@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Prestasi Guru"
    subtitle="Kelola pencatatan prestasi guru"
    :ajax-url="route('admin.prestasi-pelanggaran.prestasi-guru.data')"
    export-filename="Prestasi Guru"
    :columns="['Nama Guru', 'NIP', 'Jabatan', 'Judul', 'Tanggal', 'Point', 'Bukti', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :show-export="true">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="prestasi-guru-modal"
                data-form-reset="prestasi-guru-form"
                data-store-url="{{ route('admin.prestasi-pelanggaran.prestasi-guru.store') }}"
                data-modal-title="Tambah Prestasi Guru">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            @include('admin.partials.filters.date-range', ['id' => 'filter-tanggal'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@include('admin.partials.prestasi-pelanggaran-form-modal', ['type' => 'prestasi', 'context' => 'guru', 'katalogOptions' => $katalogOptions ?? []])
@include('admin.partials.prestasi-pelanggaran-detail-modal', ['type' => 'prestasi', 'context' => 'guru'])

@push('scripts')
<script src="{{ asset('js/bukti-upload.js') }}?v=1"></script>
<script src="{{ asset('js/prestasi-form.js') }}?v=1"></script>
<script>
(function () {
    document.addEventListener('edit-record-populated', function (e) {
        var record = e.detail?.record;
        var form = e.detail?.form;
        if (!record?.show_url || !form) return;

        $.getJSON(record.show_url, function (res) {
            if (!res.success) return;
            var d = res.data;
            if (typeof window.showExistingBukti === 'function') {
                window.showExistingBukti('prestasi-guru-modal', d.bukti);
            }
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-view-url]');
        if (!btn) return;
        e.preventDefault();

        $.getJSON(btn.dataset.viewUrl, function (res) {
            if (!res.success) return;
            var d = res.data;
            var modal = $('#' + btn.dataset.viewModal);
            modal.find('.detail-entity-name').text(d.guru_name || '-');
            modal.find('.detail-entity-sub').text('NIP ' + (d.guru_nip || '-') + ' · ' + (d.guru_jabatan || '-'));
            modal.find('.detail-judul').text(d.judul || '-');
            modal.find('.detail-keterangan').text(d.keterangan || '-');
            modal.find('.detail-tanggal').text(d.tanggal || '-');
            modal.find('.detail-point').text(d.point || 0);
            modal.find('.detail-reported-by').text(d.reported_by_name || '-');
            var buktiList = modal.find('.detail-bukti-list');
            buktiList.empty();
            if (typeof window.renderBuktiDetailList === 'function') {
                window.renderBuktiDetailList(buktiList[0], d.bukti || [], { group: 'prestasi-guru-' + (d.id || Date.now()) });
            } else {
                buktiList.html('<p class="text-xs text-slate-400">Tidak ada bukti.</p>');
            }
            modal.removeClass('hidden');
        });
    });

    document.addEventListener('modalReset', function (e) {
        if (e.detail?.formId === 'prestasi-guru-form') {
            var form = document.getElementById('prestasi-guru-form');
            form.action = '{{ route("admin.prestasi-pelanggaran.prestasi-guru.store") }}';
            form.dataset.method = 'POST';
        }
    });
})();
</script>
@endpush
