@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200">
        Riwayat penarikan tunai pendapatan kantin (audit off-ledger).
        Membatalkan penarikan akan mengembalikan sisa belum ditarik operator.
        Kembali ke
        <a href="{{ route('admin.dompet-digital.pendapatan-kantin.index') }}" class="font-medium text-primary-700 underline underline-offset-2 dark:text-primary-300">Pendapatan Kantin</a>.
    </div>

    <x-admin.datatable-page
        title="Riwayat Penarikan Pendapatan Kantin"
        :ajax-url="route('admin.dompet-digital.pendapatan-kantin.penarikan.data')"
        :columns="['Waktu', 'No. Ref', 'Operator', 'Sekolah', 'Nominal', 'Metode', 'Ditarik Oleh', 'Keterangan', 'Aksi']"
        :column-options="[
            4 => ['type' => 'number'],
            8 => ['html' => true, 'orderable' => false, 'searchable' => false, 'exportable' => false],
        ]"
        :default-order="[[0, 'desc']]"
        :show-export="true">
        <x-slot:actions>
            <a href="{{ route('admin.dompet-digital.pendapatan-kantin.index') }}" class="btn-secondary flex-1 sm:flex-none">
                <x-icon name="arrow-left" size="sm" class="mr-1" /> Pendapatan Kantin
            </a>
        </x-slot:actions>
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
                <div>
                    <label class="form-label" for="filter-q">Cari</label>
                    <input type="text" name="q" id="filter-q" class="form-input" placeholder="No. ref, operator, keterangan" value="{{ request('q') }}">
                </div>
                @if($showSekolahFilter)
                    @include('admin.partials.filters.sekolah-select', [
                        'schools' => $schools,
                        'selected' => request('sekolah_id'),
                        'id' => 'filter-sekolah',
                    ])
                @endif
                <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
@endsection
