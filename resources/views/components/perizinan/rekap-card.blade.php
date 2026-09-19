@props([
    'summary' => [
        'total' => 0,
        'aktif' => 0,
        'kembali' => 0,
        'terlambat' => 0,
        'pending' => 0,
        'recent' => collect(),
    ],
    'title' => 'Rekap Perizinan Santri',
    'rekapUrl' => route('portal.perizinan.rekap-laporan'),
    'showLink' => true,
])

<div id="rekap-perizinan" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Ringkasan status perizinan keluar/masuk & pulang libur</p>
            </div>
        </div>

        @if ($showLink && $rekapUrl)
            <a href="{{ $rekapUrl }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                <span>Lihat Selengkapnya</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        @endif
    </div>

    {{-- Stat Cards Grid --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
        <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5 dark:border-blue-900/40 dark:bg-blue-950/20">
            <div class="text-xs font-medium text-blue-600 dark:text-blue-400">Sedang Izin (Diluar)</div>
            <div class="mt-1 text-xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($summary['aktif'] ?? 0) }}</div>
        </div>

        <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/20">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Menunggu Persetujuan</div>
            <div class="mt-1 text-xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($summary['pending'] ?? 0) }}</div>
        </div>

        <div class="rounded-xl border border-red-100 bg-red-50/50 p-3.5 dark:border-red-900/40 dark:bg-red-950/20">
            <div class="text-xs font-medium text-red-600 dark:text-red-400">Terlambat Kembali</div>
            <div class="mt-1 text-xl font-bold text-red-700 dark:text-red-300">{{ number_format($summary['terlambat'] ?? 0) }}</div>
        </div>

        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3.5 dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Sudah Kembali</div>
            <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($summary['kembali'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Recent Items List --}}
    @if (isset($summary['recent']) && count($summary['recent']) > 0)
        <div class="space-y-3">
            <div class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Perizinan Terbaru</div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($summary['recent'] as $item)
                    <div class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0 gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm text-slate-900 truncate dark:text-white">{{ $item->siswa?->name ?? '-' }}</span>
                                <span class="text-xs text-slate-400">({{ $item->siswa?->kelas?->nama_kelas ?? '-' }})</span>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $item->jenis_label }}</span> — {{ $item->alasan }}
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">Pemberi Izin:</span> {{ $item->pemberiIzinLabel() }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            {!! $item->status_badge !!}
                            <div class="text-[11px] text-slate-400 mt-1">
                                {{ $item->tgl_mulai ? $item->tgl_mulai->isoFormat('D MMM, HH:mm') : '-' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400 dark:border-slate-800">
            Belum ada data perizinan.
        </div>
    @endif
</div>
