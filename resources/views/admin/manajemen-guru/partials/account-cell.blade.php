@props(['guru'])

@php
    $user = $guru->portalUser;
@endphp

@if($user)
    <div class="teacher-account-cell teacher-account-cell--linked">
        <span class="badge badge-success shrink-0">Punya akun</span>
        <div class="min-w-0">
            <p class="truncate font-medium text-slate-900 dark:text-white">{{ $user->username }}</p>
            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
        </div>
    </div>
@else
    <div class="teacher-account-cell teacher-account-cell--missing">
        <span class="badge badge-warning shrink-0">Belum ada akun</span>
        <p class="text-xs text-slate-500">Buat akun login untuk portal guru</p>
    </div>
@endif
