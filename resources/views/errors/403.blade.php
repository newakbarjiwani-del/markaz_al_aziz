@extends('errors.minimal')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('icon', 'lock')
@section('tone', 'danger')
@section('heading', 'Akses ditolak')
@section('message', $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.')
@section('hint', 'Jika Anda yakin seharusnya bisa mengakses halaman ini, hubungi administrator sekolah.')
