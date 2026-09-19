@extends('layouts.app')
@section('title', $title)
@section('content')
@include('admin.partials.datatable-page', [
    'tableTitle' => 'Riwayat Mengajar',
    'ajaxUrl' => route('admin.manajemen-guru.riwayat-mengajar.data'),
    'columns' => ['NIP', 'Nama Guru', 'Mata Pelajaran', 'Kelas', 'Tahun'],
])
@endsection
