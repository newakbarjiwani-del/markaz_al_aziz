@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.perpustakaan.partials.loan-stats-peminjaman')

<x-admin.datatable-page
    title="Data Peminjaman"
    subtitle="Kelola peminjaman buku, perpanjang masa pinjam, dan lihat status keterlambatan."
    :ajax-url="$ajaxUrl ?? route('admin.perpustakaan.peminjaman.data')"
    :columns="['Tipe', 'ID/NIS/NIP', 'Nama', 'Meta', 'Buku (Jml)', 'Pinjam', 'Jatuh Tempo', 'Sisa/Telat', 'Status', 'Denda', 'Catatan', 'Aksi']"
    :column-options="[
        4 => ['html' => true],
        7 => ['html' => true],
        8 => ['html' => true],
        10 => ['html' => true],
        11 => ['html' => true],
    ]"
    :show-export="true">
    <x-slot:actions>
        <a href="{{ $settingUrl ?? route('admin.perpustakaan.setting') }}" class="btn-secondary flex-1 sm:flex-none">
            <x-icon name="settings" size="sm" class="mr-1" /> Setting Denda
        </a>
        <a href="{{ $createUrl ?? route('admin.perpustakaan.peminjaman.create') }}" class="btn-primary flex-1 sm:flex-none">
            <x-icon name="plus" size="sm" class="mr-1" /> Pinjam Buku
        </a>
    </x-slot:actions>
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label for="filter-borrower-type" class="form-label">Tipe Peminjam</label>
            <select name="borrower_type" id="filter-borrower-type" class="form-input">
                <option value="">Semua</option>
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
                <option value="tamu">Tamu</option>
            </select>
        </div>
        <div>
            <label for="filter-status" class="form-label">Status</label>
            <select name="status" id="filter-status" class="form-input">
                <option value="">Semua</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="dikembalikan">Dikembalikan</option>
                <option value="hilang">Hilang</option>
            </select>
        </div>
        <div>
            <label for="filter-overdue" class="form-label">Keterangan</label>
            <select name="overdue" id="filter-overdue" class="form-input">
                <option value="">Semua</option>
                <option value="1">Terlambat saja</option>
            </select>
        </div>
        @if(($classes ?? collect())->isNotEmpty())
            <div>
                <label for="filter-kelas" class="form-label">Kelas (siswa)</label>
                <select name="kelas_id" id="filter-kelas" class="form-input" data-s2>
                    <option value="">Semua kelas</option>
                    @foreach($classes as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @include('admin.partials.filters.date-range', [
            'from' => request('date_from'),
            'to' => request('date_to'),
            'fromLabel' => 'Pinjam Dari',
            'toLabel' => 'Pinjam Sampai',
        ])
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>

@include('admin.perpustakaan.partials.loan-detail-modal')
@include('admin.perpustakaan.partials.loan-extend-modal')
@endsection

@push('scripts')
@php
    $libraryLoanConfig = [
        'page' => 'list',
        'extensionDays' => $extensionDays ?? 7,
        'detailUrl' => $detailUrl ?? url('admin/perpustakaan/peminjaman'),
    ];
@endphp
<script type="application/json" id="library-loan-config">{!! json_encode($libraryLoanConfig) !!}</script>
<script src="{{ asset('js/library-loan.js') }}?v=5"></script>
@endpush
