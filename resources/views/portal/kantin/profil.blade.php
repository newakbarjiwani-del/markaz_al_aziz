@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-lg">
    <div class="card p-6 text-center">
        <x-app-logo size="md" class="mx-auto" />
        <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
        <p class="text-muted mt-1">Operator Kantin</p>
        <p class="text-muted text-sm">{{ $user->sekolah?->name ?? 'Sekolah' }}</p>
    </div>

    <dl class="card mt-4 divide-y">
        <div class="flex justify-between gap-4 p-4">
            <dt class="text-muted">Email</dt>
            <dd class="font-medium">{{ $user->email }}</dd>
        </div>
        <div class="flex justify-between gap-4 p-4">
            <dt class="text-muted">Username</dt>
            <dd class="font-medium">{{ $user->username ?? '-' }}</dd>
        </div>
        <div class="flex justify-between gap-4 p-4">
            <dt class="text-muted">Telepon</dt>
            <dd class="font-medium">{{ $user->phone ?? '-' }}</dd>
        </div>
        <div class="flex justify-between gap-4 p-4">
            <dt class="text-muted">Status</dt>
            <dd><span class="badge badge-{{ $user->canLogin() ? 'green' : 'neutral' }}">{{ $user->statusLabel() }}</span></dd>
        </div>
    </dl>
</div>
@endsection
