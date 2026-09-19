@extends('layouts.app')

@section('title', $title)

@section('content')

<x-admin.datatable-page
    title="Pendaftar SPMB"
    subtitle="Verifikasi, terima, atau tolak calon murid"
    :ajax-url="route('admin.spmb.pendaftar.data')"
    :columns="['Nomor', 'Nama', 'Periode', 'Status', 'Diajukan', 'Aksi']">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
