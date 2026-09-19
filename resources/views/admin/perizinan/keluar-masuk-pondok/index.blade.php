@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $columnOptions = [
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => true, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true, 'html' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Izin Keluar Masuk Pondok (Asrama)"
    subtitle="Perizinan santri mukim meninggalkan kompleks / area lingkungan pondok pesantren"
    :ajax-url="$ajaxUrl ?? route('portal.perizinan.keluar-masuk-pondok.data')"
    export-filename="Izin Keluar Masuk Pondok"
    :columns="['Siswa / Santri', 'Alasan', 'Pemberi Izin', 'Waktu Keluar Pondok', 'Batas Waktu Kembali', 'Kembali Aktual', 'Status', 'Aksi']"
    :column-options="$columnOptions"
    :show-export="true">
    <x-slot:actions>
        <button type="button" class="btn-primary flex-1 sm:flex-none"
                data-open-modal="perizinan-modal"
                data-form-reset="perizinan-form"
                data-store-url="{{ $storeUrl ?? route('portal.perizinan.keluar-masuk-pondok.store') }}"
                data-modal-title="Tambah Izin Keluar Masuk Pondok">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Izin Pondok
        </button>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.student-search', ['id' => 'filter-q', 'value' => request('q')])
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            @include('admin.partials.filters.class-select', ['classes' => \App\Support\AdminSchoolScope::kelasList(), 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            <div class="filter-item">
                <label class="form-label text-xs" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-select text-xs">
                    <option value="">Semua Status</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="pending">Menunggu</option>
                    <option value="kembali">Sudah Kembali</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>
            @include('admin.partials.filters.date-range', ['id' => 'filter-tanggal'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

@include('admin.perizinan.partials.form-modal', ['jenis' => \App\Models\Perizinan::JENIS_KELUAR_MASUK_PONDOK])
@include('admin.perizinan.partials.checkin-late-modal', ['katalogOptions' => $katalogPelanggaran ?? []])
@include('admin.perizinan.partials.detail-modal')

@push('scripts')
<script src="{{ asset('js/pelanggaran-form.js') }}?v=2"></script>
<script src="{{ asset('js/perizinan.js') }}?v=3"></script>
@endpush
@endsection
