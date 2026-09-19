@extends('errors.minimal')

@section('title', 'Kesalahan Server')
@section('code', '500')
@section('icon', 'server-bolt')
@section('tone', 'danger')
@section('heading', 'Terjadi kesalahan server')
@section('message', 'Maaf, sistem mengalami gangguan saat memproses permintaan Anda.')
@section('hint', 'Tim IT sekolah telah diberi tahu jika logging aktif. Silakan coba beberapa saat lagi.')
