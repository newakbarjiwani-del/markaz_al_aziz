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
    title="Pelanggaran Siswa"
    subtitle="Kelola pencatatan pelanggaran seluruh siswa"
    :ajax-url="route('portal.guru.pelanggaran-siswa.data')"
    export-filename="Pelanggaran Siswa"
    :columns="['Nama Siswa', 'NIS', 'Kelas', 'Judul', 'Tanggal', 'Point', 'Bukti', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :default-order="[[5, 'desc']]"
    :show-export="true">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="pelanggaran-siswa-modal"
                data-form-reset="pelanggaran-siswa-form"
                data-store-url="{{ route('portal.guru.pelanggaran-siswa.store') }}"
                data-modal-title="Tambah Pelanggaran Siswa">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <x-siswa-select
                name="siswa_id"
                id="filter-siswa"
                label="Siswa"
                :required="false"
                :status="null"
                placeholder="Semua siswa (cari min. 3 karakter)"
                :lookup-url="route('portal.guru.siswa.lookup')"
                :lookup-resolve-url="url('portal/guru/siswa/lookup')"
            />
            @include('admin.partials.filters.date-range', ['id' => 'filter-tanggal'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@include('admin.partials.prestasi-pelanggaran-form-modal', [
    'type' => 'pelanggaran',
    'context' => 'siswa',
    'katalogOptions' => $katalogOptions,
    'storeUrl' => route('portal.guru.pelanggaran-siswa.store'),
    'siswaLookupUrl' => route('portal.guru.siswa.lookup'),
    'siswaLookupResolveUrl' => url('portal/guru/siswa/lookup'),
])
@include('admin.partials.prestasi-pelanggaran-detail-modal', ['type' => 'pelanggaran', 'context' => 'siswa'])

@push('scripts')
<script src="{{ asset('js/bukti-upload.js') }}?v=1"></script>
<script src="{{ asset('js/pelanggaran-form.js') }}?v=4"></script>
<script>
(function () {
    var table = window.mainTable;

    function getShowUrl(row) {
        return '{{ route("portal.guru.pelanggaran-siswa.show", "__ID__") }}'.replace('__ID__', row.id);
    }

    function getUpdateUrl(row) {
        return '{{ route("portal.guru.pelanggaran-siswa.update", "__ID__") }}'.replace('__ID__', row.id);
    }

    function getDeleteUrl(row) {
        return '{{ route("portal.guru.pelanggaran-siswa.destroy", "__ID__") }}'.replace('__ID__', row.id);
    }

    if (table) {
        table.on('click', '.pelanggaran-detail-btn', function () {
            var row = $(this).closest('tr').data();
            if (!row) return;
            $.getJSON(getShowUrl(row), function (res) {
                if (!res.success) return;
                var d = res.data;
                var modal = $('#pelanggaran-siswa-detail-modal');
                modal.find('.detail-entity-name').text(d.siswa_name || '-');
                modal.find('.detail-entity-sub').text('NIS ' + (d.siswa_nis || '-') + ' · ' + (d.siswa_kelas || '-'));
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
                    window.renderBuktiDetailList(buktiList[0], d.bukti || [], { group: 'portal-guru-pelanggaran-' + (d.id || Date.now()) });
                } else {
                    buktiList.html('<p class="text-xs text-slate-400">Tidak ada bukti.</p>');
                }
                window.jQuery && $('#pelanggaran-siswa-detail-modal').removeClass('hidden');
            });
        });

        table.on('click', '.pelanggaran-edit-btn', function () {
            var row = $(this).closest('tr').data();
            if (!row) return;
            $.getJSON(getShowUrl(row), function (res) {
                if (!res.success) return;
                var d = res.data;
                var form = $('#pelanggaran-siswa-form');
                var modal = $('#pelanggaran-siswa-modal');
                form.attr('action', getUpdateUrl(row));
                form.find('[name="_method"]').val('PUT');
                form.find('[name="judul"]').val(d.judul);
                form.find('[name="keterangan"]').val(d.keterangan);
                form.find('[name="tanggal"]').val(d.tanggal);
                form.find('[name="point"]').val(d.point);
                if (typeof window.setPelanggaranJenisSelection === 'function') {
                    window.setPelanggaranJenisSelection(form[0], d.jenis_pelanggaran_id || '');
                } else {
                    form.find('[name="jenis_pelanggaran_id"]').val(d.jenis_pelanggaran_id || '').trigger('change');
                }
                modal.find('#pelanggaran-siswa-modal-title').text('Ubah Pelanggaran Siswa');
                if (typeof window.setAjaxSelectValue === 'function') {
                    window.setAjaxSelectValue(form.find('[name="siswa_id"]'), d.siswa_id, d.siswa_name + ' · ' + d.siswa_nis);
                }
                if (typeof window.showExistingBukti === 'function') {
                    window.showExistingBukti('pelanggaran-siswa', d.bukti);
                }
                modal.removeClass('hidden');
            });
        });

        table.on('click', '.pelanggaran-delete-btn', function () {
            var row = $(this).closest('tr').data();
            if (!row) return;
            window.showConfirm({
                title: 'Hapus Pelanggaran',
                message: 'Apakah Anda yakin ingin menghapus pelanggaran ini?',
                tone: 'danger',
                confirmText: 'Hapus',
                onConfirm: function () {
                    $.ajax({
                        url: getDeleteUrl(row),
                        type: 'POST',
                        data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                        success: function (res) {
                            window.showToast(res.message, 'success');
                            window.reloadMainTable();
                        },
                        error: function (xhr) {
                            window.showToast(xhr.responseJSON?.message || 'Gagal menghapus.', 'error');
                        }
                    });
                }
            });
        });
    }

    document.addEventListener('modalReset', function (e) {
        if (e.detail?.formId === 'pelanggaran-siswa-form') {
            var form = $('#pelanggaran-siswa-form');
            var modal = $('#pelanggaran-siswa-modal');
            form.attr('action', '{{ route("portal.guru.pelanggaran-siswa.store") }}');
            form.find('[name="_method"]').val('POST');
            modal.find('#pelanggaran-siswa-modal-title').text('Tambah Pelanggaran Siswa');
        }
    });
})();
</script>
@endpush
