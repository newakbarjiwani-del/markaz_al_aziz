@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $columnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
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
    :title="$title"
    subtitle="Rekapitulasi dan laporan seluruh aktivitas perizinan siswa"
    :ajax-url="$ajaxUrl ?? route('portal.perizinan.rekap-laporan.data')"
    export-filename="Rekapitulasi Perizinan Siswa"
    :columns="['Jenis Perizinan', 'Siswa / Santri', 'Alasan', 'Pemberi Izin', 'Waktu Berangkat / Mulai', 'Batas Kembali', 'Kembali Aktual', 'Status', 'Aksi']"
    :column-options="$columnOptions"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @if(!isset($children))
                @include('admin.partials.filters.student-search', ['id' => 'filter-q', 'value' => request('q')])
            @endif
            <div class="filter-item">
                <label class="form-label text-xs" for="filter-jenis">Jenis Perizinan</label>
                <select name="jenis_perizinan" id="filter-jenis" class="form-select text-xs">
                    <option value="">Semua Jenis</option>
                    <option value="keluar_masuk">Izin Keluar Masuk Harian</option>
                    <option value="keluar_masuk_pondok">Izin Keluar Masuk Pondok</option>
                    <option value="pulang_libur">Izin Pulang Libur</option>
                </select>
            </div>
            @if(!empty($schools))
                @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
                @include('admin.partials.filters.class-select', ['classes' => \App\Support\AdminSchoolScope::kelasList(), 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @endif
            @if(isset($children) && count($children) > 1)
                <div class="filter-item">
                    <label class="form-label text-xs" for="filter-siswa">Pilih Anak</label>
                    <select name="siswa_id" id="filter-siswa" class="form-select text-xs">
                        <option value="">Semua Anak</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}">{{ $child->name }} ({{ $child->kelas?->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
            @endif
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

@include('admin.perizinan.partials.detail-modal')

@push('scripts')
<script src="{{ asset('js/perizinan.js') }}?v=2"></script>
@endpush
@endsection
