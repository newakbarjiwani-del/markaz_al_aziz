@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-card stat-card-blue">
        <p class="text-sm text-slate-500">Tagihan Bulan Ini</p>
        <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['tagihan_bulan'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card stat-card-green">
        <p class="text-sm text-slate-500">Penerimaan Bulan Ini</p>
        <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['penerimaan_bulan'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card stat-card-amber">
        <p class="text-sm text-slate-500">Total Tunggakan</p>
        <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['tunggakan'], 0, ',', '.') }}</p>
    </div>
    <div class="stat-card stat-card-purple">
        <p class="text-sm text-slate-500">Tingkat Pelunasan</p>
        <p class="mt-2 text-2xl font-bold">{{ $stats['tingkat_pelunasan'] }}%</p>
    </div>
</div>

<div class="mb-6 grid gap-6 lg:grid-cols-2">
    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Rekap per Jenis Tagihan</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2 text-left font-medium text-slate-500">Jenis</th>
                        <th class="py-2 text-right font-medium text-slate-500">Total</th>
                        <th class="py-2 text-right font-medium text-slate-500">Terbayar</th>
                        <th class="py-2 text-right font-medium text-slate-500">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byJenis as $row)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2 font-medium">{{ $row->jenis }}</td>
                            <td class="py-2 text-right">Rp {{ number_format($row->total, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">Rp {{ number_format($row->terbayar, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">Rp {{ number_format(max(0, $row->total - $row->terbayar), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-slate-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Tren Penerimaan (6 Bulan)</h2>
        <div class="space-y-3">
            @php $maxTrend = $monthlyTrend->max('total') ?: 1; @endphp
            @forelse($monthlyTrend as $trend)
                <div>
                    <div class="mb-1 flex justify-between text-xs text-slate-500">
                        <span>{{ $trend->bulan }}</span>
                        <span>Rp {{ number_format($trend->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ min(100, ($trend->total / $maxTrend) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Belum ada data penerimaan.</p>
            @endforelse
        </div>
    </div>
</div>

<x-admin.datatable-page
    title="Detail Penerimaan"
    :ajax-url="$ajaxUrl ?? route('admin.keuangan.laporan-keuangan.data')"
    :columns="['Tanggal', 'NIS', 'Nama', 'Kelas', 'Jenis', 'Periode', 'Nominal', 'Metode', 'Referensi']">
    <x-slot:filters>
        <form id="filter-form" data-filter-mode="navigate" class="filter-form">
        @include('admin.partials.filters.date-range', ['from' => $dateFrom, 'to' => $dateTo, 'fromId' => 'filter-date-from', 'toId' => 'filter-date-to'])
        @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
        <div>
            <label class="form-label" for="filter-jenis-tagihan">Jenis Tagihan</label>
            <select name="jenis_tagihan_id" id="filter-jenis-tagihan" class="form-input">
                <option value="">Semua</option>
                @foreach($jenisTagihanList as $jenisTagihan)
                    <option value="{{ $jenisTagihan->id }}" @selected((string) $selectedJenisTagihanId === (string) $jenisTagihan->id)>
                        {{ $jenisTagihan->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-method">Metode</label>
            <select name="method" id="filter-method" class="form-input">
                <option value="">Semua</option>
                <option value="transfer" @selected(request('method') === 'transfer')>Transfer</option>
                <option value="tunai" @selected(request('method') === 'tunai')>Tunai</option>
                <option value="qris" @selected(request('method') === 'qris')>QRIS</option>
                <option value="va" @selected(request('method') === 'va')>Virtual Account</option>
            </select>
        </div>
        <x-filter-actions />
        </form>
    </x-slot:filters>
</x-admin.datatable-page>
@endsection
