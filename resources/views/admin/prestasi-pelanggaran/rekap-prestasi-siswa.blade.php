@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $mainTableColumnOptions = [
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => true, 'searchable' => false, 'exportable' => true],
        ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
    ];
@endphp

<x-admin.datatable-page
    title="Rekap Prestasi Siswa"
    subtitle="Peringkat siswa berdasarkan total point prestasi (tertinggi dulu)"
    :ajax-url="route('admin.prestasi-pelanggaran.rekap-prestasi-siswa.data')"
    export-filename="Rekap Prestasi Siswa"
    :columns="['Nama Siswa', 'NIS', 'Kelas', 'Jumlah', 'Total Point', 'Aksi']"
    :column-options="$mainTableColumnOptions"
    :default-order="[[4, 'desc']]"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            @include('admin.partials.filters.class-select', ['classes' => \App\Support\AdminSchoolScope::kelasList(), 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @include('admin.partials.filters.date-range', ['id' => 'filter-tanggal'])
            <div>
                <label class="form-label" for="filter-siswa">Nama / NIS</label>
                <input type="text" name="siswa" id="filter-siswa" class="form-input" value="{{ request('siswa') }}" placeholder="Cari nama atau NIS">
            </div>
            <div>
                <label class="form-label" for="filter-min-point">Min. Total Point</label>
                <input type="number" name="min_total_point" id="filter-min-point" class="form-input" min="0" value="{{ request('min_total_point') }}" placeholder="0">
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
