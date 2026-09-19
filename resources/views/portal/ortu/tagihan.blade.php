@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="ortu-tagihan-page"
     data-unpaid-url="{{ route('portal.ortu.tagihan.unpaid') }}"
     data-pay-url="{{ route('portal.ortu.tagihan.bayar') }}"
     data-default-siswa-id="{{ $children->count() === 1 ? $children->first()->id : '' }}">
    <div class="card mb-6 p-5">
        <div class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3">
            <div class="portal-stat-segment">
                <p class="text-muted text-xs font-medium sm:text-sm">Total Tagihan</p>
                <p class="mt-1 break-words text-xl font-bold text-primary-700 dark:text-primary-300 sm:text-2xl">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="portal-stat-segment">
                <p class="text-muted text-xs font-medium sm:text-sm">Lunas</p>
                <p class="mt-1 break-words text-xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-2xl">{{ number_format($stats['lunas']) }}</p>
            </div>
            <div class="portal-stat-segment col-span-2 sm:col-span-1">
                <p class="text-muted text-xs font-medium sm:text-sm">Belum Lunas</p>
                <p class="mt-1 break-words text-xl font-bold text-amber-600 dark:text-amber-400 sm:text-2xl">{{ number_format($stats['belum_lunas']) }}</p>
            </div>
        </div>
    </div>

    <x-admin.datatable-page
        title="Daftar Tagihan"
        subtitle="Bayar tagihan belum lunas menggunakan saldo keuangan anak."
        :ajax-url="route('portal.ortu.tagihan.data')"
        :columns="['NIS', 'No. VA', 'Nama', 'Kelas', 'Jenis', 'Periode', 'Nominal', 'Terbayar', 'Sisa', 'Status', 'Metode', 'Tgl. Lunas', 'Jatuh Tempo']"
        :column-options="[1 => ['html' => true, 'exportable' => true]]"
        :show-export="true">
        @if($children->isNotEmpty())
            <x-slot:actions>
                <button type="button" class="btn-primary" data-open-modal="ortu-bayar-tagihan-modal">
                    <x-icon name="wallet" size="sm" class="mr-1" /> Bayar dari Saldo Keuangan
                </button>
            </x-slot:actions>
        @endif
        <x-slot:filters>
            @include('portal.partials.ortu-tagihan-filters', [
                'children' => $children,
                'jenisOptions' => $jenisOptions,
            ])
        </x-slot:filters>
    </x-admin.datatable-page>
</div>
@endsection

@push('modals')
<x-modal id="ortu-bayar-tagihan-modal" title="Bayar Tagihan dari Saldo Keuangan">
    <div class="space-y-5">
        <section class="form-section">
            <h4 class="form-section__title">Pilih Anak</h4>
            <div class="form-section__body space-y-4">
                <div>
                    <label class="form-label" for="ortu-bayar-siswa">Anak</label>
                    <select id="ortu-bayar-siswa" class="form-input" @disabled($children->count() <= 1)>
                        @if($children->count() > 1)
                            <option value="">Pilih anak</option>
                        @endif
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected($children->count() === 1)>
                                {{ $child->nis }} — {{ $child->name }}{{ $child->kelas ? ' · '.$child->kelas->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="ortu-bayar-saldo-box" class="hidden rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-900 dark:border-primary-900/40 dark:bg-primary-950/30 dark:text-primary-100">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>Saldo Keuangan tersedia (bukan Saldo Cashless)</span>
                        <strong id="ortu-bayar-saldo-label">Rp 0</strong>
                    </div>
                    <p id="ortu-bayar-siswa-label" class="mt-1 text-xs opacity-80"></p>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h4 class="form-section__title">Tagihan Belum Lunas</h4>
            <div class="form-section__body space-y-3">
                <p id="ortu-bayar-empty" class="hidden text-sm text-slate-500 dark:text-slate-400">Pilih anak untuk melihat tagihan yang dapat dibayar.</p>
                <div id="ortu-bayar-list" class="hidden space-y-2"></div>
                <div id="ortu-bayar-summary" class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>Total dipilih</span>
                        <strong id="ortu-bayar-total-label">Rp 0</strong>
                    </div>
                    <p id="ortu-bayar-hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>
        </section>

        <div class="modal-panel__footer flex justify-end gap-2">
            <button type="button" data-modal-close="ortu-bayar-tagihan-modal" class="btn-secondary">Batal</button>
            <button type="button" id="ortu-bayar-submit" class="btn-primary" disabled>
                <x-icon name="cash" size="sm" class="mr-1" /> Bayar dari Saldo Keuangan
            </button>
        </div>
    </div>
</x-modal>
@endpush

@push('scripts')
<script src="{{ asset('js/portal-ortu-bayar-tagihan.js') }}?v=2"></script>
@endpush
