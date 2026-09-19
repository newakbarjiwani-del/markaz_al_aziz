@extends('layouts.app')

@section('title', '')

@section('content')
<div class="portal-dashboard dashboard-page">
    <x-portal.dashboard-hero
        :name="$operator->name"
        :subtitle="$operator->sekolah?->name ?? 'Operator Kantin'"
        tip="Kelola transaksi, menu, dan ringkasan penjualan kantin sekolah."
        highlight-label="Omzet Hari Ini"
        :highlight-value="'Rp '.number_format($stats['omzet_hari_ini'], 0, ',', '.')"
        tone="accent"
        :chips="[
            ['label' => 'Transaksi Hari Ini', 'value' => $stats['transaksi_hari_ini']],
            ['label' => 'Dompet Aktif', 'value' => $stats['dompet_aktif']],
            ['label' => 'Menu Aktif', 'value' => $stats['menu_aktif']],
            ['label' => 'Total Saldo Kantin', 'value' => 'Rp '.number_format($stats['total_dompet_kantin'], 0, ',', '.')],
        ]"
    />

    <x-portal.quick-actions
        title="Layanan Kantin"
        description="Akses cepat fitur operator kantin."
        :actions="[
            ['label' => 'Belanja', 'route' => 'portal.kantin.pos', 'icon' => 'nfc', 'tone' => 'primary'],
            ['label' => 'Daftar Menu', 'route' => 'portal.kantin.menu.index', 'icon' => 'tools-kitchen-2', 'tone' => 'blue'],
            ['label' => 'Transaksi', 'route' => 'portal.kantin.transaksi.index', 'icon' => 'receipt', 'tone' => 'accent'],
            ['label' => 'Ringkasan', 'route' => 'portal.kantin.ringkasan', 'icon' => 'chart-bar', 'tone' => 'purple'],
        ]"
    />

    <div class="card portal-dashboard-panel">
        <x-portal.panel-head
            title="Aktivitas Terbaru"
            subtitle="Transaksi kantin terakhir"
            :action-url="route('portal.kantin.transaksi.index')"
            action-label="Lihat Semua"
        />

        @if($recentTransactions->isEmpty())
            <div class="p-6 text-center text-muted">Belum ada transaksi kantin.</div>
        @else
            <div class="divide-y border-t border-surface-border-subtle">
                @foreach($recentTransactions as $trx)
                    @php
                        $isCredit = $trx->KREDIT > 0;
                        $amount = $isCredit ? $trx->KREDIT : $trx->DEBET;
                    @endphp
                    <div class="portal-activity-item">
                        <div class="portal-activity-item__avatar">
                            {{ collect(explode(' ', $trx->siswa?->name ?? '?'))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $trx->siswa?->name ?? '-' }}</p>
                            <p class="text-muted text-sm">{{ $trx->METODE ?? '-' }} · {{ strtoupper($trx->wallet ?? '-') }} · {{ $trx->TRXDATE ? $trx->TRXDATE->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <p class="portal-activity-item__amount {{ $isCredit ? 'portal-activity-item__amount--credit' : 'portal-activity-item__amount--debit' }}">
                            {{ $isCredit ? '+' : '-' }}Rp {{ number_format($amount, 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
