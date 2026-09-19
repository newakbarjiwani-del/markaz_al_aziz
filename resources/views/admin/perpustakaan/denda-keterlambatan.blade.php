@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="stat-card stat-card-amber mb-6 max-w-sm">
    <p class="text-sm text-slate-500">Total Denda Terkumpul</p>
    <p class="mt-2 text-2xl font-bold">Rp {{ number_format($totalFine, 0, ',', '.') }}</p>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Denda Keterlambatan',
    'ajaxUrl' => $ajaxUrl ?? route('admin.perpustakaan.denda-keterlambatan.data'),
    'columns' => ['Tipe', 'ID/NIS/NIP', 'Nama', 'Jml', 'Buku', 'Jatuh Tempo', 'Kembali', 'Terlambat', 'Denda'],
])
@endsection
