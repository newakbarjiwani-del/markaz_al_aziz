@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="stat-card stat-card-green">
        <p class="text-sm text-slate-500">Dibatalkan Hari Ini</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['hari_ini'] }}</p>
    </div>
    <div class="stat-card stat-card-blue">
        <p class="text-sm text-slate-500">Dibatalkan Bulan Ini</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['bulan_ini'] }}</p>
    </div>
    <div class="stat-card stat-card-purple">
        <p class="text-sm text-slate-500">Total Log Pembatalan</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['total'] }}</p>
    </div>
</div>

<x-admin.datatable-page
    title="Riwayat Pembatalan Pembayaran"
    :ajax-url="route('admin.keuangan.log-batalkan-pembayaran.data')"
    :columns="['Waktu Batal', 'NIS', 'Nama', 'Kelas', 'No. Kuitansi', 'Metode', 'Tgl Bayar', 'Total', 'Dibatalkan Oleh', 'Aksi']"
    :column-options="[9 => ['html' => true]]"
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
            'fromLabel' => 'Dibatalkan Dari',
            'toLabel' => 'Dibatalkan Sampai',
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

<x-modal id="payment-cancel-log-detail-modal" title="Detail Log Pembatalan">
    <div id="payment-cancel-log-detail-root"
         class="space-y-4"
         data-show-url="{{ url('/admin/keuangan/log-batalkan-pembayaran') }}">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="grid gap-3 sm:grid-cols-2">
                <div><p class="text-slate-500">Waktu Pembatalan</p><p class="font-medium text-slate-900 dark:text-white" data-field="created_at">-</p></div>
                <div><p class="text-slate-500">No. Kuitansi</p><p class="font-medium text-slate-900 dark:text-white" data-field="reference">-</p></div>
                <div><p class="text-slate-500">Siswa</p><p class="font-medium text-slate-900 dark:text-white" data-field="siswa_name">-</p></div>
                <div><p class="text-slate-500">NIS / Kelas</p><p class="font-medium text-slate-900 dark:text-white" data-field="siswa_meta">-</p></div>
                <div><p class="text-slate-500">Metode</p><p class="font-medium text-slate-900 dark:text-white" data-field="method_label">-</p></div>
                <div><p class="text-slate-500">Tanggal Bayar</p><p class="font-medium text-slate-900 dark:text-white" data-field="paid_at">-</p></div>
                <div><p class="text-slate-500">Petugas Kasir</p><p class="font-medium text-slate-900 dark:text-white" data-field="original_operator">-</p></div>
                <div><p class="text-slate-500">Dibatalkan Oleh</p><p class="font-medium text-slate-900 dark:text-white" data-field="cancelled_by">-</p></div>
                <div><p class="text-slate-500">Alamat IP</p><p class="font-medium text-slate-900 dark:text-white" data-field="ip_address">-</p></div>
                <div><p class="text-slate-500">ID Pembayaran</p><p class="font-medium text-slate-900 dark:text-white" data-field="pembayaran_id">-</p></div>
            </div>
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
                <tbody id="payment-cancel-log-items-body">
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-slate-500">Memuat tagihan...</td>
                    </tr>
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-medium text-slate-700 dark:text-slate-200">Total Dibatalkan</td>
                        <td class="px-4 py-3 text-right font-semibold text-primary-700 dark:text-primary-300" data-field="total_label">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="flex justify-end">
            <button type="button" data-modal-close="payment-cancel-log-detail-modal" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/log-batalkan-pembayaran.js') }}?v=2"></script>
@endpush
