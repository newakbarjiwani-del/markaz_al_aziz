@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mb-4 flex flex-wrap items-center justify-between gap-4 p-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Rekening Bank Sekolah</h2>
        <p class="text-sm text-slate-500">{{ $activeCount }} rekening aktif untuk penerimaan pembayaran</p>
    </div>
    <button type="button" class="btn-primary"
            data-open-modal="rekening-modal"
            data-form-reset="rekening-form"
            data-store-url="{{ route('admin.keuangan.rekening.store') }}"
            data-modal-title="Tambah Rekening Bank">
        <x-icon name="plus" size="sm" class="mr-1" /> Tambah Rekening
    </button>
</div>

@include('admin.partials.datatable-page', [
    'tableTitle' => 'Daftar Rekening',
    'ajaxUrl' => route('admin.keuangan.rekening.data'),
    'columns' => ['Bank', 'No. Rekening', 'Atas Nama', 'Status', 'Aksi'],
])
@endsection

@push('modals')
<x-modal id="rekening-modal" title="Tambah / Ubah Rekening Bank">
    <form id="rekening-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.rekening.store') }}"
          data-reload-table
          data-close-modal="rekening-modal"
          action="{{ route('admin.keuangan.rekening.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Rekening</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="rekening-bank">Bank</label>
                    <input name="bank" id="rekening-bank" class="form-input" placeholder="BCA, Mandiri, BRI..." required>
                </div>
                <div>
                    <label class="form-label" for="rekening-account-number">No. Rekening</label>
                    <input name="account_number" id="rekening-account-number" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="rekening-account-name">Atas Nama</label>
                    <input name="account_name" id="rekening-account-name" class="form-input" required>
                </div>
                <div>
                    <x-form.checkbox name="is_active" label="Rekening aktif" :checked="true" />
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="rekening-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush
