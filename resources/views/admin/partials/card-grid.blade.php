@props(['cards', 'type' => 'siswa', 'emptyMessage' => 'Tidak ada data.'])

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse($cards as $card)
        @php
            $person = $type === 'guru' ? $card->guru : $card->siswa;
            $code = $type === 'guru'
                ? ($person?->nip ?? 'GURU-'.$card->id)
                : ($card->qr_code ?? $person?->nis ?? 'SISWA-'.$card->id);
        @endphp
        <div class="card p-5 text-center">
            <div class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                <x-icon name="qrcode" size="lg" />
            </div>
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ $person?->name ?? '-' }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $code }}</p>
            @if($type === 'siswa' && $person?->kelas)
                <p class="text-xs text-slate-400">{{ $person->kelas->name }}</p>
            @elseif($type === 'guru')
                <p class="text-xs text-slate-400">{{ $person?->jabatan ?? '-' }}</p>
            @endif
            <span class="badge badge-green mt-3 inline-block">{{ ucfirst($card->status) }}</span>
        </div>
    @empty
        <div class="card col-span-full p-8 text-center text-slate-500">{{ $emptyMessage }}</div>
    @endforelse
</div>
