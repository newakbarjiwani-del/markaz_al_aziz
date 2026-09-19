@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Saldo Cashless', 'route' => 'admin.dompet-digital.saldo-cashless.index', 'icon' => 'wallet', 'tone' => 'success'],
            ['label' => 'Saldo RFID', 'route' => 'admin.dompet-digital.saldo-rfid.index', 'icon' => 'nfc', 'tone' => 'info'],
            ['label' => 'Transaksi', 'route' => 'admin.dompet-digital.transaksi.index', 'icon' => 'history', 'tone' => 'warning'],
            ['label' => 'Pendapatan Kantin', 'route' => 'admin.dompet-digital.pendapatan-kantin.index', 'icon' => 'building-store', 'tone' => 'warning'],
            ['label' => 'Riwayat Penarikan', 'route' => 'admin.dompet-digital.pendapatan-kantin.penarikan.index', 'icon' => 'cash', 'tone' => 'purple'],
            ['label' => 'Limit & Kontrol', 'route' => 'admin.dompet-digital.limit-kontrol', 'icon' => 'adjustments-horizontal', 'tone' => 'info'],
            ['label' => 'Kontrol RFID', 'route' => 'admin.dompet-digital.rfid-kontrol', 'icon' => 'nfc', 'tone' => 'primary'],
            ['label' => 'Kartu / QR & POS', 'route' => 'admin.dompet-digital.kartu-pos', 'icon' => 'qrcode', 'tone' => 'neutral'],
        ]"
    />

    <div class="dashboard-stats">
        @foreach($stats as $stat)
            <x-stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :accent="$stat['accent']"
                :currency="!empty($stat['currency'])"
                :hint="$stat['hint'] ?? null"
            />
        @endforeach
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="dashboard-grid dashboard-grid--2">
        <div class="card dashboard-table-card">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Transaksi Terbaru</h2>
            </div>
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Waktu</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Siswa</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Metode</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransaksi as $row)
                            @php
                                $nominal = (int) $row->DEBET > 0 ? (int) $row->DEBET : (int) $row->KREDIT;
                            @endphp
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    {{ \App\Support\DisplayDate::datetime($row->TRXDATE) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->siswa?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $row->METODE }}{{ $row->wallet ? ' · '.strtoupper($row->wallet) : '' }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card dashboard-table-card">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 p-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Penarikan Kantin Terbaru</h2>
                <a href="{{ route('admin.dompet-digital.pendapatan-kantin.penarikan.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">Semua</a>
            </div>
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Waktu</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Operator</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Ref</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPenarikan as $row)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    {{ \App\Support\DisplayDate::datetime($row->settled_at) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->kantinUser?->name ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $row->noreff }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada penarikan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
