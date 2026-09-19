@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Daftar Jadwal Absen"
    subtitle="Atur jadwal pelajaran, hari aktif, guru pengampu, dan cakupan siswa"
    :ajax-url="route('admin.absensi.jadwal-absen.data')"
    :columns="['Nama Jadwal', 'Sekolah', 'Cakupan Siswa', 'Hari Aktif', 'Jumlah Slot', 'Status', 'Aksi']">
    <x-slot:actions>
        <a href="{{ route('admin.absensi.jadwal-absen.create') }}" class="btn-primary flex-1 sm:flex-none">
            <x-icon name="plus" size="sm" class="mr-1" /> Tambah Jadwal
        </a>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
        <div>
            <label for="filter-is_active" class="form-label">Status</label>
            <select name="is_active" id="filter-is_active" class="form-input">
                <option value="">Semua</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
