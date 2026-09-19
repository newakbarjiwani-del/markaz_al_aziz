@extends('layouts.app')

@section('title', $title)

@section('content')
@include('admin.perpustakaan.partials.loan-stats-pengembalian')

<div class="library-return-page">
<x-admin.datatable-page
    title="Buku Sedang Dipinjam — klik baris untuk proses pengembalian"
    subtitle="Klik baris peminjaman aktif atau tombol pengembalian untuk proses pengembalian. Denda telat Rp {{ number_format($finePerDay ?? 2000, 0, ',', '.') }}/hari."
    :ajax-url="$ajaxUrl ?? route('admin.perpustakaan.pengembalian-buku.data')"
    :columns="['Tipe', 'ID/NIS/NIP', 'Nama', 'Meta', 'Buku (Jml)', 'Pinjam', 'Jatuh Tempo', 'Sisa/Telat', 'Perpanjang', 'Est. Denda', 'Aksi']"
    :column-options="[
        4 => ['html' => true],
        7 => ['html' => true],
        10 => ['html' => true],
    ]"
    :show-export="true">
    @if(! empty($settingUrl))
        <x-slot:actions>
            <a href="{{ $settingUrl }}" class="btn-secondary flex-1 sm:flex-none">
                <x-icon name="settings" size="sm" class="mr-1" /> Setting Denda
            </a>
        </x-slot:actions>
    @endif
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
            <label for="filter-overdue" class="form-label">Keterangan</label>
            <select name="overdue" id="filter-overdue" class="form-input">
                <option value="">Semua peminjaman aktif</option>
                <option value="1">Terlambat saja</option>
            </select>
        </div>
        @if(($classes ?? collect())->isNotEmpty())
            <div>
                <label for="filter-kelas" class="form-label">Kelas</label>
                <select name="kelas_id" id="filter-kelas" class="form-input" data-s2>
                    <option value="">Semua kelas</option>
                    @foreach($classes as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @include('admin.partials.filters.date-range', [
            'from' => request('due_from'),
            'to' => request('due_to'),
            'fromName' => 'due_from',
            'toName' => 'due_to',
            'fromLabel' => 'Jatuh Tempo Dari',
            'toLabel' => 'Jatuh Tempo Sampai',
        ])
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
</div>

@include('admin.perpustakaan.partials.loan-return-modal', [
    'formAction' => $formAction ?? route('admin.perpustakaan.pengembalian-buku.store'),
    'kondisiOptions' => $kondisiOptions ?? [],
])
@include('admin.perpustakaan.partials.loan-detail-modal')
@endsection

@push('scripts')
@php
    $libraryLoanConfig = [
        'page' => 'return',
        'finePerDay' => $finePerDay ?? 2000,
        'previewUrl' => $previewUrl ?? url('admin/perpustakaan/pengembalian-buku/preview'),
        'detailUrl' => $detailUrl ?? url('admin/perpustakaan/peminjaman'),
    ];
@endphp
<script type="application/json" id="library-loan-config">{!! json_encode($libraryLoanConfig) !!}</script>
<script src="{{ asset('js/library-loan.js') }}?v=5"></script>
@endpush
