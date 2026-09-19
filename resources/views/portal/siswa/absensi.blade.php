@extends('layouts.app')

@section('title', $title)

@section('content')
<x-admin.datatable-page
    title="Absensi Saya"
    :ajax-url="route('portal.siswa.absensi.data')"
    :columns="['Tanggal', 'Status', 'Jam Masuk']"
    :show-export="true">
    <x-slot:filters>
        @include('portal.partials.date-filter')
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
