@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="kas-manual-page-config"
     data-stats-url="{{ route('admin.keuangan.kas-manual.stats') }}"></div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="stat-card stat-card-green">
        <p class="text-sm text-slate-500">Total Kredit (Pemasukan)</p>
        <p id="kas-manual-total-kredit" class="mt-2 text-2xl font-bold">Rp {{ number_format($totalKredit, 0, ',', '.') }}</p>
    </div>
    <div class="stat-card stat-card-red">
        <p class="text-sm text-slate-500">Total Debet (Pengeluaran)</p>
        <p id="kas-manual-total-debet" class="mt-2 text-2xl font-bold">Rp {{ number_format($totalDebet, 0, ',', '.') }}</p>
    </div>
    <div class="stat-card stat-card-blue">
        <p class="text-sm text-slate-500">Saldo Bersih</p>
        <p id="kas-manual-saldo-bersih"
           class="mt-2 text-2xl font-bold {{ $saldoBersih >= 0 ? 'text-green-600' : 'text-red-600' }}">
            Rp {{ number_format($saldoBersih, 0, ',', '.') }}
        </p>
    </div>
</div>

<x-admin.datatable-page
    title="Riwayat Kas Manual"
    subtitle="Pencatatan pemasukan & pengeluaran kas di luar sistem tagihan dan saldo siswa"
    :ajax-url="route('admin.keuangan.kas-manual.data')"
    :columns="['Tanggal', 'Kategori', 'Deskripsi', 'Kredit', 'Debet', 'Petugas', 'Aksi']"
    :column-options="[6 => ['html' => true]]"
    export-filename="kas-manual">
    @can('finance.create')
        <x-slot:actions>
            <button type="button" class="btn-primary flex-1 sm:flex-none"
                    data-open-modal="kas-manual-modal"
                    data-form-reset="kas-manual-form"
                    data-store-url="{{ route('admin.keuangan.kas-manual.store') }}"
                    data-modal-title="Tambah Catatan Kas">
                <x-icon name="plus" size="sm" class="mr-1" /> Tambah Catatan
            </button>
        </x-slot:actions>
    @endcan
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
        <div>
            <label class="form-label" for="filter-kategori">Kategori</label>
            <select name="kategori" id="filter-kategori" class="form-input" data-ajax-select
                    data-url="{{ route('admin.keuangan.kas-manual.kategori') }}"
                    data-allow-clear="1"
                    data-placeholder="Semua Kategori"
                    data-min-length="0">
                <option value=""></option>
            </select>
        </div>
        @include('admin.partials.filters.date-range', [
            'from' => request('date_from'),
            'to' => request('date_to'),
            'fromId' => 'filter-date-from',
            'toId' => 'filter-date-to',
            'fromLabel' => 'Tanggal Dari',
            'toLabel' => 'Tanggal Sampai',
        ])
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection

@push('modals')
<x-modal id="kas-manual-modal" title="Tambah Catatan Kas">
    <form id="kas-manual-form"
          data-fetch-form
          data-default-action="{{ route('admin.keuangan.kas-manual.store') }}"
          data-reload-table
          data-close-modal="kas-manual-modal"
          action="{{ route('admin.keuangan.kas-manual.store') }}"
          method="POST"
          class="space-y-5">
        @csrf
        <section class="form-section">
            <h4 class="form-section__title">Informasi Kas</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="kas-tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="kas-tanggal" class="form-input"
                           value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label" for="kas-kategori">Kategori</label>
                    <input type="text" name="kategori" id="kas-kategori" class="form-input"
                           list="kas-kategori-suggestions"
                           placeholder="Infaq, Donasi, Operasional, ..." required>
                    <datalist id="kas-kategori-suggestions">
                        <option value="Infaq"></option>
                        <option value="Donasi"></option>
                        <option value="Operasional"></option>
                        <option value="Gaji"></option>
                        <option value="Utilitas"></option>
                    </datalist>
                </div>
                <fieldset>
                    <legend class="form-label mb-2">Jenis Transaksi</legend>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="radio" name="arah" value="pemasukan" class="form-radio" checked>
                            Pemasukan (Kredit)
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="radio" name="arah" value="pengeluaran" class="form-radio">
                            Pengeluaran (Debet)
                        </label>
                    </div>
                </fieldset>
                <div>
                    <label class="form-label" for="kas-nominal" id="kas-nominal-label">Nominal Pemasukan</label>
                    <x-form.amount name="nominal" id="kas-nominal" :min="1" placeholder="0" required />
                </div>
                <div>
                    <label class="form-label" for="kas-deskripsi">Deskripsi</label>
                    <textarea name="deskripsi" id="kas-deskripsi" class="form-input" rows="3"
                              placeholder="Keterangan tambahan..."></textarea>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="kas-manual-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="device-floppy" size="sm" class="mr-1" /> Simpan
            </button>
        </div>
    </form>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/kas-manual-form.js') }}?v=2"></script>
<script src="{{ asset('js/kas-manual-stats.js') }}?v=2"></script>
@endpush
