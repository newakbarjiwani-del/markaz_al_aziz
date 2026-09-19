@extends('layouts.app')

@section('title', '')

@section('content')
<div class="mx-auto flex min-h-[50vh] max-w-md flex-col items-center justify-center text-center">
    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
        <x-icon name="wifi-off" size="lg" />
    </div>
    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Anda sedang offline</h1>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        Koneksi internet tidak tersedia. Buka kembali halaman ini setelah sinyal kembali normal.
    </p>
    <button type="button" class="btn-primary mt-6" onclick="window.location.reload()">
        Coba Lagi
    </button>
</div>
@endsection
