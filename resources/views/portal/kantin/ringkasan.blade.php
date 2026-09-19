@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-stat-card label="Transaksi Hari Ini" :value="$stats['transaksi_hari_ini']" accent="primary" />
    <x-stat-card label="Omzet Hari Ini" :value="'Rp '.number_format($stats['omzet_hari_ini'], 0, ',', '.')" accent="accent" />
    <x-stat-card label="Dompet Aktif" :value="$stats['dompet_aktif']" accent="green" />
    <x-stat-card label="Total Saldo Kantin" :value="'Rp '.number_format($stats['total_dompet_kantin'], 0, ',', '.')" accent="blue" />
</div>

<div class="card p-6">
    <h2 class="mb-2 text-lg font-semibold">Ringkasan Operasional</h2>
    <p class="text-muted mb-4 text-sm">Data di-scope ke sekolah operator kantin yang sedang login.</p>
    <dl class="grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-muted text-sm">Menu Aktif</dt>
            <dd class="text-xl font-semibold">{{ $stats['menu_aktif'] }}</dd>
        </div>
        <div>
            <dt class="text-muted text-sm">Periode</dt>
            <dd class="text-xl font-semibold">{{ now()->translatedFormat('d F Y') }}</dd>
        </div>
    </dl>
</div>
@endsection
