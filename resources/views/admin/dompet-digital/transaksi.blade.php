@extends('layouts.app')
@section('title', $title)
@section('content')
<div id="cashless-transaksi-summary" class="card mb-6 p-5" data-summary-url="{{ route('admin.dompet-digital.transaksi.summary') }}">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Kredit</p>
            <p class="mt-1 break-words text-xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-2xl" data-summary-key="kredit">Rp 0</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Debet</p>
            <p class="mt-1 break-words text-xl font-bold text-rose-600 dark:text-rose-400 sm:text-2xl" data-summary-key="debet">Rp 0</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Selisih</p>
            <p class="mt-1 break-words text-xl font-bold text-primary-700 dark:text-primary-300 sm:text-2xl" data-summary-key="net">Rp 0</p>
        </div>
    </div>
</div>

<x-admin.datatable-page
    title="Riwayat Transaksi Cashless"
    :ajax-url="$ajaxUrl ?? route('admin.dompet-digital.transaksi.data')"
    :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Metode', 'Kredit', 'Debet', 'Dompet', 'Referensi']"
    :default-order="[[0, 'desc']]">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
            <div>
                <label class="form-label" for="filter-metode">Metode</label>
                <select name="metode" id="filter-metode" class="form-input">
                    <option value="">Semua</option>
                    @foreach($metodeOptions ?? [] as $metode)
                        <option value="{{ $metode }}" @selected(request('metode') === $metode)>{{ $metode }}</option>
                    @endforeach
                </select>
            </div>
            @include('admin.partials.filters.date-range', ['from' => request('date_from'), 'to' => request('date_to'), 'fromId' => 'filter-date-from', 'toId' => 'filter-date-to'])
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('scripts')
<script src="{{ asset('js/transaksi-cashless-summary.js') }}?v=1"></script>
@endpush
