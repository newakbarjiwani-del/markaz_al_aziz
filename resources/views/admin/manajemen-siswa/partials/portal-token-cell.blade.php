@props(['expiresAt' => null])

@php
    $parsed = $expiresAt ? \Illuminate\Support\Carbon::parse($expiresAt) : null;
    $active = $parsed?->isFuture() ?? false;
@endphp

<div class="portal-token-cell {{ $active ? 'portal-token-cell--active' : 'portal-token-cell--inactive' }}">
    <span class="badge shrink-0 {{ $active ? 'badge-success' : 'badge-neutral' }}">
        {{ $active ? 'Aktif' : 'Tidak aktif' }}
    </span>
    <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ $active ? \App\Support\DisplayDate::datetime($parsed) : '—' }}
    </p>
</div>
