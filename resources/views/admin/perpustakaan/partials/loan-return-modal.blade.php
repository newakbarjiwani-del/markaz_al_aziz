<x-modal id="loan-return-modal" title="Pengembalian Buku">
    <form
        id="loan-return-form"
        data-fetch-form
        data-close-modal="loan-return-modal"
        data-reset-on-success="false"
        action="{{ $formAction ?? route('admin.perpustakaan.pengembalian-buku.store') }}"
        method="POST"
        class="space-y-5"
    >
        @csrf
        <input type="hidden" name="peminjaman_id" id="return_peminjaman_id">

        <section class="form-section">
            <h4 class="form-section__title">Informasi Peminjaman</h4>
            <div class="form-section__body">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                    <p class="font-medium text-slate-900 dark:text-white" id="return-loan-label">—</p>
                    <div class="mt-2 grid gap-1 text-slate-500 sm:grid-cols-2">
                        <p>Pinjam: <span id="return-loan-date">—</span></p>
                        <p>Jatuh tempo: <span id="return-loan-due">—</span></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Pengembalian</h4>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="return_date">Tanggal Kembali</label>
                        <input type="date" name="return_date" id="return_date" class="form-input" value="{{ now()->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label" for="kondisi_kembali">Kondisi Buku</label>
                        <select name="kondisi_kembali" id="kondisi_kembali" class="form-input" required>
                            @foreach(($kondisiOptions ?? []) as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="catatan_kembali">Catatan Pengembalian</label>
                    <textarea name="catatan_kembali" id="catatan_kembali" rows="2" class="form-input" maxlength="500" placeholder="Opsional — keterangan kondisi, kerusakan, dll."></textarea>
                </div>

                <div id="return-fine-preview" class="library-loan-summary-empty text-sm text-slate-500">
                    Rincian denda akan muncul setelah kondisi buku dipilih.
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" class="btn-secondary" data-modal-close="loan-return-modal">Batal</button>
            <button type="submit" class="btn-primary" id="loan-return-submit">
                <x-icon name="book-upload" size="sm" class="mr-1" /> Proses Pengembalian
            </button>
        </div>
    </form>
</x-modal>
