@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Profil Guru"
    :ajax-url="route('admin.manajemen-guru.profil-guru.data')"
    :columns="['NIP', 'Nama', 'Jabatan', 'Foto', 'Alamat', 'Pendidikan']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-q">Cari Guru</label>
                <input type="search"
                       name="q"
                       id="filter-q"
                       value="{{ request('q') }}"
                       class="form-input"
                       placeholder="NIP, nama, atau jabatan"
                       autocomplete="off">
            </div>
            @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah'])
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
