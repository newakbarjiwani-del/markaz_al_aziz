@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="mx-auto max-w-xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-secondary">Profil Akun</h1>
        <p class="mt-1 text-muted">Perbarui nama dan kontak Anda.</p>
    </div>

    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="card space-y-4 p-5">
        @csrf
        @method('PUT')

        <div>
            <label class="form-label" for="username">Username</label>
            <input id="username" type="text" class="form-input" value="{{ $user->username }}" disabled>
        </div>

        <div>
            <label class="form-label" for="name">Nama</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required>
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="form-label" for="phone">Telepon</label>
            <input id="phone" name="phone" type="text" class="form-input" value="{{ old('phone', $user->phone) }}">
            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
@endsection
