@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Total Tagihan" :value="$stats['total']" accent="primary" />
    <x-stat-card label="Lunas" :value="$stats['lunas']" accent="green" />
    <x-stat-card label="Belum Lunas" :value="$stats['belum_lunas']" accent="accent" />
</div>

<x-admin.datatable-page
    title="Daftar Tagihan"
    :ajax-url="route('portal.siswa.tagihan.data')"
    :columns="['No. VA', 'Jenis', 'Periode', 'Nominal', 'Terbayar', 'Sisa', 'Status', 'Tgl. Lunas', 'Jatuh Tempo']"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'colClass' => '',
            ])
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="">Semua</option>
                    <option value="0" @selected(request('status') === '0')>Belum Lunas</option>
                    <option value="1" @selected(request('status') === '1')>Lunas</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
