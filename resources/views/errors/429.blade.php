@extends('errors.minimal')

@section('title', 'Terlalu Banyak Permintaan')
@section('code', '429')
@section('icon', 'clock-pause')
@section('tone', 'warning')
@section('heading', 'Terlalu banyak permintaan')
@section('message', 'Anda mengirim permintaan terlalu cepat. Tunggu beberapa saat lalu coba lagi.')
@section('hint', 'Batas ini membantu menjaga keamanan dan stabilitas sistem.')
@section('actions')
    <button type="button" class="btn-primary" onclick="window.location.reload()">
        <x-icon name="refresh" size="sm" class="mr-1" />
        Coba Lagi
    </button>
    <a href="{{ url('/') }}" class="btn-secondary">
        <x-icon name="home" size="sm" class="mr-1" />
        Ke Beranda
    </a>
@endsection
