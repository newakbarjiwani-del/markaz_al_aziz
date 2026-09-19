@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Berkas Siswa"
    :ajax-url="route('admin.manajemen-siswa.berkas-siswa.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'Judul Berkas', 'Tipe', 'Tanggal Upload']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date-from', 'toId' => 'filter-date-to'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
