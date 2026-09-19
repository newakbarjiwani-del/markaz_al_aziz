@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Tambah Alokasi</h2>
    <form data-fetch-form action="{{ route('admin.dompet-digital.alokasi-uang-saku.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
        @csrf
        <x-siswa-select :status="null" id="alokasi-siswa" />
        <div>
            <label class="form-label" for="alokasi-amount">Nominal (Rp)</label>
            <x-form.amount name="amount" id="alokasi-amount" :min="1000" required />
        </div>
        <div>
            <label class="form-label" for="alokasi-period">Periode</label>
            <input type="month" name="period" id="alokasi-period" value="{{ now()->format('Y-m') }}" class="form-input">
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="btn-primary">Simpan Alokasi</button>
        </div>
    </form>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Daftar Alokasi Uang Saku',
    'ajaxUrl' => route('admin.dompet-digital.alokasi-uang-saku.data'),
    'columns' => ['NIS', 'Nama', 'Nominal', 'Periode', 'Status', 'Tanggal'],
])
@endsection
