@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6">
    <x-stat-card label="Saldo Cashless" :value="'Rp '.number_format($saldoCashless, 0, ',', '.')" accent="primary" />
</div>

<div id="portal-cashless-page"
     data-export-filename="riwayat-cashless-siswa">
    <x-admin.datatable-page
        title="Riwayat Transaksi"
        :ajax-url="route('portal.siswa.dompet.data')"
        :columns="['Tanggal', 'Metode', 'Kredit', 'Debet', 'Referensi', 'Aksi']"
        :column-options="[
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => true],
            ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true],
        ]"
        :show-export="true">
        <x-slot:filters>
            @include('portal.partials.date-filter')
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
