@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Tagihan', 'route' => 'admin.keuangan.tagihan.index', 'icon' => 'file-invoice', 'tone' => 'warning'],
            ['label' => 'Kirim Tagihan WA', 'route' => 'admin.keuangan.kirim-tagihan-wa.index', 'icon' => 'brand-whatsapp', 'tone' => 'success'],
            ['label' => 'Pembayaran', 'route' => 'admin.keuangan.pembayaran.index', 'icon' => 'cash', 'tone' => 'success'],
            ['label' => 'Riwayat Pembayaran', 'route' => 'admin.keuangan.riwayat-pembayaran.index', 'icon' => 'history', 'tone' => 'info'],
            ['label' => 'Saldo Siswa', 'route' => 'admin.keuangan.saldo-siswa.index', 'icon' => 'wallet', 'tone' => 'info'],
            ['label' => 'Pindah Saldo', 'route' => 'admin.keuangan.pindah-saldo', 'icon' => 'arrows-exchange', 'tone' => 'purple'],
            ['label' => 'Potongan Siswa', 'route' => 'admin.keuangan.potongan-siswa.index', 'icon' => 'percentage', 'tone' => 'warning'],
            ['label' => 'Laporan', 'route' => 'admin.keuangan.laporan-keuangan', 'icon' => 'report-analytics', 'tone' => 'primary'],
        ]"
    />

    <div class="dashboard-stats">
        @foreach($stats as $stat)
            <x-stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :accent="$stat['accent']"
                :currency="!empty($stat['currency'])"
            />
        @endforeach
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="dashboard-grid dashboard-grid--2">
        <div class="card dashboard-table-card">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Tagihan Terbaru</h2>
            </div>
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Siswa</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Jenis</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Jumlah</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTagihan as $tagihan)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $tagihan->siswa?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $tagihan->jenis }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">Rp {{ number_format($tagihan->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge {{ $tagihan->statusBadgeClass() }}">
                                        {{ $tagihan->statusLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card dashboard-table-card">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pembayaran Terbaru</h2>
            </div>
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Siswa</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Nominal</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Metode</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPembayaran as $bayar)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $bayar->siswa?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">Rp {{ number_format($bayar->total_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($bayar->method ?? '-') }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $bayar->paid_dt?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
