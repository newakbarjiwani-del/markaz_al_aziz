@extends('layouts.guest')

@section('title', 'Login — ' . config('app.name'))

@section('content')
<div class="login-page">
    <div class="login-page__grid">
        <aside class="login-page__brand" aria-hidden="false">
            <div class="login-page__brand-bg" aria-hidden="true">
                <img src="{{ asset('logo.png') }}" alt="" class="login-page__brand-bg-logo" decoding="async">
            </div>
            <div class="login-page__brand-inner">
                <x-app-logo size="" class="login-page__logo" />
                <h1 class="login-page__brand-title">{{ config('app.name') }}</h1>
                <p class="login-page__brand-tagline">Sistem Akademik Sekolah</p>
            </div>
        </aside>

        <main class="login-page__main">
            <div class="login-page__card card">
                <div class="login-page__form-intro">
                    <h2 class="login-page__heading">Masuk ke akun Anda</h2>
                    <p class="login-page__form-subtitle">Gunakan username atau email sekolah.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="login-page__form space-y-4">
                    @csrf
                    <div>
                        <label for="login" class="form-label">Username atau Email</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                               class="form-input @error('login') border-red-500 @enderror"
                               placeholder="admin atau admin@school.local" autocomplete="username">
                        @error('login')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required
                                   class="form-input pr-10" placeholder="••••••••">
                            <button type="button" data-password-toggle="password"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-muted hover:text-primary-700 dark:hover:text-primary-300"
                                    aria-label="Tampilkan password">
                                <x-icon name="eye" data-icon-show size="md" />
                                <x-icon name="eye-off" data-icon-hide class="hidden" size="md" />
                            </button>
                        </div>
                    </div>

                    <x-form.checkbox name="remember" label="Ingat saya" :hidden-fallback="false" />

                    @if($turnstileEnabled && $turnstileSiteKey)
                        <div class="flex justify-center">
                            <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="auto"></div>
                        </div>
                        @error('cf-turnstile-response')
                            <p class="text-center text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif

                    <button type="submit" class="btn-primary w-full py-2.5">Masuk</button>
                </form>

                <p class="login-page__demo text-muted mt-6 text-center text-xs">
                    Demo: <span class="font-medium">admin</span> atau <span class="font-medium">admin@school.local</span> / <span class="font-medium">password</span>
                </p>
            </div>
        </main>
    </div>
</div>
@endsection

@if($turnstileEnabled && $turnstileSiteKey)
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif
