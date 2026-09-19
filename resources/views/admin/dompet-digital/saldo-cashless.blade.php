@extends('layouts.app')

@section('title', $title)

@section('content')
<div id="saldo-cashless-page"
     data-show-url="{{ url('admin/dompet-digital/saldo-cashless') }}"
     data-transactions-url="{{ url('admin/dompet-digital/saldo-cashless') }}"
     data-withdraw-preview-url="{{ route('admin.dompet-digital.withdraw-saldo.preview') }}"
     data-lookup-rfid-url="{{ route('admin.dompet-digital.topup-saldo.lookup-rfid') }}"
     data-face-references-url="{{ route('admin.dompet-digital.withdraw-saldo.face-references') }}"
     data-face-model-url="https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights">
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card stat-card-purple">
            <p class="text-sm text-slate-500">Total Saldo</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['total_saldo'], 0, ',', '.') }}</p>
        </div>
        <div class="stat-card stat-card-blue">
            <p class="text-sm text-slate-500">Siswa Bersaldo</p>
            <p class="mt-2 text-2xl font-bold">{{ $stats['siswa_bersaldo'] }}</p>
        </div>
        <div class="stat-card stat-card-green">
            <p class="text-sm text-slate-500">Rata-rata Saldo</p>
            <p class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['rata_rata'], 0, ',', '.') }}</p>
        </div>
    </div>

    <x-admin.datatable-page
        title="Saldo Cashless"
        subtitle="Daftar saldo cashless per siswa dari ledger sccttran_cashless"
        :ajax-url="route('admin.dompet-digital.saldo-cashless.data')"
        :columns="['NIS', 'Nama', 'Kelas', 'RFID', 'Saldo Cashless', 'Transaksi Terakhir', 'Aksi']"
        :column-options="[6 => ['orderable' => false, 'searchable' => false, 'exportable' => false, 'html' => true]]"
        :default-order="[[5, 'desc']]"
        :show-export="true">
        <x-slot:actions>
            @if($manualSaldoEnabled ?? false)
                <button type="button"
                        class="btn-primary flex-1 sm:flex-none"
                        data-open-modal="topup-cashless-modal"
                        data-form-reset="topup-cashless-form"
                        data-store-url="{{ $topupStoreUrl }}"
                        data-modal-title="Top-up Saldo Cashless">
                    <x-icon name="wallet" size="sm" class="mr-1" /> Top-up Saldo
                </button>
                <button type="button"
                        class="btn-secondary flex-1 sm:flex-none"
                        data-open-modal="withdraw-cashless-modal"
                        data-form-reset="withdraw-cashless-form"
                        data-store-url="{{ $withdrawStoreUrl }}"
                        data-modal-title="Tarik Saldo Cashless">
                    <x-icon name="cash" size="sm" class="mr-1" /> Tarik Saldo
                </button>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
                    Top-up / tarik saldo manual sementara dinonaktifkan.
                </div>
            @endif
        </x-slot:actions>
        <x-slot:filters>
            <form id="filter-form" class="filter-form">
                <div>
                    <label class="form-label" for="filter-q">Cari Siswa</label>
                    <input type="text" name="q" id="filter-q" class="form-input" placeholder="Nama, NIS, atau RFID" value="{{ request('q') }}">
                </div>
                @include('admin.partials.filters.class-select', ['classes' => $classes, 'selected' => request('kelas_id'), 'id' => 'filter-kelas'])
                <x-filter-actions />
            </form>
        </x-slot:filters>
    </x-admin.datatable-page>
</div>

