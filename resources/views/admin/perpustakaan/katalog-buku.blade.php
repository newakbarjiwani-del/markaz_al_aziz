@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $columns = ['Kode', 'ISBN', 'Judul', 'Pengarang', 'Penerbit', 'Tahun', 'Jumlah', 'Baik', 'Tersedia', 'Rating'];
    $columnOptions = [];
    if ($canManage ?? false) {
        $columns[] = 'Aksi';
        $columnOptions[10] = ['html' => true, 'orderable' => false, 'searchable' => false, 'exportable' => false];
    }
@endphp

<x-admin.datatable-page
    title="Katalog Buku"
    subtitle="Kelola inventaris buku perpustakaan"
    :ajax-url="$ajaxUrl"
    :columns="$columns"
    :column-options="$columnOptions"
    export-filename="katalog-buku">
    @can('library.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="buku-modal"
                    data-form-reset="buku-form"
                    data-store-url="{{ $storeUrl }}"
                    data-modal-title="Tambah Buku">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Buku
            </button>
        </x-slot:actions>
    @endcan
</x-admin.datatable-page>
@endsection

@push('modals')
@canany(['library.create', 'library.update'])
<x-modal id="buku-modal" title="Tambah / Ubah Buku">
    <form id="buku-form"
          data-fetch-form
          data-default-action="{{ $storeUrl }}"
          data-reload-table
          data-close-modal="buku-modal"
          action="{{ $storeUrl }}"
          method="POST"
          class="space-y-5">
        @csrf

        <section class="form-section">
            <h4 class="form-section__title">Identitas Buku</h4>
            <div class="form-section__body space-y-4">
                @if($showSchoolSelect ?? false)
                    <div>
                        <label class="form-label" for="buku-sekolah">Sekolah <span class="text-muted text-xs font-normal">(kosong = katalog global)</span></label>
                        <select name="sekolah_id" id="buku-sekolah" class="form-input">
                            <option value="">Katalog Global</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="buku-kode">Kode Buku</label>
                        <input type="text" name="kode_buku" id="buku-kode" class="form-input" maxlength="50" placeholder="Opsional">
                    </div>
                    <div>
                        <label class="form-label" for="buku-isbn">ISBN</label>
                        <input type="text" name="isbn" id="buku-isbn" class="form-input" maxlength="32" placeholder="Opsional, unik">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="buku-judul">Judul</label>
                    <input type="text" name="judul" id="buku-judul" class="form-input" required maxlength="255" placeholder="Judul buku">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="buku-pengarang">Pengarang</label>
                        <input type="text" name="pengarang" id="buku-pengarang" class="form-input" maxlength="255">
                    </div>
                    <div>
                        <label class="form-label" for="buku-penerbit">Penerbit</label>
                        <input type="text" name="penerbit" id="buku-penerbit" class="form-input" maxlength="255">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="buku-kategori">Kategori</label>
                        <input type="text" name="kategori" id="buku-kategori" class="form-input" maxlength="100">
                    </div>
                    <div>
                        <label class="form-label" for="buku-tahun">Tahun Terbit</label>
                        <input type="number" name="tahun_terbit" id="buku-tahun" class="form-input" min="1900" max="2100" placeholder="YYYY">
                    </div>
                    <div>
                        <label class="form-label" for="buku-cetak">Cetak Ke</label>
                        <input type="number" name="cetak_ke" id="buku-cetak" class="form-input" min="1" max="999" value="1">
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Inventaris & Keadaan</h4>
            <p class="form-section__desc">Jumlah keadaan baik + rusak ringan + rusak berat harus sama dengan jumlah eksemplar.</p>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="buku-jumlah">Jumlah Eksemplar</label>
                    <input type="number" name="jumlah" id="buku-jumlah" class="form-input" min="1" max="99999" required value="1">
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label" for="buku-baik">Keadaan Baik</label>
                        <input type="number" name="keadaan_baik" id="buku-baik" class="form-input" min="0" max="99999" required value="1">
                    </div>
                    <div>
                        <label class="form-label" for="buku-rusak-ringan">Rusak Ringan</label>
                        <input type="number" name="keadaan_rusak_ringan" id="buku-rusak-ringan" class="form-input" min="0" max="99999" required value="0">
                    </div>
                    <div>
                        <label class="form-label" for="buku-rusak-berat">Rusak Berat</label>
                        <input type="number" name="keadaan_rusak_berat" id="buku-rusak-berat" class="form-input" min="0" max="99999" required value="0">
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Metadata Inventaris</h4>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="buku-tanggal-terima">Tanggal Penerimaan</label>
                        <input type="date" name="tanggal_penerimaan" id="buku-tanggal-terima" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="buku-sumber-dana">Sumber Dana</label>
                        <input type="text" name="sumber_dana" id="buku-sumber-dana" class="form-input" maxlength="255">
                    </div>
                </div>
                <div>
                    <label class="form-label" for="buku-keterangan">Keterangan</label>
                    <textarea name="keterangan" id="buku-keterangan" class="form-input" rows="2" maxlength="1000"></textarea>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="buku-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan Buku
            </button>
        </div>
    </form>
</x-modal>
@endcanany
@endpush

@push('scripts')
<script>
(function () {
    var jumlah = document.getElementById('buku-jumlah');
    var baik = document.getElementById('buku-baik');
    if (!jumlah || !baik || jumlah.dataset.syncBound) return;
    jumlah.dataset.syncBound = '1';

    jumlah.addEventListener('change', function () {
        if (document.getElementById('buku-form')?.dataset.method === 'PUT') return;
        var value = Math.max(0, parseInt(jumlah.value || '0', 10) || 0);
        if (!baik.value || baik.dataset.userEdited !== '1') {
            baik.value = String(value);
        }
    });

    baik.addEventListener('input', function () {
        baik.dataset.userEdited = '1';
    });

    document.querySelector('[data-open-modal="buku-modal"]')?.addEventListener('click', function () {
        delete baik.dataset.userEdited;
    });
})();
</script>
@endpush
