@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.partials.datatable-page', [
    'tableTitle' => 'Rating & Review Buku',
    'ajaxUrl' => $ajaxUrl ?? route('admin.perpustakaan.rating-ulasan.data'),
    'columns' => ['Buku', 'Siswa', 'Rating', 'Ulasan', 'Tanggal'],
])
@endsection
