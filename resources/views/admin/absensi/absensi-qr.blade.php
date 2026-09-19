@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-6 p-6">
    <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Check-in Siswa</h2>
    <form data-fetch-form action="{{ route('admin.absensi.absensi-qr.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
        @csrf
        <div>
            <label for="qr-nis" class="form-label">NIS / Scan QR</label>
            <input type="text"
                   name="nis"
                   id="qr-nis"
                   class="form-input font-mono"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   maxlength="{{ \App\Support\VirtualAccountNumber::NIS_MAX_LENGTH }}"
                   placeholder="NIS (maks. {{ \App\Support\VirtualAccountNumber::NIS_MAX_LENGTH }} digit)"
                   required
                   autofocus>
        </div>
        <div>
            <label for="qr-status" class="form-label">Status</label>
            <x-attendance-status-select id="qr-status" :required="true" class="form-input" />
        </div>
        <div class="flex items-end">
            <button type="submit" class="btn-primary">
                <x-icon name="scan" size="sm" class="mr-1" /> Proses Check-in
            </button>
        </div>
    </form>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Check-in Hari Ini',
    'ajaxUrl' => route('admin.absensi.absensi-qr.data'),
    'columns' => ['NIS', 'Nama', 'Status', 'Jam Masuk'],
])
@endsection
