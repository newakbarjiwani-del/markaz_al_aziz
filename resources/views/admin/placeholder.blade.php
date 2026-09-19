@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-950 dark:text-primary-400">
        <x-icon name="tool" size="lg" />
    </div>
    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
    <p class="mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}"
       class="btn-secondary mt-6">
        <x-icon name="arrow-left" size="sm" class="mr-1" /> Kembali
    </a>
</div>
@endsection
