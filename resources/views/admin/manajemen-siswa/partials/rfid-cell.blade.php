@props(['siswa'])

<div class="text-sm">
    <p class="font-mono text-slate-800 dark:text-slate-200">{{ $siswa->rfidUid() ?: '-' }}</p>
    @if($siswa->isRfidBlocked())
        <span class="mt-1 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950/50 dark:text-red-300">
            Diblokir
        </span>
    @elseif($siswa->hasRfid())
        <span class="mt-1 inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
            Aktif
        </span>
    @endif
    @if($siswa->daily_transaction_limit)
        <p class="text-muted mt-1 text-xs">Limit: Rp {{ number_format($siswa->daily_transaction_limit, 0, ',', '.') }}</p>
    @endif
</div>
