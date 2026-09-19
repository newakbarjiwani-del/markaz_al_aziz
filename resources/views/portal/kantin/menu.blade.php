@extends('layouts.app')

@section('title', $title)

@section('content')
@if($menus->isEmpty())
    <div class="card p-6 text-center text-muted">Belum ada menu kantin aktif.</div>
@else
    <div class="grid gap-3">
        @foreach($menus as $menu)
            <div class="card flex items-center justify-between gap-4 p-4">
                <div>
                    <p class="font-semibold">{{ $menu->name }}</p>
                    <p class="text-muted text-sm">{{ ucfirst($menu->category ?? 'Makanan') }}</p>
                </div>
                <p class="font-semibold text-primary-600">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>
@endif
@endsection
