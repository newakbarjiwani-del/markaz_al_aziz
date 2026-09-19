<x-modal id="loan-extend-modal" title="Perpanjang Peminjaman">
    <form id="loan-extend-form" class="space-y-5">
        <input type="hidden" name="loan_id" id="extend_loan_id">
        <section class="form-section">
            <h4 class="form-section__title">Informasi Peminjaman</h4>
            <div class="form-section__body">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                    <p class="font-medium text-slate-900 dark:text-white" id="extend-loan-label">—</p>
                    <p class="mt-1 text-slate-500">Jatuh tempo saat ini: <span id="extend-current-due">—</span></p>
                </div>
            </div>
        </section>
        <section class="form-section">
            <h4 class="form-section__title">Perpanjangan</h4>
            <div class="form-section__body">
                <label class="form-label" for="extend_due_date">Jatuh Tempo Baru</label>
                <input type="date" name="due_date" id="extend_due_date" class="form-input" required>
                <p class="mt-1 text-xs text-slate-500">Isi tanggal jatuh tempo baru setelah perpanjangan.</p>
            </div>
        </section>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-modal-close="loan-extend-modal">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="calendar-plus" size="sm" class="mr-1" /> Simpan Perpanjangan
            </button>
        </div>
    </form>
</x-modal>
