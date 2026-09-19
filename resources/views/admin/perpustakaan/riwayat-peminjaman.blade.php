@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.partials.datatable-page', [
    'tableTitle' => 'History Peminjaman',
    'ajaxUrl' => $ajaxUrl ?? route('admin.perpustakaan.riwayat-peminjaman.data'),
    'columns' => ['Tipe', 'ID/NIS/NIP', 'Nama', 'Jml', 'Buku', 'Pinjam', 'Jatuh Tempo', 'Kembali', 'Status'],
])
@endsection
