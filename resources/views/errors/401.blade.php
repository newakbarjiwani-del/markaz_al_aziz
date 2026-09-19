@extends('errors.minimal')

@section('title', 'Belum Login')
@section('code', '401')
@section('icon', 'user-exclamation')
@section('tone', 'warning')
@section('heading', 'Autentikasi diperlukan')
@section('message', 'Silakan login terlebih dahulu untuk melanjutkan.')
@section('actions')
    <a href="{{ route('login') }}" class="btn-primary">
        <x-icon name="login" size="sm" class="mr-1" />
        Login
    </a>
    <a href="{{ url('/') }}" class="btn-secondary">
        <x-icon name="home" size="sm" class="mr-1" />
        Ke Beranda
    </a>
@endsection
