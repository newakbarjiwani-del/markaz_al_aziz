@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Saldo Cashless" :value="'Rp '.number_format($saldoCashless, 0, ',', '.')" accent="primary" />
    <x-stat-card label="Total Top-up" :value="'Rp '.number_format($totalTopup, 0, ',', '.')" accent="green" />
    <x-stat-card label="Total Belanja" :value="'Rp '.number_format($totalBelanja, 0, ',', '.')" accent="amber" />
</div>

<x-admin.datatable-page
    title="Riwayat Belanja Cashless"
    :ajax-url="route('portal.siswa.cashless.data')"
    :columns="['Tanggal', 'Jenis', 'Nominal', 'No. Transaksi', 'Kantin']"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'colClass' => '',
            ])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
