@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Riwayat Pemakaian Potongan"
    subtitle="Audit log setiap langkah potongan yang diterapkan pada tagihan (read-only)"
    :ajax-url="route('admin.keuangan.potongan-pemakaian.data')"
    export-filename="Riwayat Pemakaian Potongan"
    :columns="['Waktu', 'NIS', 'Nama', 'Jenis Potongan', 'Tagihan', 'Periode', 'Potongan', 'Sisa Net', 'Urutan', 'Oleh']"
    :show-export="true" />
@endsection
