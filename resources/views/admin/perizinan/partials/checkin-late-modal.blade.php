@props([
    'katalogOptions' => [],
])

@php
    $katalogPayload = collect($katalogOptions)->map(fn ($jenis) => [
        'id' => $jenis->id,
        'level' => $jenis->level,
        'nama' => $jenis->nama,
        'bidang' => $jenis->bidang,
        'point' => (int) $jenis->point,
        'label' => $jenis->nama.($jenis->bidang ? ' — '.$jenis->bidang : ''),
    ])->values();
@endphp

<x-modal id="perizinan-checkin-late-modal" title="Catat Kembali — Terlambat" size="lg">
    <form id="perizinan-checkin-late-form" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="checkin_url" id="perizinan-checkin-late-url" value="">
        <input type="hidden" name="submit_mode" id="perizinan-checkin-late-mode" value="checkin">

        <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
            <p class="font-semibold" id="perizinan-checkin-late-headline">Siswa kembali melebihi batas waktu izin.</p>
            <p class="mt-1 text-xs opacity-90" id="perizinan-checkin-late-detail"></p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-900/40">
            <div class="font-medium text-slate-900 dark:text-white" id="perizinan-checkin-late-siswa">-</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Batas kembali: <span id="perizinan-checkin-late-batas">-</span>
            </div>
        </div>

        <script type="application/json" data-pelanggaran-katalog-for="perizinan-checkin-late-form">@json($katalogPayload)</script>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="perizinan-checkin-late-level">Tingkat Pelanggaran <span class="text-rose-500">*</span></label>
                <select id="perizinan-checkin-late-level"
                        class="form-input"
                        data-s2
                        data-pelanggaran-level
                        data-placeholder="Pilih tingkat"
                        required>
                    <option value="">Pilih tingkat</option>
                    @foreach(\App\Support\PelanggaranLevel::labels() as $level => $label)
                        <option value="{{ $level }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="perizinan-checkin-late-jenis">Jenis Pelanggaran <span class="text-rose-500">*</span></label>
                <select name="pelanggaran_jenis_pelanggaran_id" id="perizinan-checkin-late-jenis" class="form-input"
                        data-s2
                        data-pelanggaran-jenis
                        data-placeholder="Pilih tingkat dulu"
                        required>
                    <option value="">Pilih tingkat dulu</option>
                </select>
            </div>
        </div>

        <div>
            <label class="form-label" for="perizinan-checkin-late-judul">Judul Pelanggaran <span class="text-rose-500">*</span></label>
            <input type="text" name="pelanggaran_judul" id="perizinan-checkin-late-judul" class="form-input" maxlength="255" required>
        </div>

        <div>
            <label class="form-label" for="perizinan-checkin-late-keterangan">Keterangan</label>
            <textarea name="pelanggaran_keterangan" id="perizinan-checkin-late-keterangan" class="form-input" rows="3" maxlength="1000" placeholder="Keterangan otomatis dari data perizinan (bisa disesuaikan)"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="form-label" for="perizinan-checkin-late-tanggal">Tanggal Pelanggaran</label>
                <input type="date" name="pelanggaran_tanggal" id="perizinan-checkin-late-tanggal" class="form-input" max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="form-label" for="perizinan-checkin-late-point">Point</label>
                <input type="number" name="pelanggaran_point" id="perizinan-checkin-late-point" class="form-input" min="0" max="1000" required readonly tabindex="-1">
            </div>
        </div>

        <p class="text-xs text-slate-500 dark:text-slate-400">Pelanggaran akan masuk ke modul <strong>Prestasi &amp; Pelanggaran → Pelanggaran Siswa</strong> dan rekap point siswa.</p>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-close-modal="perizinan-checkin-late-modal">Batal</button>
            <button type="submit" class="btn-primary">Catat Kembali &amp; Simpan Pelanggaran</button>
        </div>
    </form>
</x-modal>
