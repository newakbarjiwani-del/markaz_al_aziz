@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Peminjaman Buku"
    :ajax-url="route('portal.ortu.perpustakaan.data')"
    :columns="['NIS', 'Nama', 'Judul Buku', 'Pinjam', 'Jatuh Tempo', 'Kembali', 'Status']"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.ortu-datatable-filters', ['children' => $children])
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
