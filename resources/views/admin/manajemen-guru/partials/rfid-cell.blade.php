@props(['guru'])

<div>
    <p class="font-mono text-sm text-slate-800 dark:text-slate-200">{{ $guru->rfidUid() ?: '-' }}</p>
    @if($guru->hasRfid())
        <span class="badge badge-success mt-1">Terdaftar</span>
    @else
        <span class="badge badge-secondary mt-1">Belum diisi</span>
    @endif
</div>
