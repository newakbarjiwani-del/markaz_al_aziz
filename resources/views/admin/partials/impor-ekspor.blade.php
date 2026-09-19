@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.partials.spreadsheet-import-assets')

<div class="grid gap-6 lg:grid-cols-2">
    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ekspor Data</h2>
        <p class="mb-4 text-sm text-slate-500">{{ $exportDescription }}</p>
        <a href="{{ $exportRoute ?? '#' }}" class="btn-secondary" @if(!isset($exportRoute)) onclick="window.mainTable && document.getElementById('export-excel')?.click(); return false;" @endif>
            <x-icon name="file-spreadsheet" size="sm" class="mr-1" /> {{ $exportButtonLabel ?? 'Ekspor Excel' }}
        </a>
    </div>
    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Import Data</h2>
        <p class="mb-4 text-sm text-slate-500">{{ $importHint ?? 'Unggah file Excel (.xlsx atau .xls) sesuai template kolom data.' }}</p>
        @if(isset($templateColumns))
            <div class="import-template-columns mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Kolom template</p>
                <p class="mt-1 text-sm text-slate-500">{{ implode(' · ', $templateColumns) }}</p>
                <p class="mt-2 text-xs text-slate-400">Pratinjau disimpan sebagai data import sementara selama 2 jam.</p>
            </div>
        @endif
        @isset($templateRoute)
            <a href="{{ $templateRoute }}" class="btn-secondary mb-4 inline-flex">
                <x-icon name="download" size="sm" class="mr-1" /> Unduh Template Excel
            </a>
        @endisset
        <form id="spreadsheet-import-form"
              class="space-y-4"
              novalidate
              data-spreadsheet-import-form
              data-import-type="{{ $importType ?? 'siswa' }}"
              data-preview-route="{{ $previewRoute ?? '' }}"
              data-confirm-route="{{ $confirmRoute ?? '' }}"
              data-clear-preview-route="{{ $clearPreviewRoute ?? '' }}"
              data-cached-preview-route="{{ $cachedPreviewRoute ?? '' }}">
            @csrf
            @if(!empty($requiresSchoolSelection))
                @include('admin.partials.filters.sekolah-select', [
                    'schools' => $schools,
                    'allowAll' => false,
                    'required' => true,
                    'label' => 'Sekolah Tujuan Import',
                    'id' => 'filter-sekolah',
                ])
            @elseif(!empty($schoolOptional) && !empty($schools))
                @include('admin.partials.filters.sekolah-select', [
                    'schools' => $schools,
                    'allowAll' => false,
                    'required' => false,
                    'label' => 'Sekolah (opsional — kosong = katalog global)',
                    'id' => 'filter-sekolah',
                ])
            @endif
            <x-spreadsheet-import-upload />
            <button type="button" class="btn-primary" data-spreadsheet-import-preview>
                <x-icon name="eye" size="sm" class="mr-1" /> Buat Pratinjau
            </button>
        </form>
    </div>
</div>

<div id="spreadsheet-import-preview-panel"
     class="card mt-6 hidden p-6"
     data-spreadsheet-import-preview-panel
     data-import-type="{{ $importType ?? 'siswa' }}"
     data-siswa-import-review="{{ !empty($siswaImportReview) ? '1' : '0' }}">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pratinjau Import</h2>
            <p class="mt-1 text-sm text-slate-500" data-spreadsheet-import-meta>Belum ada pratinjau.</p>
        </div>
        <button type="button" class="btn-secondary text-sm" data-spreadsheet-import-clear>
            <x-icon name="trash" size="sm" class="mr-1" /> Hapus Data Import Sementara
        </button>
    </div>

    <form class="import-preview-actions mb-4 flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40"
          data-spreadsheet-import-confirm-form
          novalidate>
        @csrf
        @if(!empty($requiresSchoolSelection) || !empty($schoolOptional))
            <input type="hidden" name="sekolah_id" data-spreadsheet-import-sekolah-hidden>
        @endif
        <div class="min-w-[16rem] flex-1">
            <label class="form-label">Metode Penyimpanan</label>
            <select name="method" id="import-method" class="form-input" required data-spreadsheet-import-method>
                <option value="create_and_update">Tambah dan perbarui data</option>
                <option value="create_only">Hanya tambah data baru</option>
                <option value="update_only">Hanya perbarui data existing</option>
            </select>
        </div>
        <button type="submit" class="btn-primary" data-spreadsheet-import-confirm disabled>
            <x-icon name="check" size="sm" class="mr-1" /> Setujui & Proses Import
        </button>
    </form>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" data-spreadsheet-import-summary></div>

    @if(!empty($siswaImportReview))
        <div data-siswa-import-review class="hidden space-y-6">
            <section class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                <h3 class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Data Baru (Akan Ditambahkan)</h3>
                <div class="mt-3 overflow-x-auto rounded-lg border border-emerald-200/80 bg-white dark:border-emerald-900/60 dark:bg-slate-900/60">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Baris</th>
                                <th class="px-4 py-3 font-medium">NIS</th>
                                <th class="px-4 py-3 font-medium">Nama</th>
                                <th class="px-4 py-3 font-medium">Kelas</th>
                                <th class="px-4 py-3 font-medium">Tempat Lahir</th>
                                <th class="px-4 py-3 font-medium">Tanggal Lahir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800" data-siswa-import-create-rows></tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-300">Data Existing (Akan Diupdate)</h3>
                <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">Perbandingan sebelum dan sesudah perubahan.</p>
                <div class="mt-3 space-y-3" data-siswa-import-update-rows></div>
            </section>

            <section class="rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                <h3 class="text-sm font-semibold text-rose-700 dark:text-rose-300">Data Gagal (Tidak Dapat Diproses)</h3>
                <div class="mt-3 overflow-x-auto rounded-lg border border-rose-200/80 bg-white dark:border-rose-900/60 dark:bg-slate-900/60">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Baris</th>
                                <th class="px-4 py-3 font-medium">NIS</th>
                                <th class="px-4 py-3 font-medium">Nama</th>
                                <th class="px-4 py-3 font-medium">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800" data-siswa-import-invalid-rows></tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif

    <div data-generic-import-review @class(['overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800', 'hidden' => !empty($siswaImportReview)])>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                <tr>
                    @foreach(($previewColumns ?? ['Baris', 'Status', 'Keterangan']) as $column)
                        <th class="px-4 py-3 font-medium">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody data-spreadsheet-import-rows class="divide-y divide-slate-200 dark:divide-slate-800"></tbody>
        </table>
        <div class="import-preview-pagination hidden" data-spreadsheet-import-pagination>
            <p class="import-preview-pagination__info text-sm text-slate-500" data-spreadsheet-import-page-info></p>
            <div class="import-preview-pagination__controls">
                <label class="import-preview-pagination__size">
                    <span class="text-sm text-slate-500">Baris</span>
                    <select class="form-input form-input-sm" data-spreadsheet-import-page-size>
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>
                <button type="button" class="btn-secondary btn-sm" data-spreadsheet-import-prev disabled>Sebelumnya</button>
                <button type="button" class="btn-secondary btn-sm" data-spreadsheet-import-next disabled>Berikutnya</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/spreadsheet-import-preview.js') }}?v=9"></script>
@endpush
