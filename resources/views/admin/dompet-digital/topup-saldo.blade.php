@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="saldo-cashless-page"
     data-show-url="{{ url('admin/dompet-digital/saldo-cashless') }}"
     data-lookup-rfid-url="{{ route('admin.dompet-digital.topup-saldo.lookup-rfid') }}">
    <x-admin.datatable-page
        title="Riwayat Top-up"
        subtitle="Riwayat top-up cashless (METODE TOP UP)"
        :ajax-url="route('admin.dompet-digital.topup-saldo.data')"
        :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Nominal', 'Keterangan', 'Ref', 'Petugas']"
        :default-order="[[0, 'desc']]">
        <x-slot:actions>
            @if($manualSaldoEnabled ?? false)
                <button type="button"
                        class="btn-primary flex-1 sm:flex-none"
                        data-open-modal="topup-cashless-modal"
                        data-form-reset="topup-cashless-form"
                        data-store-url="{{ route('admin.dompet-digital.topup-saldo.store') }}"
                        data-modal-title="Top-up Saldo Cashless">
                    <x-icon name="wallet" size="sm" class="mr-1" /> Top-up Saldo
                </button>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                    Top-up manual sementara dinonaktifkan.
                </div>
            @endif
        </x-slot:actions>
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.student-search', ['id' => 'filter-q', 'value' => request('q')])
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'fromId' => 'filter-date-from',
                'toId' => 'filter-date-to',
            ])
            <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

@if($manualSaldoEnabled ?? false)
<x-modal id="topup-cashless-modal" title="Top-up Saldo Cashless">
    <form id="topup-cashless-form"
          data-fetch-form
          data-topup-cashless-form
          data-confirm-submit
          data-confirm-builder="buildCashlessTopupConfirm"
          data-close-modal="topup-cashless-modal"
          data-default-action="{{ route('admin.dompet-digital.topup-saldo.store') }}"
          action="{{ route('admin.dompet-digital.topup-saldo.store') }}"
          method="POST"
          class="space-y-4"
          data-reload-table>
        @csrf
        <div>
            <p class="form-label mb-2">Metode pilih siswa</p>
            <div class="visitor-segment" role="tablist" aria-label="Metode top-up">
                <button type="button" class="visitor-segment__btn is-active" data-cashless-method="search" data-target-form="topup-cashless-form" role="tab" aria-selected="true">
                    <x-icon name="search" size="sm" class="mr-1" /> Cari Siswa
                </button>
                <button type="button" class="visitor-segment__btn" data-cashless-method="rfid" data-target-form="topup-cashless-form" role="tab" aria-selected="false">
                    <x-icon name="nfc" size="sm" class="mr-1" /> Scan RFID
                </button>
            </div>
        </div>
        <div data-method-panel="search" data-form="topup-cashless-form">
            <x-siswa-select :status="null" id="topup-siswa" />
        </div>
        <div data-method-panel="rfid" data-form="topup-cashless-form" class="hidden space-y-3">
            <div>
                <label class="form-label" for="topup-rfid">RFID Siswa</label>
                <input type="text"
                       name="rfid_uid"
                       id="topup-rfid"
                       class="form-input font-mono"
                       placeholder="Scan kartu RFID..."
                       autocomplete="off"
                       disabled>
            </div>
            <div id="topup-cashless-rfid-summary"
                 class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                <p class="font-medium text-slate-900 dark:text-white" id="topup-cashless-rfid-name">-</p>
                <p class="text-muted mt-1" id="topup-cashless-rfid-meta">-</p>
            </div>
        </div>
        <div id="topup-cashless-saldo-info"
             class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-600 dark:text-slate-400">Saldo cashless tersedia</span>
                <span id="topup-cashless-saldo-tersedia" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
            </div>
        </div>
        <div>
            <label class="form-label" for="topup-amount">Nominal (Rp)</label>
            <x-form.amount name="amount" id="topup-amount" :min="0" required />
        </div>
        <div>
            <label class="form-label" for="topup-description">Keterangan</label>
            <input type="text" name="description" id="topup-description" class="form-input" placeholder="Opsional">
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="topup-cashless-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="wallet" size="sm" class="mr-1" /> Proses Top Up
            </button>
        </div>
    </form>
</x-modal>
@endif
@endsection

@push('scripts')
    <script src="{{ asset('js/topup-cashless.js') }}?v=7"></script>
@endpush
