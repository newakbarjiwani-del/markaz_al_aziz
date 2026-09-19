@props([
    'sanctionOptions' => [],
    'statusOptions' => [],
    'prefillSiswaId' => null,
    'openCreate' => false,
    'hukumanMinPoints' => 250,
])

<x-modal id="hukuman-siswa-modal" title="Terbitkan Hukuman Siswa" size="lg">
    <form id="hukuman-siswa-form"
          data-fetch-form
          data-reload-table
          data-close-modal="hukuman-siswa-modal"
          data-default-action="{{ route('admin.prestasi-pelanggaran.hukuman-siswa.store') }}"
          action="{{ route('admin.prestasi-pelanggaran.hukuman-siswa.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf

        <div id="hukuman-siswa-create-fields" class="space-y-4">
            <input type="hidden" name="siswa_id" id="hukuman-siswa-siswa" value="{{ $prefillSiswaId ?: '' }}" required>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                <p class="text-xs uppercase tracking-wide text-slate-500">Siswa (dari daftar eligible ≥{{ $hukumanMinPoints }})</p>
                <p id="hukuman-siswa-display-name" class="mt-1 font-semibold text-slate-900 dark:text-white">Pilih dari daftar eligible</p>
                <p id="hukuman-siswa-display-sub" class="text-sm text-slate-500">-</p>
            </div>

            <div id="hukuman-recommendation-panel" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Pilih pelanggaran yang dihukum</p>
                <p class="mt-1 text-xs text-slate-500">Hanya pelanggaran tercentang yang direset poinnya. Siswa tetap eligible jika sisa poin ≥ ambang.</p>
                <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-slate-500">Poin dipilih</span>
                        <p id="hukuman-rec-total-point" class="font-semibold">0</p>
                    </div>
                    <div>
                        <span class="text-slate-500">Rekomendasi hukuman</span>
                        <p id="hukuman-rec-sanction" class="font-semibold">-</p>
                    </div>
                </div>
                <p id="hukuman-rec-available" class="mt-1 text-xs text-slate-500"></p>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    <button type="button" id="hukuman-rec-select-all" class="font-medium text-primary-700 hover:underline dark:text-primary-300">Pilih semua</button>
                    <button type="button" id="hukuman-rec-clear-all" class="font-medium text-slate-600 hover:underline dark:text-slate-300">Kosongkan</button>
                </div>
                <div id="hukuman-rec-violations" class="mt-3 max-h-56 space-y-2 overflow-y-auto"></div>
            </div>
        </div>

        <div id="hukuman-siswa-edit-fields" class="hidden space-y-4">
            <p class="text-sm text-slate-500">Mengubah catatan hukuman yang sudah diterbitkan.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="hukuman-siswa-sanction">Hukuman Diterapkan</label>
                <input type="text"
                       name="sanction"
                       id="hukuman-siswa-sanction"
                       class="form-input"
                       list="hukuman-sanction-suggestions"
                       maxlength="255"
                       placeholder="Tulis hukuman atau pilih saran"
                       required>
                <datalist id="hukuman-sanction-suggestions">
                    @foreach($sanctionOptions as $code => $label)
                        <option value="{{ $label }}"></option>
                        <option value="{{ $code }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-slate-500">Rekomendasi hanya mengisi saran; teks bebas diizinkan.</p>
            </div>
            <div>
                <label class="form-label" for="hukuman-siswa-status">Status</label>
                <select name="status" id="hukuman-siswa-status" class="form-input" data-s2 required>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === \App\Support\HukumanStatus::DITERBITKAN)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="hukuman-siswa-tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="hukuman-siswa-tanggal" class="form-input" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div>
            <label class="form-label" for="hukuman-siswa-keterangan">Keterangan</label>
            <textarea name="keterangan" id="hukuman-siswa-keterangan" class="form-input" rows="3" maxlength="2000" placeholder="Catatan tambahan (opsional)"></textarea>
        </div>

        <div id="hukuman-siswa-modal-existing-bukti" class="hidden">
            <label class="form-label">Bukti Saat Ini</label>
            <div class="bukti-existing-list space-y-1.5"></div>
        </div>

        <div>
            <label class="form-label">Tambah Bukti Baru</label>
            <div class="bukti-upload-zone" data-bukti-upload data-max-files="10">
                <input type="file" name="bukti[]" id="hukuman-siswa-bukti" multiple accept="image/jpeg,image/png,image/webp,.pdf" class="hidden">
                <div class="bukti-upload-zone__placeholder" onclick="document.getElementById('hukuman-siswa-bukti').click()">
                    <x-icon name="upload" size="md" class="text-slate-400" />
                    <p class="text-sm text-slate-500 dark:text-slate-400">Klik atau seret file ke sini</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Gambar maks 1 MB, PDF maks 2 MB. Maks 10 file.</p>
                </div>
                <div class="bukti-upload-zone__preview hidden"></div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="hukuman-siswa-modal">Batal</button>
            <button type="submit" class="btn-primary"><x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan</button>
        </div>
    </form>
</x-modal>
