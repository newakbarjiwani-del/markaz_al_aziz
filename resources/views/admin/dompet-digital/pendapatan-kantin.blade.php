@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="pendapatan-kantin-page"
     data-show-url="{{ url('/admin/dompet-digital/pendapatan-kantin') }}"
     data-withdraw-url="{{ url('/admin/dompet-digital/pendapatan-kantin') }}"
     data-can-withdraw="{{ !empty($canWithdraw) ? '1' : '0' }}">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Pendapatan Hari Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['hari_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Pendapatan Bulan Ini</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['bulan_ini'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-purple">
            <p class="text-sm text-slate-500">Operator Kantin</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['total_operator'] }}</p>
        </div>
    </div>

    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
        Rekap pendapatan POS kantin (<strong>BELANJA</strong>) per akun role <strong>kantin</strong>.
        Filter tanggal membatasi kolom pendapatan periode; <strong>Sisa belum ditarik</strong> adalah sisa omzet all-time dikurangi penarikan tunai.
        Lihat juga
        <a href="{{ route('admin.dompet-digital.pendapatan-kantin.penarikan.index') }}" class="font-medium text-primary-700 underline underline-offset-2 dark:text-primary-300">Riwayat Penarikan</a>.
    </div>

    <x-admin.datatable-page
        title="Pendapatan per Operator Kantin"
        :ajax-url="route('admin.dompet-digital.pendapatan-kantin.data')"
        :columns="['Nama', 'Username', 'Sekolah', 'Pendapatan', 'Jumlah Transaksi', 'Sisa Belum Ditarik', 'Aksi']"
        :column-options="[6 => ['html' => true, 'orderable' => false, 'searchable' => false, 'exportable' => false]]"
        :show-export="true">
        <x-slot:actions>
            <a href="{{ route('admin.dompet-digital.pendapatan-kantin.penarikan.index') }}" class="btn-secondary flex-1 sm:flex-none">
                <x-icon name="receipt" size="sm" class="mr-1" /> Riwayat Penarikan
            </a>
        </x-slot:actions>
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-q">Cari Operator</label>
                <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama atau username" value="{{ request('q') }}">
            </div>
            @if($showSekolahFilter)
                @include('admin.partials.filters.sekolah-select', [
                    'schools' => $schools,
                    'selected' => request('sekolah_id'),
                    'id' => 'filter-sekolah',
                ])
            @endif
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'fromId' => 'filter-date-from',
                'toId' => 'filter-date-to',
                'fromLabel' => 'Tanggal Dari',
                'toLabel' => 'Tanggal Sampai',
            ])
            <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

<x-modal id="pendapatan-kantin-detail-modal" title="Detail Pendapatan Operator">
    <div class="space-y-4">
        <div id="pendapatan-kantin-detail-summary" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
            Memuat data operator...
        </div>
        <div class="card card--datatable">
            <div class="card-divider p-4">
                <h3 class="card-title text-base">Transaksi BELANJA</h3>
            </div>
            <div class="card--datatable__body p-4 pt-0">
                <form id="pendapatan-kantin-trx-filter-form" class="filter-form mb-4">
                    @include('admin.partials.filters.date-range', [
                        'from' => null,
                        'to' => null,
                        'fromId' => 'detail-date-from',
                        'toId' => 'detail-date-to',
                        'fromName' => 'date_from',
                        'toName' => 'date_to',
                    ])
                    <x-filter-actions />
                </form>
                <table id="pendapatan_kantin_trx_table"
                       class="datatable-main w-full display"
                       style="--table-min-width: 40rem"
                       data-default-order='[[0,"desc"]]'>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Dompet</th>
                            <th>Nominal</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" data-modal-close="pendapatan-kantin-detail-modal" class="btn-secondary">Tutup</button>
        </div>
    </div>
</x-modal>

@if(!empty($canWithdraw))
<x-modal id="pendapatan-kantin-withdraw-modal" title="Tarik Pendapatan Kantin (Tunai)">
    <form id="pendapatan-kantin-withdraw-form"
          data-fetch-form
          data-reload-table
          data-close-modal="pendapatan-kantin-withdraw-modal"
          data-confirm-submit
          data-confirm-builder="buildPendapatanKantinWithdrawConfirm"
          method="POST"
          class="space-y-4">
        @csrf
        <input type="hidden" name="kantin_user_id" id="withdraw-kantin-user-id" value="">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/40">
            <p class="text-slate-500">Operator</p>
            <p id="withdraw-operator-name" class="font-medium text-slate-900 dark:text-white">-</p>
            <p class="mt-3 text-slate-500">Sisa belum ditarik</p>
            <p id="withdraw-outstanding-label" class="font-medium text-slate-900 dark:text-white">Rp 0</p>
        </div>
        <div>
            <label class="form-label" for="withdraw-pendapatan-amount">Nominal Penarikan</label>
            <x-form.amount name="amount" id="withdraw-pendapatan-amount" :min="1" required />
            <p class="text-muted mt-1 text-xs">Penarikan tunai (cash handoff). Tidak mengubah ledger siswa.</p>
        </div>
        <div>
            <label class="form-label" for="withdraw-pendapatan-description">Keterangan <span class="text-muted text-xs font-normal">(opsional)</span></label>
            <textarea name="description" id="withdraw-pendapatan-description" class="form-input" rows="2" maxlength="1000" placeholder="Contoh: Setoran kas harian"></textarea>
        </div>
        <div class="modal-panel__footer flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
            <button type="button" data-modal-close="pendapatan-kantin-withdraw-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="cash" size="sm" class="mr-1" /> Konfirmasi Tarik
            </button>
        </div>
    </form>
</x-modal>
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/pendapatan-kantin.js') }}?v=3"></script>
@endpush
