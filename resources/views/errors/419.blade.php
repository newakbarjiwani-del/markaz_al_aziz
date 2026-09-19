@extends('errors.minimal')

@section('title', 'Sesi Kedaluwarsa')
@section('code', '419')
@section('icon', 'refresh')
@section('tone', 'warning')
@section('heading', 'Sesi telah kedaluwarsa')
@section('message', 'Halaman ini tidak lagi valid karena token keamanan sudah kedaluwarsa.')
@section('hint', 'Muat ulang halaman lalu coba lagi, atau login kembali jika masalah berlanjut.')
@section('actions')
    <button type="button" class="btn-primary" onclick="window.location.reload()">
        <x-icon name="refresh" size="sm" class="mr-1" />
        Muat Ulang
    </button>
    <a href="{{ route('login') }}" class="btn-secondary">
        <x-icon name="login" size="sm" class="mr-1" />
        Login
    </a>
@endsection
