@extends('layouts.app')
@section('title', $title)
@section('content')
<x-admin.datatable-page
    title="Absensi Guru"
    :ajax-url="$ajaxUrl ?? route('admin.absensi.absensi-guru.data')"
    :columns="['NIP', 'Nama', 'Tanggal', 'Status Masuk', 'Jam Masuk', 'Jam Keluar', 'Status Pulang']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to')])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
