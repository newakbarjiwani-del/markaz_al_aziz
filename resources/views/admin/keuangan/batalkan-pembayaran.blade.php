@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="batalkan-pembayaran-page"
     data-receipt-url="{{ route('admin.keuangan.batalkan-pembayaran.receipt') }}">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Pembayaran Kasir Hari Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['hari_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Pembayaran Kasir Bulan Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['bulan_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-purple">
            <p class="text-sm text-slate-500">Total Kuitansi Kasir</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['total_kuitansi'] }}</p>
        </div>
    </div>

    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
        Hanya pembayaran kasir (tunai, transfer, saldo keuangan, manual BMI) yang dapat dibatalkan. Pembayaran cicilan akan mengembalikan sisa tagihan ke induk dan menghapus baris cicilan terkait.
    </div>

    <x-admin.datatable-page
        title="Pembayaran Dapat Dibatalkan"
        :ajax-url="route('admin.keuangan.batalkan-pembayaran.data')"
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
                    <option value="1140000" @selected(request('method') === '1140000')>Tunai</option>
                    <option value="1140001" @selected(request('method') === '1140001')>Manual BMI</option>
                    <option value="1140002" @selected(request('method') === '1140002')>Saldo Keuangan</option>
                    <option value="1140003" @selected(request('method') === '1140003')>Transfer Bank Lain</option>
                </select>
            </div>
            <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

<x-modal id="payment-cancel-detail-modal" title="Detail Pembayaran">
    <div class="space-y-4">
        <div id="payment-cancel-detail-summary" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
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
                <tbody id="payment-cancel-detail-items-body">
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-slate-500">Memuat tagihan...</td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-200">Total Dibayar</td>
                        <td id="payment-cancel-detail-total" class="px-4 py-3 text-right font-semibold text-primary-700 dark:text-primary-300">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="flex justify-end">
            <button type="button" data-modal-close="payment-cancel-detail-modal" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/batalkan-pembayaran.js') }}?v=4"></script>
@endpush
