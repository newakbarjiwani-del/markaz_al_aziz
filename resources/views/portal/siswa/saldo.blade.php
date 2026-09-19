@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat-card label="Saldo" :value="'Rp '.number_format($stats['saldo'], 0, ',', '.')" accent="primary" />
    <x-stat-card label="Masuk Bulan Ini" :value="'Rp '.number_format($stats['bulan_ini_kredit'], 0, ',', '.')" accent="green" />
    <x-stat-card label="Keluar Bulan Ini" :value="'Rp '.number_format($stats['bulan_ini_debet'], 0, ',', '.')" accent="amber" />
    <x-stat-card label="Total Transaksi" :value="$stats['total_transaksi']" accent="blue" />
</div>

<x-admin.datatable-page
    title="Riwayat Saldo"
    :ajax-url="route('portal.siswa.saldo.data')"
    :columns="['Tanggal', 'Metode', 'Kredit', 'Debet', 'Referensi', 'Channel']"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'colClass' => '',
            ])
            <div>
                <label class="form-label">Metode</label>
                <select name="metode" class="form-input">
                    <option value="">Semua</option>
                    @foreach($metodeOptions as $metode)
                        <option value="{{ $metode }}" @selected(request('metode') === $metode)>{{ $metode }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
