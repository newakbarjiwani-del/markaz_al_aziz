@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $isActive = $token['active'] ?? false;
    $accessUrl = $token['access_url'] ?? '';
@endphp

<div class="mx-auto max-w-2xl space-y-6">
    <div class="card p-6">
        <div class="mb-4 flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                <x-icon name="link" size="md" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Link Login Portal</h2>
                <p class="text-muted mt-1 text-sm">
                    Gunakan link ini untuk masuk tanpa username/password di perangkat lain.
                    Link berlaku {{ $ttlDays }} hari dan hanya untuk Anda.
                </p>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            @if($isActive)
                <span class="badge badge-success">Link aktif</span>
            @else
                <span class="badge badge-warning">Belum ada link aktif</span>
            @endif
        </div>

        @if($isActive)
            <dl class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/50 sm:grid-cols-2">
                <div>
                    <dt class="text-muted text-xs uppercase tracking-wide">Kedaluwarsa</dt>
                    <dd class="mt-1 font-medium" id="portal-token-expires">
                        {{ \App\Support\DisplayDate::datetime($token['expires_at'] ?? null) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted text-xs uppercase tracking-wide">Terakhir dipakai</dt>
                    <dd class="mt-1 font-medium" id="portal-token-last-used">
                        {{ \App\Support\DisplayDate::datetime($token['last_used_at'] ?? null, 'Belum pernah') }}
                    </dd>
                </div>
            </dl>
        @endif

        <div class="space-y-3">
            <label class="form-label" for="portal-token-url">Link login</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input type="text"
                       id="portal-token-url"
                       class="form-input font-mono text-xs sm:text-sm"
                       value="{{ $accessUrl }}"
                       readonly
                       placeholder="Buat link login terlebih dahulu">
                <button type="button"
                        class="btn-secondary shrink-0"
                        id="portal-token-copy"
                        data-portal-token-copy
                        @disabled(! $isActive)>
                    <x-icon name="clipboard-copy" size="sm" class="mr-1" /> Salin
                </button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <button type="button"
                    class="btn-primary"
                    id="portal-token-refresh"
                    data-portal-token-refresh
                    data-portal-token-refresh-url="{{ $refreshUrl }}">
                <x-icon name="refresh" size="sm" class="mr-1" />
                {{ $isActive ? 'Perbarui link' : 'Buat link login' }}
            </button>
        </div>

        <p class="text-muted mt-4 text-xs">
            Memperbarui link akan menonaktifkan link sebelumnya. Jangan bagikan link login kepada pihak lain.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/portal-token-self.js') }}?v=1"></script>
@endpush
