@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
    Menampilkan transaksi <strong>BELANJA</strong> dari POS Anda (debit saldo cashless: Uang Saku, Kantin, atau Tabungan).
</div>

<x-admin.datatable-page
    title="Riwayat Belanja POS Kantin"
    :ajax-url="route('portal.kantin.transaksi.data')"
    :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Dompet', 'Nominal', 'Keterangan', 'Petugas']"
    :show-export="true"
    :default-order="[[0, 'desc']]">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            <div>
                <label class="form-label" for="filter-q">Cari Siswa</label>
                <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama atau NIS" value="{{ request('q') }}">
            </div>
            @include('admin.partials.filters.class-select', [
                'classes' => $classes,
                'selected' => request('kelas_id'),
                'id' => 'filter-kelas',
            ])
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'fromId' => 'filter-date-from',
                'toId' => 'filter-date-to',
            ])
            <div>
                <label class="form-label" for="filter-wallet">Dompet</label>
                <select name="wallet" id="filter-wallet" class="form-input">
                    <option value="">Semua</option>
                    <option value="us" @selected(request('wallet') === 'us')>Uang Saku</option>
                    <option value="kantin" @selected(request('wallet') === 'kantin')>Kantin</option>
                    <option value="tabungan" @selected(request('wallet') === 'tabungan')>Tabungan</option>
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
