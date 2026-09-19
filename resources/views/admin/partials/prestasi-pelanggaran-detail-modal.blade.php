@props([
    'type' => 'prestasi',
    'context' => 'siswa',
])

@php
    $isPrestasi = $type === 'prestasi';
    $isSiswa = $context === 'siswa';
    $modalId = "{$type}-{$context}-detail-modal";
    $title = $isPrestasi ? 'Prestasi' : 'Pelanggaran';
@endphp

<x-modal id="{{ $modalId }}" title="Detail {{ $title }}" size="lg">
    <div class="space-y-5">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-muted text-xs uppercase tracking-wide">{{ $isSiswa ? 'Siswa' : 'Guru' }}</p>
                <p class="mt-1 font-semibold text-slate-900 dark:text-white detail-entity-name">-</p>
                <p class="text-muted text-xs detail-entity-sub">-</p>
            </div>
            <div>
                <p class="text-muted text-xs uppercase tracking-wide">Tanggal</p>
                <p class="mt-1 font-semibold text-slate-900 dark:text-white detail-tanggal">-</p>
            </div>
        </div>

        @if(!$isPrestasi)
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <p class="text-muted text-xs uppercase tracking-wide">Jenis Pelanggaran (Katalog)</p>
            <div id="{{ $modalId }}-jenis-empty">
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tidak menggunakan katalog (manual).</p>
            </div>
            <div id="{{ $modalId }}-jenis-filled" class="hidden mt-1">
                <p class="font-semibold text-slate-900 dark:text-white detail-jenis">-</p>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                    <span class="badge detail-jenis-level">-</span>
                    <span class="badge detail-jenis-sanction">-</span>
                    <span class="text-slate-500 dark:text-slate-400">Point katalog: <span class="font-semibold detail-jenis-point">-</span></span>
                </div>
            </div>
        </div>
        @endif

        <div>
            <p class="text-muted text-xs uppercase tracking-wide">Judul</p>
            <p class="mt-1 font-semibold text-slate-900 dark:text-white detail-judul">-</p>
        </div>

        <div>
            <p class="text-muted text-xs uppercase tracking-wide">Keterangan</p>
            <p class="mt-1 text-slate-700 dark:text-slate-300 detail-keterangan">-</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-muted text-xs uppercase tracking-wide">Point</p>
                <p class="mt-1 font-semibold text-slate-900 dark:text-white detail-point">0</p>
            </div>
            <div>
                <p class="text-muted text-xs uppercase tracking-wide">Dicatat oleh</p>
                <p class="mt-1 text-slate-700 dark:text-slate-300 detail-reported-by">-</p>
            </div>
        </div>

        <div>
            <p class="text-muted text-xs uppercase tracking-wide mb-2">Bukti</p>
            <div class="detail-bukti-list space-y-2">
                <p class="text-xs text-slate-400">Tidak ada bukti.</p>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="{{ $modalId }}">Tutup</button>
        </div>
    </div>
</x-modal>
