@props([
    'jenis' => 'keluar_masuk',
    'defaultPemberiIzin' => auth()->user()?->name ?? '',
])

<x-modal id="perizinan-modal" title="Tambah Perizinan" size="lg">
    <form id="perizinan-form" action="{{ route('admin.perizinan.keluar-masuk.store') }}" method="POST" enctype="multipart/form-data" data-fetch-form data-close-modal="perizinan-modal">
        @csrf
        <input type="hidden" name="_method" value="POST">
        <input type="hidden" name="jenis_perizinan" id="form-jenis-perizinan" value="{{ $jenis }}">

        <div class="space-y-4">
            <div>
                <x-siswa-select name="siswa_id" id="perizinan-siswa-id" label="Siswa / Santri" required />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="tgl_mulai">Waktu Wajib Berangkat / Mulai <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="tgl_mulai" id="tgl_mulai" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="tgl_sampai">Batas Waktu Kembali (Rencana) <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="tgl_sampai" id="tgl_sampai" class="form-input" required>
                </div>
            </div>

            <div>
                <label class="form-label" for="penanggung_jawab">Penanggung Jawab / Penjemput</label>
                <input type="text" name="penanggung_jawab" id="penanggung_jawab" class="form-input" placeholder="Contoh: Ayah Kandung / Paman / Mandiri">
            </div>

            <div>
                <label class="form-label" for="pemberi_izin">Pemberi Izin</label>
                <input type="text" name="pemberi_izin" id="pemberi_izin" class="form-input"
                       value="{{ $defaultPemberiIzin }}"
                       placeholder="Nama petugas / guru / satpam yang memberi izin"
                       data-default-pemberi-izin="{{ $defaultPemberiIzin }}">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Default: akun Anda yang sedang login. Bisa diganti jika izin diberikan atas nama orang lain.</p>
            </div>

            <div>
                <label class="form-label" for="alasan">Alasan Perizinan <span class="text-rose-500">*</span></label>
                <textarea name="alasan" id="alasan" rows="3" class="form-input" placeholder="Jelaskan alasan izin secara rinci..." required></textarea>
            </div>

            <div>
                <label class="form-label" for="perizinan-file">Upload Bukti Dokumen / Surat (Opsional)</label>
                <input type="file" name="file" id="perizinan-file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Format: PDF, JPG, PNG, WEBP (maks 1MB). File akan disimpan di <code>public/perizinan/{{ $jenis }}</code>.</p>
                <div id="file-current-container" class="mt-2 hidden rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs dark:border-slate-800 dark:bg-slate-900/50">
                    <span class="text-slate-500 dark:text-slate-400">File tersimpan saat ini: </span>
                    <a href="#" id="file-current-link" target="_blank" class="font-medium text-primary-600 hover:underline dark:text-primary-400"></a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="status">Status Perizinan</label>
                    <select name="status" id="status" class="form-select">
                        <option value="disetujui" selected>Disetujui</option>
                        <option value="pending">Menunggu Persetujuan</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="kembali">Sudah Kembali</option>
                        <option value="terlambat">Terlambat</option>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="tgl_kembali_aktual">Waktu Kembali Aktual (Opsional)</label>
                    <input type="datetime-local" name="tgl_kembali_aktual" id="tgl_kembali_aktual" class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label" for="catatan">Catatan Tambahan</label>
                <textarea name="catatan" id="catatan" rows="2" class="form-input" placeholder="Catatan petugas / catatan khusus (opsional)"></textarea>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-close-modal="perizinan-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan Data</button>
        </div>
    </form>
</x-modal>
