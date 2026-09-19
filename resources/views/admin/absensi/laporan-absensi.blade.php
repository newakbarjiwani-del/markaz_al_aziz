@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Laporan Absensi"
    :ajax-url="$ajaxUrl ?? route('admin.absensi.laporan-absensi.data')"
    :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Status', 'Jam Masuk']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date_from', 'toId' => 'filter-date_to'])
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            <div>
                <label for="filter-status" class="form-label">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    @foreach(\App\Support\AttendanceStatus::siswaManual() as $status)
                        <option value="{{ $status }}">{{ \App\Support\AttendanceStatus::label($status) }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
