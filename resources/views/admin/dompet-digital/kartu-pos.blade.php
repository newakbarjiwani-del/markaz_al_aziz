@extends('layouts.app')

@section('title', $title)

@section('content')
<p class="mb-4 text-sm text-slate-500">Kartu siswa untuk transaksi POS dan scan QR di kantin.</p>
@include('admin.partials.card-grid', [
    'cards' => $cards,
    'type' => 'siswa',
    'emptyMessage' => 'Belum ada kartu aktif untuk POS.',
])
@endsection
