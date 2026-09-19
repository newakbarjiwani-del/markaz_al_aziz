@extends('errors.minimal')

@section('title', 'Sedang Pemeliharaan')
@section('code', '503')
@section('icon', 'tool')
@section('tone', 'accent')
@section('heading', 'Sistem sedang pemeliharaan')
@section('message', 'Layanan sementara tidak tersedia karena pemeliharaan atau pembaruan sistem.')
@section('hint', 'Silakan kembali beberapa saat lagi. Terima kasih atas pengertian Anda.')
@section('actions')
    <button type="button" class="btn-primary" onclick="window.location.reload()">
        <x-icon name="refresh" size="sm" class="mr-1" />
        Muat Ulang
    </button>
    <a href="{{ url('/') }}" class="btn-secondary">
        <x-icon name="home" size="sm" class="mr-1" />
        Ke Beranda
    </a>
@endsection
