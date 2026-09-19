@props([
    'type' => 'prestasi',
    'context' => 'siswa',
    'katalogOptions' => [],
    'storeUrl' => null,
    'siswaLookupUrl' => null,
    'siswaLookupResolveUrl' => null,
    'guruLookupUrl' => null,
    'guruLookupResolveUrl' => null,
])

@php
    $isPrestasi = $type === 'prestasi';
    $isSiswa = $context === 'siswa';
    $modalId = "{$type}-{$context}-modal";
    $formId = "{$type}-{$context}-form";
    $entityLabel = $isSiswa ? 'Siswa' : 'Guru';
    $entityField = $isSiswa ? 'siswa_id' : 'guru_id';
    $title = $isPrestasi ? 'Prestasi' : 'Pelanggaran';
    $resolvedStoreUrl = $storeUrl ?? route("admin.prestasi-pelanggaran.{$type}-{$context}.store");
    $resolvedSiswaLookupUrl = $siswaLookupUrl ?? route('admin.siswa.lookup');
    $resolvedGuruLookupUrl = $guruLookupUrl ?? route('admin.guru.lookup');
@endphp

<x-modal id="{{ $modalId }}" title="Tambah / Ubah {{ $title }} {{ $entityLabel }}" size="lg">
    <form id="{{ $formId }}"
          data-fetch-form
          data-reload-table
          data-close-modal="{{ $modalId }}"
          data-default-action="{{ $resolvedStoreUrl }}"
          action="{{ $resolvedStoreUrl }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-5">
        @csrf

        <div class="space-y-4">
            @if($isSiswa)
                <div id="{{ $modalId }}-siswa-create-wrap">
                    <label class="form-label" for="{{ $modalId }}-siswa-ids">{{ $entityLabel }} <span class="text-xs font-normal text-slate-500">(bisa pilih lebih dari satu)</span></label>
                    <select name="siswa_ids[]" id="{{ $modalId }}-siswa-ids" class="form-input" multiple data-ajax-select data-allow-multiple="1" data-url="{{ $resolvedSiswaLookupUrl }}" data-resolve-url="{{ $siswaLookupResolveUrl ?? url('admin/siswa/lookup') }}" required></select>
                </div>
                <div id="{{ $modalId }}-siswa-edit-wrap" class="hidden">
                    <label class="form-label" for="{{ $modalId }}-{{ $entityField }}">{{ $entityLabel }}</label>
                    <select name="{{ $entityField }}" id="{{ $modalId }}-{{ $entityField }}" class="form-input" data-ajax-select data-url="{{ $resolvedSiswaLookupUrl }}" data-resolve-url="{{ $siswaLookupResolveUrl ?? url('admin/siswa/lookup') }}"></select>
                </div>
            @else
                <div>
                    <label class="form-label" for="{{ $modalId }}-{{ $entityField }}">{{ $entityLabel }}</label>
                    <select name="{{ $entityField }}" id="{{ $modalId }}-{{ $entityField }}" class="form-input" data-ajax-select data-url="{{ $resolvedGuruLookupUrl }}" required>
                    </select>
                </div>
            @endif

            @if($isPrestasi)
            @php
                $prestasiKatalogPayload = collect($katalogOptions)->map(fn ($jenis) => [
                    'id' => $jenis->id,
                    'nama' => $jenis->nama,
                    'bidang' => $jenis->bidang,
                    'point' => (int) $jenis->point,
                    'label' => $jenis->nama.($jenis->bidang ? ' — '.$jenis->bidang : ''),
                ])->values();
            @endphp
            <script type="application/json" data-prestasi-katalog-for="{{ $formId }}">@json($prestasiKatalogPayload)</script>
            <div>
                <label class="form-label" for="{{ $modalId }}-jenis-prestasi">Jenis Prestasi <span class="text-muted text-xs font-normal">(katalog — opsional)</span></label>
                <select name="jenis_prestasi_id" id="{{ $modalId }}-jenis-prestasi" class="form-input"
                        data-s2
                        data-prestasi-jenis
                        data-placeholder="Pilih dari katalog (opsional)">
                    <option value="">— Manual / tanpa katalog —</option>
                    @foreach($katalogOptions as $jenis)
                        <option value="{{ $jenis->id }}"
                                data-nama="{{ $jenis->nama }}"
                                data-point="{{ $jenis->point }}">
                            {{ $jenis->nama }}{{ $jenis->bidang ? ' — '.$jenis->bidang : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Memilih katalog mengisi judul &amp; point otomatis (judul tetap bisa diubah).</p>
            </div>
            @endif

            @if(!$isPrestasi)
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
            <script type="application/json" data-pelanggaran-katalog-for="{{ $formId }}">@json($katalogPayload)</script>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="{{ $modalId }}-level">Tingkat Pelanggaran <span class="text-muted text-xs font-normal">(wajib)</span></label>
                    <select id="{{ $modalId }}-level"
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
                    <label class="form-label" for="{{ $modalId }}-jenis-pelanggaran">Jenis Pelanggaran <span class="text-muted text-xs font-normal">(katalog — wajib)</span></label>
                    <select name="jenis_pelanggaran_id" id="{{ $modalId }}-jenis-pelanggaran" class="form-input"
                            data-s2
                            data-pelanggaran-jenis
                            data-placeholder="Pilih tingkat dulu"
                            required>
                        <option value="">Pilih tingkat dulu</option>
                    </select>
                </div>
            </div>
            <p class="text-xs text-slate-500">Pilih tingkat (Ringan / Sedang / Berat), lalu jenis dari katalog. Judul otomatis terisi (dapat diubah); point mengikuti katalog.</p>
            @endif

            <div>
                <label class="form-label" for="{{ $modalId }}-judul">Judul</label>
                <input type="text" name="judul" id="{{ $modalId }}-judul" class="form-input" maxlength="255" required>
            </div>

            <div>
                <label class="form-label" for="{{ $modalId }}-keterangan">Keterangan</label>
                <textarea name="keterangan" id="{{ $modalId }}-keterangan" class="form-input" rows="3" maxlength="1000" placeholder="Keterangan singkat (opsional)"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="{{ $modalId }}-tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="{{ $modalId }}-tanggal" class="form-input" max="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" for="{{ $modalId }}-point">Point</label>
                    <input type="number" name="point" id="{{ $modalId }}-point" class="form-input" min="0" max="1000" required
                        @if(!$isPrestasi) readonly tabindex="-1" title="Point mengikuti jenis pelanggaran dari katalog" @endif>
                    @if(!$isPrestasi)
                        <p class="mt-1 text-xs text-slate-500">Point dari katalog — tidak dapat diubah.</p>
                    @endif
                </div>
            </div>

            <div id="{{ $modalId }}-existing-bukti" class="hidden">
                <label class="form-label">Bukti Saat Ini</label>
                <div class="bukti-existing-list space-y-1.5"></div>
            </div>

            <div>
                <label class="form-label">Tambah Bukti Baru</label>
                <div class="bukti-upload-zone" data-bukti-upload data-max-files="10">
                    <input type="file" name="bukti[]" id="{{ $modalId }}-bukti" multiple accept="image/jpeg,image/png,image/webp,.pdf" class="hidden">
                    <div class="bukti-upload-zone__placeholder" onclick="document.getElementById('{{ $modalId }}-bukti').click()">
                        <x-icon name="upload" size="md" class="text-slate-400" />
                        <p class="text-sm text-slate-500 dark:text-slate-400">Klik atau seret file ke sini</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Gambar maks 1 MB, PDF maks 2 MB. Maks 10 file.</p>
                    </div>
                    <div class="bukti-upload-zone__preview hidden"></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="{{ $modalId }}">Batal</button>
            <button type="submit" class="btn-primary"><x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan</button>
        </div>
    </form>
</x-modal>

@if($isSiswa)
@push('scripts')
<script>
(function () {
    var modalId = @json($modalId);
    var formId = @json($formId);

    function setSiswaFieldMode(isEdit) {
        var createWrap = document.getElementById(modalId + '-siswa-create-wrap');
        var editWrap = document.getElementById(modalId + '-siswa-edit-wrap');
        var multi = document.getElementById(modalId + '-siswa-ids');
        var single = document.getElementById(modalId + '-' + @json($entityField));
        if (createWrap) createWrap.classList.toggle('hidden', isEdit);
        if (editWrap) editWrap.classList.toggle('hidden', !isEdit);
        if (multi) multi.required = !isEdit;
        if (single) single.required = isEdit;
    }

    document.addEventListener('modalReset', function (e) {
        if (e.detail?.formId !== formId) return;
        setSiswaFieldMode(false);
    });

    document.addEventListener('edit-record-populated', function (e) {
        if (e.detail?.form?.id !== formId) return;
        setSiswaFieldMode(true);
    });
})();
</script>
@endpush
@endif
