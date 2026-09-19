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
    title="Pelanggaran Saya"
    subtitle="Daftar pelanggaran yang tercatat"
    :ajax-url="route('portal.siswa.pelanggaran-siswa.data')"
    export-filename="Pelanggaran Saya"
    :columns="['Judul', 'Tanggal', 'Point', 'Bukti', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.date-filter')
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@include('admin.partials.prestasi-pelanggaran-detail-modal', ['type' => 'pelanggaran', 'context' => 'siswa'])

@push('scripts')
<script>
(function () {
    var table = window.mainTable;

    function getShowUrl(row) {
        return '{{ route("portal.siswa.pelanggaran-siswa.show", "__ID__") }}'.replace('__ID__', row.id);
    }

    if (table) {
        table.on('click', '.pelanggaran-detail-btn', function () {
            var row = $(this).closest('tr').data();
            if (!row) return;
            $.getJSON(getShowUrl(row), function (res) {
                if (!res.success) return;
                var d = res.data;
                var modal = $('#pelanggaran-siswa-detail-modal');
                modal.find('.detail-entity-name').text('{{ $siswa->name }}');
                modal.find('.detail-entity-sub').text('NIS {{ $siswa->nis }} · {{ $siswa->kelas?->name ?? "-" }}');
                if (d.jenis_nama) {
                    modal.find('#pelanggaran-siswa-detail-modal-jenis-filled').removeClass('hidden');
                    modal.find('#pelanggaran-siswa-detail-modal-jenis-empty').addClass('hidden');
                    modal.find('.detail-jenis').text(d.jenis_nama);
                    modal.find('.detail-jenis-level').text(d.jenis_level || '-');
                    modal.find('.detail-jenis-sanction').text(d.jenis_sanction || '-');
                    modal.find('.detail-jenis-point').text(d.jenis_point ?? 0);
                } else {
                    modal.find('#pelanggaran-siswa-detail-modal-jenis-filled').addClass('hidden');
                    modal.find('#pelanggaran-siswa-detail-modal-jenis-empty').removeClass('hidden');
                }
                modal.find('.detail-judul').text(d.judul || '-');
                modal.find('.detail-keterangan').text(d.keterangan || '-');
                modal.find('.detail-tanggal').text(d.tanggal || '-');
                modal.find('.detail-point').text(d.point || 0);
                modal.find('.detail-reported-by').text(d.reported_by_name || '-');
                var buktiList = modal.find('.detail-bukti-list');
                buktiList.empty();
                if (typeof window.renderBuktiDetailList === 'function') {
                    window.renderBuktiDetailList(buktiList[0], d.bukti || [], { group: 'portal-siswa-pelanggaran-' + (d.id || Date.now()) });
                } else {
                    buktiList.html('<p class="text-xs text-slate-400">Tidak ada bukti.</p>');
                }
                window.jQuery && $('#pelanggaran-siswa-detail-modal').removeClass('hidden');
            });
        });
    }
})();
</script>
@endpush
