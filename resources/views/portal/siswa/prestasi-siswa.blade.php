@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Prestasi Saya"
    subtitle="Daftar prestasi yang tercatat"
    :ajax-url="route('portal.siswa.prestasi-siswa.data')"
    export-filename="Prestasi Saya"
    :columns="['Judul', 'Tanggal', 'Point', 'Bukti', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.date-filter')
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@include('admin.partials.prestasi-pelanggaran-detail-modal', ['type' => 'prestasi', 'context' => 'siswa'])

@push('scripts')
<script>
(function () {
    var table = window.mainTable;

    function getShowUrl(row) {
        return '{{ route("portal.siswa.prestasi-siswa.show", "__ID__") }}'.replace('__ID__', row.id);
    }

    if (table) {
        table.on('click', '.prestasi-detail-btn', function () {
            var row = $(this).closest('tr').data();
            if (!row) return;
            $.getJSON(getShowUrl(row), function (res) {
                if (!res.success) return;
                var d = res.data;
                var modal = $('#prestasi-siswa-detail-modal');
                modal.find('.detail-entity-name').text('{{ $siswa->name }}');
                modal.find('.detail-entity-sub').text('NIS {{ $siswa->nis }} · {{ $siswa->kelas?->name ?? "-" }}');
                modal.find('.detail-judul').text(d.judul || '-');
                modal.find('.detail-keterangan').text(d.keterangan || '-');
                modal.find('.detail-tanggal').text(d.tanggal || '-');
                modal.find('.detail-point').text(d.point || 0);
                modal.find('.detail-reported-by').text(d.reported_by_name || '-');
                var buktiList = modal.find('.detail-bukti-list');
                buktiList.empty();
                if (typeof window.renderBuktiDetailList === 'function') {
                    window.renderBuktiDetailList(buktiList[0], d.bukti || [], { group: 'portal-siswa-prestasi-' + (d.id || Date.now()) });
                } else {
                    buktiList.html('<p class="text-xs text-slate-400">Tidak ada bukti.</p>');
                }
                window.jQuery && $('#prestasi-siswa-detail-modal').removeClass('hidden');
            });
        });
    }
})();
</script>
@endpush
