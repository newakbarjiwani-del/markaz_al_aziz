@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="kirim-tagihan-wa-config"
     data-build-url="{{ route('admin.keuangan.kirim-tagihan-wa.build') }}"
     data-template-options-url="{{ route('admin.keuangan.template-pesan-tagihan.options') }}"></div>

@if($templateCount === 0)
    <div class="alert alert-warning mb-4">
        <div class="flex items-start gap-2">
            <x-icon name="alert-triangle" size="sm" class="mt-0.5 shrink-0" />
            <p class="text-sm">Belum ada template pesan aktif. <a href="{{ route('admin.keuangan.template-pesan-tagihan.index') }}" class="font-medium underline">Kelola template pesan WA</a> terlebih dahulu.</p>
        </div>
    </div>
@endif

<div id="tagihan-wa-selection-bar" class="card mb-4 hidden p-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-900 dark:text-white" id="tagihan-wa-selection-summary">Belum ada tagihan dipilih</p>
            <p class="text-muted mt-1 text-xs" id="tagihan-wa-selection-phone"></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="tagihan-wa-clear-btn" class="btn-secondary btn-sm">Bersihkan</button>
            <button type="button" id="tagihan-wa-preview-btn" class="btn-primary btn-sm" disabled>
                <x-icon name="brand-whatsapp" size="sm" class="mr-1" /> Preview & Kirim WA
            </button>
        </div>
    </div>
</div>

<x-admin.datatable-page
    title="Tagihan Belum Lunas"
    subtitle="Centang tagihan satu siswa (klik baris atau checkbox), lalu kirim pengingat via wa.me ke nomor orang tua/wali"
    :ajax-url="route('admin.keuangan.kirim-tagihan-wa.data')"
    :show-export="false"
    :columns="['Pilih', 'NIS', 'Nama', 'Kelas', 'Jenis', 'Periode', 'Sisa', 'Jatuh Tempo']"
    :default-order="[[2, 'asc']]"
    :column-options="[
        0 => ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true, 'className' => 'dt-col-check dt-center'],
        2 => ['html' => true],
        7 => ['html' => true],
    ]">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @if(! $operatorSchoolId)
                @include('admin.partials.filters.sekolah-select', ['schools' => $schools, 'selected' => request('sekolah_id'), 'id' => 'filter-sekolah_id'])
            @endif
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas_id'])
            @include('admin.partials.filters.student-search', ['id' => 'filter-q', 'value' => request('q')])
            <div>
                <label class="form-label" for="filter-overdue">Jatuh Tempo</label>
                <select name="overdue_only" id="filter-overdue" class="form-input">
                    <option value="">Semua belum lunas</option>
                    <option value="1">Hanya lewat jatuh tempo</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

<div class="mt-2 flex items-center gap-2 px-1">
    <input type="checkbox" id="tagihan-wa-select-all" class="form-checkbox">
    <label for="tagihan-wa-select-all" class="text-sm text-slate-600 dark:text-slate-300">Pilih semua tagihan di halaman ini (siswa & nomor WA harus sama)</label>
</div>
@endsection

@push('modals')
<x-modal id="tagihan-wa-preview-modal" title="Preview Pesan WhatsApp" size="lg">
    <div class="space-y-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
            <p><strong>Siswa:</strong> <span id="tagihan-wa-preview-siswa">-</span></p>
            <p class="mt-1"><strong>Nomor:</strong> <span id="tagihan-wa-preview-phone">-</span></p>
            <p class="mt-1"><strong>Total sisa:</strong> <span id="tagihan-wa-preview-total">-</span></p>
            <p class="mt-1"><strong>Template:</strong> <span id="tagihan-wa-preview-template">-</span></p>
        </div>
        <div>
            <label class="form-label" for="tagihan-wa-template-mode">Pemilihan template</label>
            <select id="tagihan-wa-template-mode" class="form-input">
                <option value="random">Acak dari kategori (disarankan)</option>
                <option value="manual">Pilih template manual</option>
            </select>
        </div>
        <div id="tagihan-wa-template-picker-wrap" class="hidden">
            <label class="form-label" for="tagihan-wa-template-id">Template</label>
            <select id="tagihan-wa-template-id" class="form-input">
                <option value="">Memuat…</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="tagihan-wa-preview-message">Isi pesan</label>
            <textarea id="tagihan-wa-preview-message" class="form-input min-h-[240px] font-mono text-sm" readonly></textarea>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="tagihan-wa-preview-modal" class="btn-secondary">Tutup</button>
            <button type="button" id="tagihan-wa-open-btn" class="btn-primary">
                <x-icon name="brand-whatsapp" size="sm" class="mr-1" /> Buka WhatsApp
            </button>
        </div>
    </div>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/tagihan-wa-reminder.js') }}?v=2"></script>
@endpush
