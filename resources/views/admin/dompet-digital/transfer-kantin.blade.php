@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Transfer Uang Saku ke Kantin</h2>
    <form data-fetch-form action="{{ route('admin.dompet-digital.transfer-kantin.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
        @csrf
        <x-siswa-select :status="null" id="transfer-siswa" />
        <div>
            <label class="form-label" for="transfer-amount">Nominal Transfer (Rp)</label>
            <x-form.amount name="amount" id="transfer-amount" :min="1000" required />
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">
                <x-icon name="arrows-exchange" size="sm" class="mr-1" /> Transfer
            </button>
        </div>
    </form>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Riwayat Transfer',
    'ajaxUrl' => route('admin.dompet-digital.transfer-kantin.data'),
    'columns' => ['NIS', 'Nama', 'Nominal', 'Arah', 'Waktu'],
])
@endsection
