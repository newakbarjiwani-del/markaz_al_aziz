@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Riwayat Akademik"
    :ajax-url="route('admin.manajemen-siswa.riwayat-akademik.data')"
    :columns="['NIS', 'Nama', 'Tahun Akademik', 'Kelas', 'IPK']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