@if($manualSaldoEnabled ?? false)
<x-modal id="topup-cashless-modal" title="Top-up Saldo Cashless">
    <form id="topup-cashless-form"
          data-fetch-form
          data-topup-cashless-form
          data-confirm-submit
          data-confirm-builder="buildCashlessTopupConfirm"
          data-close-modal="topup-cashless-modal"
          data-default-action="{{ $topupStoreUrl }}"
          action="{{ $topupStoreUrl }}"
          method="POST"
          class="space-y-4"
          data-reload-table
          data-infaq-mode="{{ $infaqMode }}"
          data-infaq-max="{{ $infaqMax }}"
          data-infaq-tiers="{{ json_encode($infaqTiers) }}">
        @csrf
        <div>
            <p class="form-label mb-2">Metode pilih siswa</p>
            <div class="visitor-segment" role="tablist" aria-label="Metode top-up">
                <button type="button" class="visitor-segment__btn is-active" data-cashless-method="search" data-target-form="topup-cashless-form" role="tab" aria-selected="true">
                    <x-icon name="search" size="sm" class="mr-1" /> Cari Siswa
                </button>
                <button type="button" class="visitor-segment__btn" data-cashless-method="rfid" data-target-form="topup-cashless-form" role="tab" aria-selected="false">
                    <x-icon name="nfc" size="sm" class="mr-1" /> Scan RFID
                </button>
            </div>
        </div>
        <div data-method-panel="search" data-form="topup-cashless-form">
            <x-siswa-select :status="null" id="saldo-cashless-topup-siswa" />
        </div>
        <div data-method-panel="rfid" data-form="topup-cashless-form" class="hidden space-y-3">
            <div>
                <label class="form-label" for="saldo-cashless-topup-rfid">RFID Siswa</label>
                <input type="text"
                       name="rfid_uid"
                       id="saldo-cashless-topup-rfid"
                       class="form-input font-mono"
                       placeholder="Scan kartu RFID..."
                       autocomplete="off"
                       disabled>
            </div>
            <div id="topup-cashless-rfid-summary"
                 class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                <p class="font-medium text-slate-900 dark:text-white" id="topup-cashless-rfid-name">-</p>
                <p class="text-muted mt-1" id="topup-cashless-rfid-meta">-</p>
            </div>
        </div>
        <div id="topup-cashless-saldo-info"
             class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-600 dark:text-slate-400">Saldo cashless tersedia</span>
                <span id="topup-cashless-saldo-tersedia" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
            </div>
        </div>
        <div>
            <label class="form-label" for="saldo-cashless-topup-amount">Nominal (Rp)</label>
            <x-form.amount name="amount" id="saldo-cashless-topup-amount" :min="0" required />
        </div>

        @if($infaqEnabled && $infaqMode === 'optional')
            <div id="topup-infaq-section"
                 class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" id="topup-infaq-check" name="infaq_check" checked
                           class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span class="font-medium text-slate-900 dark:text-white">Saya ingin berinfaq</span>
                </label>
                <div id="topup-infaq-fields" class="mt-3">
                    <label class="form-label" for="saldo-cashless-topup-infaq-amount">Nominal Infaq (Rp)</label>
                    <x-form.amount name="infaq_amount" id="saldo-cashless-topup-infaq-amount" :min="0" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Maksimal Rp {{ number_format($infaqMax, 0, ',', '.') }}</p>
                </div>
            </div>
        @endif

        <div id="topup-summary-section"
             class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
            @if($infaqEnabled)
                <div id="topup-infaq-summary-row" class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Infaq</span>
                    <span id="topup-infaq-summary-value" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
                </div>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-400">Masuk ke saldo cashless</span>
                <span id="topup-net-credited" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
            </div>
            <div id="topup-saldo-akhir-row" class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 dark:border-slate-700">
                <span class="font-medium text-slate-700 dark:text-slate-300">Saldo Akhir</span>
                <span id="topup-saldo-akhir-value" class="font-bold text-slate-900 dark:text-white">Rp 0</span>
            </div>
        </div>

        <div>
            <label class="form-label" for="saldo-cashless-topup-description">Keterangan</label>
            <input type="text" name="description" id="saldo-cashless-topup-description" class="form-input" placeholder="Opsional">
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="topup-cashless-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="wallet" size="sm" class="mr-1" /> Proses Top Up
            </button>
        </div>
    </form>
    @if($infaqEnabled && count($infaqTiers) > 0)
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-900/50">
            <p class="mb-2 font-semibold text-slate-700 dark:text-slate-300">Nominal Infaq</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-slate-600 dark:text-slate-400">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="py-1 pr-4 font-medium">Nominal Top Up</th>
                            <th class="py-1 font-medium">Infaq</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($infaqTiers as $tier)
                        <tr>
                            <td class="py-1 pr-4">
                                Rp {{ number_format($tier['min'], 0, ',', '.') }}
                                – {{ $tier['max'] !== null ? 'Rp '.number_format($tier['max'], 0, ',', '.') : 'lebih' }}
                            </td>
                            <td class="py-1">Rp {{ number_format($tier['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
</x-modal>

<x-modal id="withdraw-cashless-modal" title="Tarik Saldo Cashless">
    <form id="withdraw-cashless-form"
          data-fetch-form
          data-withdraw-cashless-form
          data-confirm-submit
          data-confirm-builder="buildCashlessWithdrawConfirm"
          data-close-modal="withdraw-cashless-modal"
          data-default-action="{{ $withdrawStoreUrl }}"
          action="{{ $withdrawStoreUrl }}"
          method="POST"
          class="space-y-4"
          data-reload-table>
        @csrf
        <div>
            <p class="form-label mb-2">Metode pilih siswa</p>
            <div class="visitor-segment" role="tablist" aria-label="Metode tarik saldo">
                <button type="button" class="visitor-segment__btn is-active" data-cashless-method="search" data-target-form="withdraw-cashless-form" role="tab" aria-selected="true">
                    <x-icon name="search" size="sm" class="mr-1" /> Cari Siswa
                </button>
                <button type="button" class="visitor-segment__btn" data-cashless-method="rfid" data-target-form="withdraw-cashless-form" role="tab" aria-selected="false">
                    <x-icon name="nfc" size="sm" class="mr-1" /> Scan RFID
                </button>
                <button type="button" class="visitor-segment__btn" data-cashless-method="face" data-target-form="withdraw-cashless-form" role="tab" aria-selected="false">
                    <x-icon name="face-id" size="sm" class="mr-1" /> Deteksi Wajah
                </button>
            </div>
        </div>
        <div data-method-panel="search" data-form="withdraw-cashless-form">
            <x-siswa-select :status="null" id="saldo-cashless-withdraw-siswa" />
        </div>
        <div data-method-panel="rfid" data-form="withdraw-cashless-form" class="hidden space-y-3">
            <div>
                <label class="form-label" for="saldo-cashless-withdraw-rfid">RFID Siswa</label>
                <input type="text"
                       name="rfid_uid"
                       id="saldo-cashless-withdraw-rfid"
                       class="form-input font-mono"
                       placeholder="Scan kartu RFID..."
                       autocomplete="off"
                       disabled>
            </div>
            <div id="withdraw-cashless-rfid-summary"
                 class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900/40">
                <p class="font-medium text-slate-900 dark:text-white" id="withdraw-cashless-rfid-name">-</p>
                <p class="text-muted mt-1" id="withdraw-cashless-rfid-meta">-</p>
            </div>
        </div>
        <div data-method-panel="face" data-form="withdraw-cashless-form" class="hidden space-y-3">
            {{-- Siswa hasil deteksi wajah dikirim lewat input tersembunyi ini (name=siswa_id). --}}
            <input type="hidden" name="siswa_id" id="saldo-cashless-withdraw-face-siswa" value="" disabled>
            <div>
                <label class="form-label" for="withdraw-face-kelas">Batasi kelas (disarankan)</label>
                <select id="withdraw-face-kelas" class="form-input">
                    <option value="">Semua kelas ({{ $classes->count() }})</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                <p class="text-muted mt-1 text-xs">Pilih kelas untuk mempercepat & memperkuat deteksi bila siswa banyak.</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <button type="button" id="withdraw-face-toggle" class="btn-primary h-11">
                    <x-icon name="camera" size="sm" class="mr-1" /> Mulai Kamera
                </button>
                <select id="withdraw-face-camera-mode" class="form-input h-11">
                    <option value="user">Kamera Depan</option>
                    <option value="environment">Kamera Belakang</option>
                </select>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-900 to-slate-950 p-2 dark:border-slate-800">
                <div class="relative mx-auto w-full overflow-hidden rounded-2xl border border-white/15 bg-black">
                    <div class="relative aspect-video">
                        <video id="withdraw-face-video" class="h-full w-full object-cover" autoplay muted playsinline></video>
                        <canvas id="withdraw-face-overlay" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                    </div>
                </div>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400" id="withdraw-face-status">Arahkan wajah siswa ke kamera. Nominal & keterangan diisi setelah siswa terdeteksi.</p>
            <div id="withdraw-cashless-face-summary"
                 class="hidden rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm dark:border-primary-900/50 dark:bg-primary-950/30">
                <p class="font-medium text-primary-900 dark:text-primary-100" id="withdraw-cashless-face-name">-</p>
                <p class="mt-1 text-primary-700 dark:text-primary-300" id="withdraw-cashless-face-meta">-</p>
            </div>
        </div>
        <div id="withdraw-cashless-saldo-info"
             class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between gap-3">
                <span class="text-slate-600 dark:text-slate-400">Saldo cashless tersedia</span>
                <span id="withdraw-cashless-saldo-tersedia" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
            </div>
        </div>
        <div>
            <label class="form-label" for="saldo-cashless-withdraw-amount">Nominal (Rp)</label>
            <x-form.amount name="amount" id="saldo-cashless-withdraw-amount" :min="0" required />
        </div>
        <div id="withdraw-cashless-limit-info"
             class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
            <p id="withdraw-cashless-limit-text" class="font-medium">Nominal melebihi limit harian.</p>
            <p id="withdraw-cashless-pin-hint" class="mt-1 text-xs opacity-90"></p>
        </div>
        <div id="withdraw-cashless-pin-wrap" class="hidden">
            <label class="form-label" for="saldo-cashless-withdraw-pin">PIN Cashless (4 digit)</label>
            <input type="password"
                   name="pin"
                   id="saldo-cashless-withdraw-pin"
                   class="form-input font-mono tracking-widest"
                   inputmode="numeric"
                   maxlength="4"
                   autocomplete="one-time-code"
                   placeholder="••••">
            <p class="text-muted mt-1 text-xs">Wajib jika tarik saldo melebihi limit harian siswa/sekolah.</p>
        </div>
        <div id="withdraw-summary-section"
             class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-400">Saldo tersedia</span>
                <span id="withdraw-saldo-tersedia-summary" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-400">Ditarik</span>
                <span id="withdraw-amount-summary" class="font-semibold text-danger">Rp 0</span>
            </div>
            <div id="withdraw-saldo-akhir-row" class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 dark:border-slate-700">
                <span class="font-medium text-slate-700 dark:text-slate-300">Saldo Akhir</span>
                <span id="withdraw-saldo-akhir-value" class="font-bold text-slate-900 dark:text-white">Rp 0</span>
            </div>
        </div>
        <div>
            <label class="form-label" for="saldo-cashless-withdraw-description">Keterangan</label>
            <input type="text" name="description" id="saldo-cashless-withdraw-description" class="form-input" placeholder="Opsional">
        </div>
        <p class="text-muted text-xs">Dicatat di ledger cashless dengan FIDBANK <strong>CASH</strong> (cari siswa / deteksi wajah) atau <strong>RFID</strong> (scan kartu).</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-modal-close="withdraw-cashless-modal" class="btn-secondary">Batal</button>
            <button type="submit" class="btn-primary">
                <x-icon name="cash" size="sm" class="mr-1" /> Proses Tarik Saldo
            </button>
        </div>
    </form>
</x-modal>
@endif

@include('admin.dompet-digital.partials.saldo-cashless-detail-modal')
@endsection

@push('scripts')
    <script src="{{ asset('js/topup-cashless.js') }}?v=10"></script>
    <script src="{{ asset('js/saldo-cashless.js') }}?v=5"></script>
    @if($manualSaldoEnabled ?? false)
        <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
        <script src="{{ asset('js/saldo-cashless-face.js') }}?v=2"></script>
    @endif
@endpush
