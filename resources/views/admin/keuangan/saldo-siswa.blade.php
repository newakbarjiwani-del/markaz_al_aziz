@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="saldo-siswa-page"
     data-show-url="{{ url('admin/keuangan/saldo-siswa') }}"
     data-transactions-url="{{ url('admin/keuangan/saldo-siswa') }}">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card stat-card-purple">
            <p class="text-sm text-slate-500">Total Saldo</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['total_saldo'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Siswa Bersaldo</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['siswa_bersaldo'] }}</p>
        </div>
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Rata-rata Saldo</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['rata_rata'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="card mb-6 p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Penyesuaian Saldo</h2>
        <p class="mb-4 text-sm text-slate-500">Saldo dihitung dari riwayat transaksi keuangan (kredit − debet). Penyesuaian manual akan dicatat sebagai transaksi <strong>JURNAL SALDO</strong>.</p>
        @if($manualSaldoEnabled ?? false)
        <form data-fetch-form data-reload-table action="{{ route('admin.keuangan.saldo-siswa.adjust') }}" method="POST" class="grid gap-4 md:grid-cols-4">
            @csrf
            <x-siswa-select id="adjust-siswa" />
            <div>
                <label class="form-label" for="adjust-type">Tipe</label>
                <select name="type" id="adjust-type" class="form-input" required>
                    <option value="tambah">Tambah Saldo</option>
                    <option value="kurang">Kurangi Saldo</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="adjust-amount">Nominal (Rp)</label>
                <x-form.amount name="amount" id="adjust-amount" :min="1000" required />
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary">Simpan Penyesuaian</button>
            </div>
        </form>
        @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
            Penyesuaian saldo manual (tambah/kurangi) sementara dinonaktifkan.
        </div>
        @endif
    </div>

    <x-admin.datatable-page
        title="Saldo per Siswa"
        subtitle="Daftar saldo keuangan (SPP ledger) per siswa"
        :ajax-url="route('admin.keuangan.saldo-siswa.data')"
        :columns="['NIS', 'Nama', 'Kelas', 'Saldo', 'Transaksi Terakhir', 'Aksi']"
        :column-options="[5 => ['html' => true]]"
        :show-export="true">
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
                <div>
                    <label class="form-label" for="filter-q">Cari Siswa</label>
                    <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama atau NIS" value="{{ request('q') }}">
                </div>
                @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
                <div class="mt-8">
                    <x-form.checkbox name="punya_transaksi" id="filter-punya-transaksi" label="Hanya siswa dengan riwayat transaksi" :hidden-fallback="false" />
                </div>
                <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

@include('admin.keuangan.partials.saldo-detail-modal', ['metodeOptions' => $metodeOptions])
@endsection

@push('scripts')
<script src="{{ asset('js/saldo-siswa.js') }}?v=4"></script>
@endpush
