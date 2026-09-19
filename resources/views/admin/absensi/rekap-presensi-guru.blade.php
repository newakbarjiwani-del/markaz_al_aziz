@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Rekap Presensi Guru"
    :ajax-url="$ajaxUrl ?? route('admin.absensi.rekap-presensi-guru.data')"
    :columns="['NIP', 'Nama', 'Sekolah', 'Jabatan', 'Hadir', 'Terlambat', 'Izin', 'Sakit', 'Cuti', 'Alpha', 'Hari Libur']"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            <div>
                <label class="form-label">Bulan</label>
                <input type="month" name="month" id="filter-month" value="{{ now()->format('Y-m') }}" class="form-input">
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
