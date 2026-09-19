@extends('layouts.app')
@section('title', $title)
@section('content')
@include('admin.partials.datatable-page', [
    'tableTitle' => 'Menu Kantin',
    'ajaxUrl' => route('admin.dompet-digital.menu-kantin.data'),
    'columns' => ['Nama Menu', 'Kategori', 'Harga', 'Status'],
])
@endsection
