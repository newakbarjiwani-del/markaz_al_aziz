@extends('layouts.app')

@section('title', $title)

@section('content')
<x-portal.saldo-legend highlight="keuangan" />

<div class="card mb-6 p-5">
    <div class="grid grid-cols-2 gap-x-4 gap-y-5 lg:grid-cols-4">
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Saldo Keuangan</p>
            <p class="mt-1 break-words text-xl font-bold text-primary-700 dark:text-primary-300 sm:text-2xl">Rp {{ number_format($stats['saldo'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Masuk Bulan Ini</p>
            <p class="mt-1 break-words text-xl font-bold text-emerald-600 dark:text-emerald-400 sm:text-2xl">Rp {{ number_format($stats['bulan_ini_kredit'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Keluar Bulan Ini</p>
            <p class="mt-1 break-words text-xl font-bold text-amber-600 dark:text-amber-400 sm:text-2xl">Rp {{ number_format($stats['bulan_ini_debet'], 0, ',', '.') }}</p>
        </div>
        <div class="portal-stat-segment">
            <p class="text-muted text-xs font-medium sm:text-sm">Total Transaksi</p>
            <p class="mt-1 break-words text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">{{ number_format($stats['total_transaksi']) }}</p>
        </div>
    </div>
</div>

@if($balanceCards->isNotEmpty())
    <div class="mb-6 grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 md:grid-cols-3">
        @foreach($balanceCards as $card)
            <div class="card p-5">
                <p class="font-semibold text-slate-900 dark:text-white">{{ $card['siswa']->name }}</p>
                <p class="text-muted mb-2 text-sm">NIS {{ $card['siswa']->nis }} · {{ $card['siswa']->kelas?->name ?? '-' }}</p>
                <div class="mb-4">
                    <x-portal.vano
                        :value="$card['siswa']->virtualAccountNumber()"
                        hint="Untuk transfer pembayaran tagihan"
                    />
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Saldo Keuangan</dt>
                        <dd class="font-semibold text-primary-700 dark:text-primary-300">Rp {{ number_format($card['saldo'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>
        @endforeach
    </div>
@endif

<x-admin.datatable-page
    title="Riwayat Saldo Keuangan"
    :ajax-url="route('portal.ortu.pembayaran.data')"
    :columns="['NIS', 'Nama', 'Kelas', 'Tanggal', 'Metode', 'Kredit', 'Debet', 'Referensi', 'Channel']"
    :show-export="true">
    <x-slot:filters>
        <form id="filter-form" class="filter-form">
            @if(isset($children) && $children->count() > 1)
                <div>
                    <label class="form-label">Pilih Anak</label>
                    <select name="siswa_id" class="form-input w-full">
                        <option value="">Semua anak</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected((int) request('siswa_id') === $child->id)>
                                {{ $child->nis }} — {{ $child->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            @include('admin.partials.filters.date-range', [
                'from' => request('date_from'),
                'to' => request('date_to'),
                'colClass' => '',
            ])
            <div>
                <label class="form-label">Metode</label>
                <select name="metode" class="form-input">
                    <option value="">Semua</option>
                    @foreach($metodeOptions as $metode)
                        <option value="{{ $metode }}" @selected(request('metode') === $metode)>{{ $metode }}</option>
                    @endforeach
                </select>
            </div>
            <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
