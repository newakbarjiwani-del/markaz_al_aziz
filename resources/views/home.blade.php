@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="card space-y-3 p-6">
    <h1 class="text-2xl font-bold text-secondary">Beranda</h1>
    <p class="text-muted">
        Portal untuk peran Anda akan tersedia pada fase berikutnya.
        Sementara ini Anda dapat mengelola <a href="{{ route('profile.edit') }}" class="text-primary underline">profil</a>.
    </p>
</div>
@endsection
