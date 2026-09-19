@extends('layouts.guest')

@section('title', $title)

@section('content')
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="card w-full max-w-md p-6 text-center">
        <x-app-logo size="md" class="mx-auto mb-4" />
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-300">
            <x-icon name="alert-circle" size="lg" />
        </div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Link akses portal tidak valid, sudah kedaluwarsa, atau telah dicabut oleh admin sekolah.
        </p>
        <a href="{{ route('login') }}" class="btn-primary mt-6 inline-flex">Ke Halaman Login</a>
    </div>
</div>
@endsection
