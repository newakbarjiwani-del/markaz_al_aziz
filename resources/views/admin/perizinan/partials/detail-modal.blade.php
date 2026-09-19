<x-modal id="perizinan-detail-modal" title="Detail Perizinan" size="lg">
    <div class="space-y-4">
        <div class="flex items-start justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white detail-siswa-name">-</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 detail-siswa-sub">-</p>
            </div>
            <div class="detail-status-badge"></div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Jenis Perizinan</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 detail-jenis">-</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Penanggung Jawab / Penjemput</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 detail-pj">-</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Waktu Berangkat / Mulai</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 detail-tgl-mulai">-</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Batas Waktu Kembali</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 detail-tgl-sampai">-</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 sm:col-span-2 dark:bg-slate-900/60">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Waktu Kembali Aktual</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 detail-tgl-kembali-aktual">-</p>
            </div>
        </div>

        <div>
            <h4 class="text-xs font-medium text-slate-500 dark:text-slate-400">Alasan Perizinan</h4>
            <p class="mt-1 text-sm text-slate-800 dark:text-slate-200 detail-alasan whitespace-pre-line">-</p>
        </div>

        <div>
            <h4 class="text-xs font-medium text-slate-500 dark:text-slate-400">Catatan Tambahan</h4>
            <p class="mt-1 text-sm text-slate-800 dark:text-slate-200 detail-catatan whitespace-pre-line">-</p>
        </div>

        <div id="detail-file-section" class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/60">
            <h4 class="text-xs font-medium text-slate-500 dark:text-slate-400">Bukti Dokumen / Surat</h4>
            <div id="detail-file-content" class="mt-2 text-sm">
                <span class="text-slate-400 text-xs italic">Tidak ada file lampiran</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 border-t border-slate-200 pt-3 text-xs text-slate-500 dark:border-slate-800">
            <div>Pemberi izin: <span class="font-medium text-slate-700 dark:text-slate-300 detail-approved-by">-</span></div>
            <div>Dicatat oleh (akun): <span class="font-medium text-slate-700 dark:text-slate-300 detail-created-by">-</span></div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end border-t border-slate-200 pt-4 dark:border-slate-800">
        <button type="button" class="btn-secondary" data-close-modal="perizinan-detail-modal">Tutup</button>
    </div>
</x-modal>
