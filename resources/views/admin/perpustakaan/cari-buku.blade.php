@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <form method="GET" action="{{ $searchAction ?? route('admin.perpustakaan.cari-buku') }}" class="flex flex-wrap gap-4">
        <div class="min-w-[280px] flex-1">
            <label for="cari-buku" class="form-label">Cari Buku</label>
            <input type="search" name="q" id="cari-buku" value="{{ $q }}" class="form-input" placeholder="Judul, pengarang, penerbit, kode, ISBN..." autofocus>
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">
                <x-icon name="search" size="sm" class="mr-1" /> Cari
            </button>
        </div>
    </form>
</div>

@if($q !== '')
    <p class="mb-4 text-sm text-slate-500">{{ $books->count() }} hasil untuk "{{ $q }}"</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($books as $book)
            <div class="card p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $book->judul }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $book->pengarang ?? 'Pengarang tidak diketahui' }}</p>
                @if($book->penerbit || $book->tahun_terbit)
                    <p class="mt-1 text-xs text-slate-400">{{ $book->penerbit }}{{ $book->tahun_terbit ? ' · '.$book->tahun_terbit : '' }}</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="badge badge-blue">{{ $book->kategori ?? 'Umum' }}</span>
                    <span class="badge badge-green">Tersedia: {{ $book->tersedia }}/{{ $book->jumlah }}</span>
                    @if($book->nilai_rata)
                        <span class="badge badge-amber">★ {{ number_format($book->nilai_rata, 1) }}</span>
                    @endif
                </div>
                @if($book->kode_buku)
                    <p class="mt-2 text-xs text-slate-400">Kode: {{ $book->kode_buku }}</p>
                @endif
                @if($book->isbn)
                    <p class="mt-1 text-xs text-slate-400">ISBN: {{ $book->isbn }}</p>
                @endif
            </div>
        @empty
            <div class="card col-span-full p-8 text-center text-slate-500">Tidak ada buku ditemukan.</div>
        @endforelse
    </div>
@else
    <div class="card p-8 text-center text-slate-500">Masukkan kata kunci untuk mencari buku.</div>
@endif
@endsection
