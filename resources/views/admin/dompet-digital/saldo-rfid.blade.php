@extends('layouts.app')

@section('content')
<style>
    body.rfid-kiosk-mode .app-sidebar,
    body.rfid-kiosk-mode .app-topbar,
    body.rfid-kiosk-mode .mobile-bottom-nav {
        display: none !important;
    }
    body.rfid-kiosk-mode .app-main {
        margin: 0 !important;
        padding: 1.25rem !important;
        max-width: none !important;
    }
    body.rfid-kiosk-mode .kiosk-page-wrapper {
        max-width: 1200px;
        margin: 0 auto;
    }
    body.rfid-kiosk-mode .rfid-recent-feed {
        max-height: min(58vh, 560px);
    }

    .scanner-input-container {
        position: absolute;
        top: -9999px;
        left: -9999px;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
    }
    #rfid-input {
        border: none;
        outline: none;
        background: transparent;
        color: transparent;
    }

    .balance-amount {
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
</style>

<div class="kiosk-page-wrapper" data-finance-enabled="{{ !empty($financeBalanceEnabled) ? '1' : '0' }}">
    <div class="kiosk-header rfid-kiosk-header flex justify-between items-center border-b pb-4 mb-6">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-[var(--text-primary)] kiosk-title">Kiosk Saldo RFID</h1>
            <p class="text-xs text-[var(--text-muted)]">Tempelkan kartu RFID siswa untuk menampilkan saldo cashless</p>
        </div>
        <div>
            <button type="button" id="kiosk-toggle-btn" class="btn-primary flex items-center gap-1.5 px-3 py-1.5 text-sm">
                <x-icon name="maximize" size="sm" />
                <span>Mode Kiosk</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[440px_1fr] gap-6 mb-6">
        <div class="flex flex-col gap-5">
            <div class="card p-5 text-center">
                <div id="kiosk-date" class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">-</div>
                <div id="kiosk-time" class="mt-1 text-5xl font-extrabold tracking-tight text-primary-600 dark:text-primary-400 font-mono">-</div>
            </div>

            <div class="card p-6 text-center relative overflow-hidden flex flex-col gap-6" id="status-panel">
                <div class="h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300" id="status-bar"></div>

                <form id="rfid-form" class="scanner-input-container">
                    <input type="text" id="rfid-input" autocomplete="off" autofocus placeholder="Scan kartu RFID...">
                </form>

                <div id="panel-idle" class="flex flex-col gap-5 py-4">
                    <div class="w-36 h-36 mx-auto rounded-full border-2 border-dashed border-primary-300 dark:border-primary-700 flex items-center justify-center" id="pulse-zone">
                        <x-icon name="wallet" size="lg" class="text-primary-600 dark:text-primary-400 text-4xl" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[var(--text-primary)]" id="status-headline">Silakan Tempelkan Kartu</h3>
                        <p class="mt-1 text-sm text-[var(--text-muted)]" id="status-subline">Saldo cashless akan ditampilkan setelah scan</p>
                    </div>
                </div>

                <div id="panel-result" class="hidden flex flex-col items-center gap-5 py-2">
                    <div class="relative">
                        <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center shadow-inner" id="result-photo-container">
                            <x-icon name="user" size="lg" class="text-slate-400 text-5xl" id="result-photo-placeholder" />
                            <img src="" alt="Foto siswa" id="result-photo" class="hidden w-full h-full object-cover">
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm shadow bg-primary-600" id="result-badge-container">
                            <x-icon name="check" size="xs" id="result-badge-icon" />
                        </div>
                    </div>

                    <div class="w-full">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 truncate px-2" id="result-name">-</h4>
                        <div class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400" id="result-class-nis">-</div>
                        <div class="mt-0.5 text-xs text-slate-500" id="result-school">-</div>
                    </div>

                    <div class="w-full rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-200/80 dark:border-primary-800 px-4 py-4 text-center">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-primary-700 dark:text-primary-300">Saldo Cashless</div>
                        <div class="balance-amount mt-1 text-3xl font-extrabold text-primary-700 dark:text-primary-200" id="result-cashless-balance">Rp 0</div>
                    </div>

                    {{-- Finance panel prepared for later; hidden and must not trigger finance API --}}
                    <div
                        id="finance-balance-panel"
                        class="w-full rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 px-4 py-4 text-center hidden"
                        data-enabled="0"
                        aria-hidden="true"
                    >
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Saldo Keuangan</div>
                        <div class="balance-amount mt-1 text-2xl font-extrabold text-slate-700 dark:text-slate-200" id="result-finance-balance">—</div>
                        <p class="mt-1 text-[10px] text-slate-400">Belum diaktifkan</p>
                    </div>
                </div>
            </div>

            <div class="text-center text-xs py-1.5 px-3 rounded-lg bg-primary-50/60 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 flex items-center justify-center gap-1.5" id="focus-indicator">
                <span class="w-2.5 h-2.5 rounded-full bg-primary-500 animate-pulse" id="focus-indicator-dot"></span>
                <span id="focus-indicator-text" class="font-bold uppercase tracking-wider text-[10px]">Siap scan RFID</span>
            </div>
        </div>

        <div class="card p-5 flex flex-col gap-4 lg:min-h-[640px]">
            <div class="flex flex-wrap justify-between items-center gap-2 border-b border-[var(--surface-border-subtle)] pb-3">
                <div>
                    <h3 class="font-bold flex items-center gap-2 text-[var(--text-primary)] text-base">
                        <x-icon name="history" size="sm" class="text-primary-600 dark:text-primary-400" />
                        <span>Transaksi Cashless Terkini</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">Scan terakhir &amp; transaksi cashless hari ini</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-md bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 font-bold border border-primary-200/80 dark:border-primary-800" id="today-tx-count">Hari ini: 0</span>
            </div>

            <div id="student-recent-section" class="hidden border-b border-[var(--surface-border-subtle)] pb-4 mb-1">
                <h4 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">Riwayat singkat siswa</h4>
                <div id="student-recent-list" class="flex flex-col gap-2"></div>
            </div>

            <div class="rfid-scroll rfid-recent-feed flex flex-col gap-3 overflow-y-auto" id="recent-feed-container">
                <div class="text-center py-12 text-[var(--text-muted)] text-sm" id="feed-empty-state">
                    <x-icon name="credit-card-off" size="lg" class="block mx-auto mb-2 text-2xl" />
                    Belum ada transaksi cashless hari ini
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/saldo-rfid-kiosk.js') }}?v=1"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initSaldoRfidKiosk({
            lookupUrl: @json(route('admin.dompet-digital.saldo-rfid.lookup')),
            recentUrl: @json(route('admin.dompet-digital.saldo-rfid.recent')),
            csrfToken: @json(csrf_token()),
        });
    });
</script>
@endpush
