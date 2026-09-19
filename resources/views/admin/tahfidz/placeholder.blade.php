@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $title }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
</div>

<div class="card p-8 text-center text-slate-500">
    Halaman placeholder — konten lengkap akan diisi pada fase berikutnya.
</div>
@endsection
