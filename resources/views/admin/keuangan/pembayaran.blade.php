@extends('layouts.app')

@section('title', $title)

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-receipt.css') }}?v=3">
@endpush

<div id="pembayaran-page"
     data-tagihan-url="{{ url('admin/keuangan/pembayaran/siswa') }}"
     data-fidbank-tunai="1140000"
     data-fidbank-saldo="1140002"
     data-receipt-app-name="{{ config('app.name') }}"
     data-receipt-operator="{{ auth()->user()?->name ?: auth()->user()?->username ?? '-' }}"
     data-receipt-location="{{ config('app.domisili') }}"
     data-receipt-logo="{{ asset('logo.png') }}"
     data-receipt-nama-instansi="{{ config('app.nama_instansi') }}"
     data-receipt-nama-sub-1="{{ config('app.nama_sub_instansi_1') }}"
     data-receipt-nama-sub-2="{{ config('app.nama_sub_instansi_2') }}"
     data-receipt-akreditasi="{{ config('app.akreditasi') }}"
     data-receipt-alamat="{{ config('app.alamat') }}"
     data-receipt-telepon="{{ config('app.telepon') }}"
     data-receipt-email="{{ config('app.email') }}"
     data-receipt-website="{{ config('app.website') }}">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card stat-card-amber">
            <p class="text-sm text-slate-500">Tagihan Belum Lunas</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['belum_lunas'] }}</p>
        </div>
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Penerimaan Hari Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['hari_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Penerimaan Bulan Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['bulan_ini'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="card mb-6 p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Pilih Siswa</h2>
        <x-student-search id="pembayaran-student-search" class="max-w-xl" />
        <p class="mt-3 text-sm text-slate-500">
            Riwayat dan cetak ulang kuitansi tersedia di
            <a href="{{ route('admin.keuangan.riwayat-pembayaran.index') }}" class="font-medium text-primary-700 underline dark:text-primary-300">Riwayat Pembayaran</a>.
        </p>
    </div>

    <div id="tagihan-section" class="hidden space-y-4">
        <div class="card p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Daftar Tagihan</h2>
                    <p id="tagihan-student-label" class="text-sm text-slate-500"></p>
                </div>
                <button type="button" id="reset-student-btn" class="btn-secondary btn-sm">
                    <x-icon name="refresh" size="sm" class="mr-1" /> Ganti Siswa
                </button>
            </div>

            <div class="table-scroll">
                <table class="w-full min-w-[48rem] text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <x-form.checkbox id="select-all-tagihan" :label="false" :hidden-fallback="false" title="Pilih semua tagihan belum lunas" />
                            </th>
                            <th class="px-4 py-3">Jenis</th>
                            <th class="px-4 py-3">Periode</th>
                            <th class="px-4 py-3">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Terbayar</th>
                            <th class="px-4 py-3 text-right">Sisa</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tagihan-table-body"></tbody>
                </table>
            </div>

            <p id="tagihan-empty" class="hidden py-6 text-center text-sm text-slate-500">Siswa ini belum memiliki tagihan.</p>

            <div id="tagihan-selection-bar" class="mt-4 hidden flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-800 dark:bg-primary-950/40">
                <p id="tagihan-selection-summary" class="text-sm font-medium text-primary-900 dark:text-primary-100"></p>
                <button type="button" id="open-payment-btn" class="btn-primary btn-sm">
                    <x-icon name="cash" size="sm" class="mr-1" /> Bayar Tagihan Terpilih
                </button>
            </div>
        </div>

        <div id="payment-form-card" class="card hidden p-6">
            <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Catat Pembayaran</h2>
            <form id="payment-form"
                  data-fetch-form
                  data-reset-on-success="false"
                  action="{{ route('admin.keuangan.pembayaran.store') }}"
                  method="POST"
                  class="space-y-4">
                @csrf
                <div id="payment-items-container" class="space-y-3"></div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/50">
                    <p class="font-medium text-slate-900 dark:text-white">Ringkasan pembayaran</p>
                    <ul id="payment-summary-list" class="mt-2 space-y-1 text-slate-600 dark:text-slate-300"></ul>
                    <p class="mt-3 border-t border-slate-200 pt-3 dark:border-slate-700">
                        Total dibayar:
                        <span id="payment-total-label" class="font-semibold text-primary-700 dark:text-primary-300">-</span>
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Tagihan non-cicilan dibayar lunas. Tagihan cicilan boleh dibayar sebagian sesuai sisa tagihan.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="form-label" for="payment-fidbank">Metode</label>
                        <select name="fidbank" id="payment-fidbank" class="form-input" required>
                            @foreach ($fidbanks as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="payment-reference">Referensi</label>
                        <input type="text" name="reference" id="payment-reference" class="form-input" placeholder="No. bukti transfer">
                        <p id="payment-reference-hint" class="mt-1 text-xs text-slate-500">Tunai &amp; Saldo Keuangan: referensi dibuat otomatis saat form pembayaran dibuka.</p>
                    </div>
                    <div>
                        <label class="form-label" for="payment-paid-at">Tanggal Bayar</label>
                        <input type="datetime-local" name="paid_dt" id="payment-paid-at" class="form-input" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn-primary">
                        <x-icon name="cash" size="sm" class="mr-1" /> Simpan Pembayaran
                    </button>
                    <button type="button" id="cancel-payment-btn" class="btn-secondary">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="payment-receipt-print-root" aria-hidden="true"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/payment-receipt.js') }}?v=13"></script>
<script src="{{ asset('js/student-search.js') }}?v=1"></script>
<script src="{{ asset('js/pembayaran.js') }}?v=15"></script>
@endpush
