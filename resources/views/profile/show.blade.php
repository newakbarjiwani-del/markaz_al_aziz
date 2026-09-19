@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $roleName = $user->getRoleNames()->first();
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'guru' => 'Guru',
        'orang_tua' => 'Orang Tua',
        'siswa' => 'Siswa',
        'kantin' => 'Operator Kantin',
        'bendahara' => 'Bendahara',
        'cashless' => 'Operator Cashless',
        'pimpinan' => 'Pimpinan',
        'perpustakaan' => 'Perpustakaan',
    ];
    $roleLabel = $roleLabels[$roleName] ?? ucfirst(str_replace('_', ' ', (string) $roleName));
    $linkedProfile = match ($roleName) {
        'siswa' => $user->siswa
            ? $user->siswa->name.' · NIS '.$user->siswa->nis.($user->siswa->kelas?->name ? ' · '.$user->siswa->kelas->name : '')
            : null,
        'guru' => $user->guru
            ? $user->guru->name.($user->guru->nip ? ' · NIP '.$user->guru->nip : '')
            : null,
        'orang_tua' => $user->orangTua?->displayName(),
        default => null,
    };
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div class="card p-6">
        <div class="flex flex-wrap items-start gap-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                <x-icon name="user" size="lg" />
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $user->name }}</h1>
                <p class="text-muted mt-1 text-sm">{{ $roleLabel }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="badge badge-{{ $user->canLogin() ? 'success' : 'neutral' }}">
                        {{ $user->statusLabel() }}
                    </span>
                    @if($user->sekolah)
                        <span class="badge badge-neutral">{{ $user->sekolah->name }}</span>
                    @elseif($user->hasRole('super_admin'))
                        <span class="badge badge-neutral">Semua Sekolah</span>
                    @endif
                </div>
            </div>
        </div>

        <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-6 dark:border-slate-800 sm:grid-cols-2">
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Username</dt>
                <dd class="mt-1 font-mono text-sm">{{ $user->username }}</dd>
            </div>
            <div>
                <dt class="text-muted text-xs uppercase tracking-wide">Peran</dt>
                <dd class="mt-1 text-sm">{{ $roleLabel }}</dd>
            </div>
            @if($linkedProfile)
                <div class="sm:col-span-2">
                    <dt class="text-muted text-xs uppercase tracking-wide">Profil Terhubung</dt>
                    <dd class="mt-1 text-sm">{{ $linkedProfile }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Data Akun</h2>
        <form data-fetch-form
              data-reload-page
              action="{{ route('profile.update') }}"
              method="POST"
              class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label" for="profile-name">Nama Lengkap</label>
                <input type="text"
                       id="profile-name"
                       name="name"
                       class="form-input"
                       value="{{ old('name', $user->name) }}"
                       required>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="profile-email">Email</label>
                    <input type="email"
                           id="profile-email"
                           name="email"
                           class="form-input"
                           value="{{ old('email', $user->email) }}"
                           placeholder="Opsional">
                </div>
                <div>
                    <label class="form-label" for="profile-phone">Telepon</label>
                    <input type="text"
                           id="profile-phone"
                           name="phone"
                           class="form-input"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="Opsional">
                </div>
            </div>
            <p class="text-muted text-xs">Username, peran, dan status hanya dapat diubah oleh administrator.</p>
            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ubah Password</h2>
        <form data-fetch-form
              action="{{ route('profile.password') }}"
              method="POST"
              class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label" for="profile-current-password">Password Saat Ini</label>
                <input type="password"
                       id="profile-current-password"
                       name="current_password"
                       class="form-input"
                       required
                       autocomplete="current-password">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="profile-password">Password Baru</label>
                    <input type="password"
                           id="profile-password"
                           name="password"
                           class="form-input"
                           required
                           autocomplete="new-password">
                </div>
                <div>
                    <label class="form-label" for="profile-password-confirmation">Konfirmasi Password Baru</label>
                    <input type="password"
                           id="profile-password-confirmation"
                           name="password_confirmation"
                           class="form-input"
                           required
                           autocomplete="new-password">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</div>
@endsection
