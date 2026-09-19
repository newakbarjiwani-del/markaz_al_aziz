@extends('layouts.app')

@section('title', $title)

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-receipt.css') }}?v=3">
@endpush

<div id="riwayat-pembayaran-page"
     data-receipt-url="{{ route('admin.keuangan.riwayat-pembayaran.receipt') }}"
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
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Penerimaan Hari Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['hari_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Penerimaan Bulan Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['bulan_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-purple">
            <p class="text-sm text-slate-500">Total Kuitansi</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['total_kuitansi'] }}</p>
        </div>
    </div>

    <x-admin.datatable-page
        title="Riwayat Kuitansi Pembayaran"
        :ajax-url="route('admin.keuangan.riwayat-pembayaran.data')"
        :columns="['Waktu', 'NIS', 'Nama', 'Kelas', 'No. Kuitansi', 'Metode', 'Jumlah Tagihan', 'Total', 'Aksi']"
        :column-options="[8 => ['html' => true]]"
        :show-export="true">
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-q">Cari Siswa</label>
                <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama atau NIS" value="{{ request('q') }}">
            </div>
            <div>
                <label class="form-label" for="filter-reference">No. Kuitansi</label>
                <input type="text" name="reference" id="filter-reference" class="form-input" placeholder="KW-..." value="{{ request('reference') }}">
            </div>
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'fromId' => 'filter-date-from',
                'toId' => 'filter-date-to',
                'fromLabel' => 'Tanggal Dari',
                'toLabel' => 'Tanggal Sampai',
            ])
            @include('admin.partials.filters.class-select', [
                'classes' => $classes,
                'selected' => request('kelas_id'),
                'id' => 'filter-kelas',
            ])
            <div>
                <label class="form-label" for="filter-method">Metode</label>
                <select name="method" id="filter-method" class="form-input">
                    <option value="">Semua</option>
                    <option value="transfer" @selected(request('method') === 'transfer')>Transfer</option>
                    <option value="tunai" @selected(request('method') === 'tunai')>Tunai</option>
                    <option value="qris" @selected(request('method') === 'qris')>QRIS</option>
                    <option value="va" @selected(request('method') === 'va')>Virtual Account</option>
                </select>
            </div>
            <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

<x-modal id="payment-detail-modal" title="Detail Pembayaran">
    <div class="space-y-4">
        <div id="payment-detail-summary" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
            Memuat detail pembayaran...
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3">Jenis Tagihan</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3 text-right">Total Tagihan</th>
                        <th class="px-4 py-3 text-right">Total Terbayar</th>
                        <th class="px-4 py-3 text-right">Dibayar</th>
                    </tr>
                </thead>
                <tbody id="payment-detail-items-body">
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-slate-500">Memuat tagihan...</td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-200">Total Dibayar</td>
                        <td id="payment-detail-total" class="px-4 py-3 text-right font-semibold text-primary-700 dark:text-primary-300">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button"
                    id="payment-detail-print-btn"
                    class="btn-secondary hidden"
                    data-print-payment-receipt="1">
                <x-icon name="printer" size="sm" class="mr-1" /> Cetak Kuitansi
            </button>
            <button type="button" data-modal-close="payment-detail-modal" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>

<div id="payment-receipt-print-root" aria-hidden="true"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/payment-receipt.js') }}?v=13"></script>
<script src="{{ asset('js/riwayat-pembayaran.js') }}?v=6"></script>
@endpush
