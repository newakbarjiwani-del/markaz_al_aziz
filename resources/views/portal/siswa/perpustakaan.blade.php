@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Peminjaman Buku"
    :ajax-url="route('portal.siswa.perpustakaan.data')"
    :columns="['Judul Buku', 'Pinjam', 'Jatuh Tempo', 'Kembali', 'Status']"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.date-filter')
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
