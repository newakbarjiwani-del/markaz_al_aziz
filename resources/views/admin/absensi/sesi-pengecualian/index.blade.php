@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Pengecualian Sesi Absensi"
    subtitle="Sesi yang ditandai tidak wajib absen tidak dihitung alpha di rekap presensi."
    :ajax-url="route('admin.absensi.sesi-pengecualian.data')"
    :columns="['Tanggal', 'Sekolah', 'Jadwal', 'Pelajaran', 'Guru', 'Jam', 'Alasan', 'Dibuat Oleh', 'Aksi']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date_from', 'toId' => 'filter-date_to'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

<div class="card mt-4 p-4 text-sm text-slate-600 dark:text-slate-300">
    <p class="font-semibold text-slate-900 dark:text-white">Cara menambah pengecualian</p>
    <p class="mt-1">Guru mencatatnya dari portal <strong>Absensi Siswa</strong> → pilih jadwal → tombol <strong>Sesi tidak diabsen</strong> (misalnya kegiatan diganti yasin tanpa absensi).</p>
</div>
@endsection
