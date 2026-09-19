@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $columnOptions = array_fill(0, 8, ['orderable' => true, 'searchable' => true, 'exportable' => true]);
    $columnOptions[8] = ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true];
    $defaultBerlakuMulai = now()->format('Y-m-d');
    $defaultBerlakuSampai = now()->addYear()->format('Y-m-d');
@endphp

<div class="alert alert-info mb-4 text-sm">
    Siswa dapat memiliki beberapa potongan aktif sekaligus. Semua potongan yang memenuhi syarat diterapkan berurutan sesuai <strong>Urutan</strong> di Katalog Potongan hingga tagihan mencapai minimum Rp 1.000.
</div>

<x-admin.datatable-page
    title="Potongan Siswa"
    subtitle="Penugasan potongan per siswa dengan nilai berbeda per jenis tagihan"
    :ajax-url="route('admin.keuangan.potongan-siswa.data')"
    export-filename="Potongan Siswa"
    :columns="['NIS', 'Nama', 'Kelas', 'Jenis Potongan', 'Nilai', 'Masa Berlaku', 'Kuota', 'Status', 'Aksi']"
    :column-options="$columnOptions">
    @can('potongan-tagihan.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="potongan-siswa-modal"
                    data-form-reset="potongan-siswa-form"
                    data-store-url="{{ route('admin.keuangan.potongan-siswa.store') }}"
                    data-modal-title="Tambah Potongan Siswa">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Potongan
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @if(! $operatorSchoolId)
                @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah_id'])
            @endif
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas_id'])
            <div>
                <label class="form-label" for="filter-jenis-potongan">Jenis Potongan</label>
                <select name="jenis_potongan_id" id="filter-jenis-potongan" class="form-input">
                    <option value="">Semua</option>
                    @foreach($jenisPotonganList as $jenis)
                        <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="filter-status">Status</label>
                <select name="status" id="filter-status" class="form-input">
                    <option value="">Semua</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="habis">Habis</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="potongan-siswa-modal" title="Tambah / Ubah Potongan Siswa" size="full">
    <form id="potongan-siswa-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.potongan-siswa.store') }}"
          data-reload-table
          data-close-modal="potongan-siswa-modal"
          action="{{ route('admin.keuangan.potongan-siswa.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <input type="hidden" name="_siswa_label" id="potongan-siswa-label-hidden" value="">
        <input type="hidden" name="_method" id="potongan-siswa-method" value="POST">

        <section class="form-section">
            <h4 class="form-section__title">Siswa & Jenis Potongan</h4>
            <div class="form-section__body grid gap-4 lg:grid-cols-2">
                <div id="potongan-siswa-select-wrap">
                    <x-siswa-select id="potongan-siswa-siswa" name="siswa_id" label="Siswa" required />
                </div>
                <div id="potongan-siswa-readonly" class="hidden">
                    <label class="form-label" for="potongan-siswa-readonly-label">Siswa</label>
                    <input type="text" id="potongan-siswa-readonly-label" class="form-input" readonly>
                </div>
                <div class="lg:col-span-2 lg:grid lg:grid-cols-2 lg:gap-4">
                    <div>
                        <label class="form-label" for="potongan-siswa-jenis">Jenis Potongan</label>
                        <select name="jenis_potongan_id" id="potongan-siswa-jenis" class="form-input" required>
                            <option value="">Pilih jenis potongan</option>
                            @foreach($jenisPotonganList as $jenis)
                                <option value="{{ $jenis->id }}"
                                        data-tipe="{{ $jenis->tipe_default }}"
                                        data-nilai="{{ $jenis->nilai_default }}"
                                        data-keterangan="{{ $jenis->keterangan }}">{{ $jenis->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="potongan-jenis-katalog-hint"
                         class="alert alert-info hidden self-end text-sm"
                         role="status"
                         aria-live="polite">
                        <span class="text-muted">Default katalog:</span>
                        <strong id="potongan-jenis-katalog-hint-text"></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Default Penugasan</h4>
            <p class="form-section__desc">
                Nilai baku dari katalog jenis potongan. Baris jenis tagihan dapat memakai default ini untuk <strong>nilai potongan</strong>
                dan/atau <strong>kuota pemakaian</strong>, atau diisi khusus per baris.
            </p>
            <div class="form-section__body space-y-4">
                <div class="grid gap-4 lg:grid-cols-3">
                    <div>
                        <label class="form-label" for="potongan-siswa-tipe">Tipe Default</label>
                        <select name="tipe" id="potongan-siswa-tipe" class="form-input" required>
                            <option value="percent">Persen (% dari sisa tagihan)</option>
                            <option value="fixed">Nominal tetap (rupiah)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="potongan-siswa-nilai">Nilai Default</label>
                        <input type="text"
                               name="nilai"
                               id="potongan-siswa-nilai"
                               class="form-input onlyNumber"
                               inputmode="numeric"
                               min="1"
                               max="100"
                               placeholder="1–100"
                               autocomplete="off"
                               required>
                        <p class="mt-1 text-xs text-slate-500" id="potongan-siswa-nilai-hint">Persen: 1–100. Isi 100 untuk membebaskan tagihan (sisa Rp 0).</p>
                    </div>
                    <div>
                        <label class="form-label" for="potongan-siswa-max">Kuota Default</label>
                        <input type="number" name="max_pemakaian" id="potongan-siswa-max" class="form-input" min="1" max="9999" value="12" required>
                        <p class="mt-1 text-xs text-slate-500">Maks. pemakaian per jenis tagihan jika baris memakai default kuota.</p>
                    </div>
                </div>
                <div id="potongan-default-preview"
                     class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
                    <span class="text-muted">Pratinjau default</span> (contoh tagihan Rp 300.000):
                    potongan <strong id="potongan-default-preview-cut">—</strong>,
                    sisa <strong id="potongan-default-preview-net">—</strong>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Potongan per Jenis Tagihan</h4>
            <p class="form-section__desc">
                Centang jenis tagihan yang menerima potongan. Kolom <strong>Default</strong> / <strong>Def. kuota</strong> memakai nilai default penugasan;
                kosongkan untuk mengisi khusus per jenis tagihan.
            </p>
            <div class="form-section__body space-y-3">
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary btn-sm" id="potongan-bill-apply-default-all">Default nilai & kuota (baris tercentang)</button>
                </div>

                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                    <table class="min-w-full w-full text-sm potongan-bill-cuts-table">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-2.5 w-12">
                                    <input type="checkbox"
                                           id="potongan-bill-cut-toggle-all"
                                           class="form-checkbox potongan-bill-cut-toggle-all"
                                           aria-label="Centang semua jenis tagihan"
                                           title="Centang / kosongkan semua">
                                </th>
                                <th class="px-4 py-2.5 min-w-[8rem]">Jenis Tagihan</th>
                                <th class="px-4 py-2.5 min-w-[6rem] whitespace-nowrap">Standar</th>
                                <th class="px-4 py-2.5 min-w-[11rem]">Pratinjau</th>
                            </tr>
                        </thead>
                        <tbody id="potongan-bill-cuts-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($jenisTagihanList as $index => $jenis)
                                <tr class="potongan-bill-cut-row potongan-bill-cut-row--meta"
                                    data-jenis-id="{{ $jenis->id }}"
                                    data-default-amount="{{ (int) $jenis->default_amount }}"
                                    data-is-spp="{{ $jenis->is_spp ? '1' : '0' }}">
                                    <td class="px-4 py-2.5 align-top" rowspan="2">
                                        <input type="hidden" name="bill_cuts[{{ $index }}][enabled]" value="0">
                                        <input type="checkbox"
                                               name="bill_cuts[{{ $index }}][enabled]"
                                               value="1"
                                               class="form-checkbox potongan-bill-cut-enabled mt-1">
                                        <input type="hidden"
                                               name="bill_cuts[{{ $index }}][jenis_tagihan_id]"
                                               value="{{ $jenis->id }}">
                                    </td>
                                    <td class="px-4 py-2.5 align-top">
                                        <span class="font-medium text-slate-800 dark:text-slate-100">{{ $jenis->name }}</span>
                                        @if($jenis->is_spp)
                                            <span class="badge badge-blue ml-1 align-middle text-[0.65rem]">SPP</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top text-xs text-slate-500 whitespace-nowrap">
                                        Rp {{ number_format((float) $jenis->default_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2.5 align-top text-xs text-slate-500 potongan-bill-cut-preview" rowspan="2">—</td>
                                </tr>
                                <tr class="potongan-bill-cut-row potongan-bill-cut-row--fields"
                                    data-jenis-id="{{ $jenis->id }}">
                                    <td colspan="2" class="px-4 py-3 align-top bg-slate-50/80 dark:bg-slate-900/30">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="potongan-bill-cut-field-group">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Potongan</p>
                                                <div class="mt-2 flex flex-wrap items-end gap-2">
                                                    <div class="min-w-[5.5rem]">
                                                        <input type="hidden" name="bill_cuts[{{ $index }}][use_default]" value="0">
                                                        <label class="form-check">
                                                            <input type="checkbox"
                                                                   name="bill_cuts[{{ $index }}][use_default]"
                                                                   value="1"
                                                                   class="form-checkbox potongan-bill-cut-use-default"
                                                                   checked
                                                                   disabled>
                                                            <span class="text-xs">Def. nilai</span>
                                                        </label>
                                                    </div>
                                                    <div class="min-w-[7rem] flex-1">
                                                        <label class="form-label text-xs">Tipe</label>
                                                        <select name="bill_cuts[{{ $index }}][tipe]"
                                                                class="form-input potongan-bill-cut-tipe"
                                                                disabled>
                                                            <option value="percent">Persen</option>
                                                            <option value="fixed">Nominal</option>
                                                        </select>
                                                    </div>
                                                    <div class="min-w-[6rem] flex-1">
                                                        <label class="form-label text-xs">Nilai</label>
                                                        <input type="text"
                                                               name="bill_cuts[{{ $index }}][nilai]"
                                                               class="form-input potongan-bill-cut-nilai onlyNumber"
                                                               inputmode="numeric"
                                                               min="1"
                                                               max="100"
                                                               placeholder="1–100"
                                                               autocomplete="off"
                                                               disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="potongan-bill-cut-field-group">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kuota pemakaian</p>
                                                <div class="mt-2 flex flex-wrap items-end gap-2">
                                                    <div class="min-w-[5.5rem]">
                                                        <input type="hidden" name="bill_cuts[{{ $index }}][use_default_max]" value="0">
                                                        <label class="form-check">
                                                            <input type="checkbox"
                                                                   name="bill_cuts[{{ $index }}][use_default_max]"
                                                                   value="1"
                                                                   class="form-checkbox potongan-bill-cut-use-default-max"
                                                                   checked
                                                                   disabled>
                                                            <span class="text-xs">Def. kuota</span>
                                                        </label>
                                                    </div>
                                                    <div class="min-w-[5rem] flex-1">
                                                        <label class="form-label text-xs">Maks.</label>
                                                        <input type="text"
                                                               name="bill_cuts[{{ $index }}][max_pemakaian]"
                                                               class="form-input potongan-bill-cut-max onlyNumber"
                                                               inputmode="numeric"
                                                               pattern="[0-9]*"
                                                               min="1"
                                                               max="9999"
                                                               autocomplete="off"
                                                               disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Masa Berlaku</h4>
            <div class="form-section__body grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="form-label" for="potongan-siswa-mulai">Berlaku Mulai</label>
                    <input type="date" name="berlaku_mulai" id="potongan-siswa-mulai" class="form-input" value="{{ $defaultBerlakuMulai }}" required>
                </div>
                <div>
                    <label class="form-label" for="potongan-siswa-sampai">Berlaku Sampai</label>
                    <input type="date" name="berlaku_sampai" id="potongan-siswa-sampai" class="form-input" value="{{ $defaultBerlakuSampai }}" required>
                </div>
                <div>
                    <label class="form-label" for="potongan-siswa-status">Status</label>
                    <select name="status" id="potongan-siswa-status" class="form-input" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="habis">Habis</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="form-label" for="potongan-siswa-keterangan">Keterangan</label>
                    <textarea name="keterangan" id="potongan-siswa-keterangan" class="form-input" rows="2" maxlength="1000" placeholder="Opsional — catatan internal"></textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" class="btn-secondary" data-modal-close="potongan-siswa-modal">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/potongan-form.js') }}?v=8"></script>
@endpush
