@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Profil Siswa"
    :ajax-url="route('admin.manajemen-siswa.profil-siswa.data')"
    :columns="array_filter([
        'NIS', 'Nama', 'Kelas', 'Kamar', 'Status Santri', 'Foto', 'Nama Panggilan', 'Gol. Darah',
        auth()->user()?->can('students.update') ? 'Aksi' : null,
    ])"
    :column-options="auth()->user()?->can('students.update')
        ? [8 => ['html' => true]]
        : []">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.student-search', ['id' => 'filter-q', 'value' => request('q')])
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @include('admin.partials.filters.student-status-select', ['selected' => request('status'), 'id' => 'filter-status'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@can('students.update')
@push('modals')
@include('admin.manajemen-siswa.partials.profil-siswa-form-modal')
@endpush
@endcan
