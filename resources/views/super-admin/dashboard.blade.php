@extends('layouts.app')
@section('title', 'Super Admin')
@section('content')
<div class="dashboard-page space-y-6">
    <p class="text-muted text-sm">Kelola user login dan audit seluruh sekolah.</p>

    <x-dashboard.launcher
        :actions="[
            ['label' => 'Manajemen User', 'route' => 'super-admin.users.index', 'icon' => 'users', 'tone' => 'primary'],
            ['label' => 'Log Login', 'route' => 'super-admin.login-logs.index', 'icon' => 'history', 'tone' => 'info'],
            ['label' => 'Beranda Admin', 'route' => 'admin.dashboard', 'icon' => 'home', 'tone' => 'success'],
        ]"
    />

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($stats as $label => $value)
            <div class="stat-card">
                <p class="text-sm capitalize text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
