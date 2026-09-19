@extends('layouts.app')

@section('title', $title)

@section('content')
<x-portal.saldo-legend highlight="cashless" />

<div class="card mb-6 p-5">
    <div class="grid grid-cols-2 gap-x-4 gap-y-5 lg:grid-cols-4">
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Saldo Cashless</p>
            <p class="mt-1 break-words text-xl font-bold text-primary-700 dark:text-primary-300 sm:text-2xl">Rp {{ number_format($stats['saldo'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Masuk Bulan Ini</p>
            <p class="mt-1 break-words text-xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-2xl">Rp {{ number_format($stats['bulan_ini_kredit'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Keluar Bulan Ini</p>
            <p class="mt-1 break-words text-xl font-bold text-amber-600 dark:text-amber-400 sm:text-2xl">Rp {{ number_format($stats['bulan_ini_debet'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Transaksi</p>
            <p class="mt-1 break-words text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">{{ number_format($stats['total_transaksi']) }}</p>
        </div>
    </div>
</div>

@if($balanceCards->isNotEmpty())
    <div class="mb-6 grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 md:grid-cols-3">
        @foreach($balanceCards as $card)
            <div class="card p-5">
                <p class="font-semibold text-slate-900 dark:text-white">{{ $card['siswa']->name }}</p>
                <p class="text-muted mb-2 text-sm">NIS {{ $card['siswa']->nis }} · {{ $card['siswa']->kelas?->name ?? '-' }}</p>
                <div class="mb-4">
                    <x-portal.vano
                        :value="$card['siswa']->virtualAccountNumber()"
                        hint="Untuk transfer pembayaran tagihan"
                    />
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Saldo Cashless</dt>
                        <dd class="font-semibold text-primary-700 dark:text-primary-300">Rp {{ number_format($card['saldo'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>
        @endforeach
    </div>
@endif

<div id="portal-cashless-page"
     data-export-filename="riwayat-cashless-ortu">
    <x-admin.datatable-page
        title="Riwayat Transaksi"
        :ajax-url="route('portal.ortu.dompet.data')"
        :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Metode', 'Kredit', 'Debet', 'Referensi', 'Aksi']"
        :column-options="[
            ['orderable' => true],
            ['orderable' => false, 'searchable' => false],
            ['orderable' => false, 'searchable' => false],
            ['orderable' => false, 'searchable' => false],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
        ]"
        :show-export="true">
        <x-slot:filters>
            @include('portal.partials.ortu-datatable-filters', ['children' => $children])
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

<x-modal id="portal-cashless-detail-modal" title="Detail Transaksi Cashless">
    <div id="portal-cashless-detail-body" class="space-y-5">
        <p class="text-sm text-slate-500 dark:text-slate-400">Memuat detail transaksi...</p>
    </div>
</x-modal>
@endsection

@push('scripts')
<script src="{{ asset('js/portal-cashless-detail.js') }}?v=1"></script>
@endpush
