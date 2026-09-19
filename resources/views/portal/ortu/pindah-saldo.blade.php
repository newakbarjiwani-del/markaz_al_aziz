@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="card mx-auto max-w-2xl p-6">
    <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Pindah Saldo Keuangan → Cashless</h2>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
        Pindahkan <strong>Saldo Keuangan</strong> (untuk tagihan/SPP) ke <strong>Saldo Cashless</strong> (uang saku kantin).
        @if($biayaAdmin > 0)
            Biaya admin Rp {{ number_format($biayaAdmin, 0, ',', '.') }} dipotong dari cashless setelah transfer.
        @endif
        @if($infaqEnabled) + infaq @endif
    </p>

    <div class="mb-5 rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-900 dark:border-primary-900/40 dark:bg-primary-950/40 dark:text-primary-100">
        <p class="font-semibold">Alur pindah saldo</p>
        <p class="mt-1">Dari: <strong>Saldo Keuangan</strong> (finance/SPP) → Ke: <strong>Saldo Cashless</strong> (uang saku).</p>
        <p class="mt-1 text-primary-700 dark:text-primary-200">Saldo ini terpisah, jadi transfer diperlukan agar dana bisa dipakai untuk transaksi cashless.</p>
    </div>

    @if($children->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
            Belum ada data anak aktif yang terhubung ke akun Anda.
        </div>
    @else
        <form data-fetch-form
              id="ortu-pindah-saldo-form"
              action="{{ route('portal.ortu.pindah-saldo.store') }}"
              method="POST"
              class="grid gap-5"
              data-saldo-url="{{ route('portal.ortu.pindah-saldo.saldo') }}"
              data-biaya-admin="{{ $biayaAdmin }}"
              data-infaq-mode="{{ $infaqMode }}"
              data-infaq-max="{{ $infaqMax }}"
              data-infaq-tiers="{{ json_encode($infaqTiers) }}">
            @csrf

            <div>
                <label class="form-label" for="ortu-pindah-siswa">Anak</label>
                <select name="siswa_id" id="ortu-pindah-siswa" class="form-input" required @disabled($children->count() <= 1)>
                    @if($children->count() > 1)
                        <option value="">Pilih anak</option>
                    @endif
                    @foreach($children as $child)
                        <option value="{{ $child->id }}"
                                @selected($children->count() === 1)
                                data-balance-keuangan="{{ (int) ($balancesKeuangan[$child->id] ?? 0) }}"
                                data-balance-cashless="{{ (int) ($balancesCashless[$child->id] ?? 0) }}">
                            {{ $child->nis }} — {{ $child->name }}{{ $child->kelas ? ' · '.$child->kelas->name : '' }}
                        </option>
                    @endforeach
                </select>
                @if($children->count() <= 1)
                    <input type="hidden" name="siswa_id" value="{{ $children->first()->id }}">
                @endif
            </div>

            <div id="ortu-pindah-saldo-info" class="{{ $children->count() === 1 ? '' : 'hidden' }} rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Saldo Keuangan (sumber)</span>
                    <span id="ortu-pindah-saldo-tersedia" class="font-semibold text-slate-900 dark:text-white">
                        Rp {{ number_format($children->count() === 1 ? (int) ($balancesKeuangan[$children->first()->id] ?? 0) : 0, 0, ',', '.') }}
                    </span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Saldo Cashless (tujuan)</span>
                    <span id="ortu-pindah-saldo-cashless" class="font-semibold text-slate-900 dark:text-white">
                        Rp {{ number_format($children->count() === 1 ? (int) ($balancesCashless[$children->first()->id] ?? 0) : 0, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div>
                <label class="form-label" for="ortu-pindah-amount">Nominal Pindah (Rp)</label>
                <x-form.amount name="amount" id="ortu-pindah-amount" :min="0" required />
            </div>

            @if($infaqEnabled && $infaqMode === 'optional')
            <div id="ortu-infaq-section" class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="ortu-infaq-check" name="infaq_check" checked class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <span class="font-medium text-slate-900 dark:text-white">Saya ingin berinfaq</span>
                </label>
                <div id="ortu-infaq-fields" class="mt-3">
                    <label class="form-label" for="ortu-pindah-infaq-amount">Nominal Infaq (Rp)</label>
                    <x-form.amount name="infaq_amount" id="ortu-pindah-infaq-amount" :min="0" />
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Maksimal Rp {{ number_format($infaqMax, 0, ',', '.') }}</p>
                </div>
            </div>
            @endif

            <div id="ortu-pindah-summary-section" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Biaya admin</span>
                    <span class="font-semibold text-slate-900 dark:text-white">Rp {{ number_format($biayaAdmin, 0, ',', '.') }}</span>
                </div>
                @if($infaqEnabled)
                <div id="ortu-infaq-summary-row" class="mt-1 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Infaq</span>
                    <span id="ortu-infaq-summary-value" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
                </div>
                @endif
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Total potongan dari Saldo Keuangan</span>
                    <span class="font-semibold text-slate-900 dark:text-white">Rp <span id="ortu-pindah-total">0</span></span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Estimasi Saldo Cashless setelah pindah</span>
                    <span class="font-semibold text-slate-900 dark:text-white">Rp <span id="ortu-pindah-estimasi-cashless">0</span></span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Pindahkan Saldo</button>
            </div>
        </form>

        @if($infaqEnabled && count($infaqTiers) > 0)
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-900/50">
            <p class="mb-2 font-semibold text-slate-700 dark:text-slate-300">Nominal Infaq</p>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-slate-600 dark:text-slate-400">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="py-1 pr-4 font-medium">Nominal Pindah</th>
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
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/portal-ortu-pindah-saldo.js') }}?v=6"></script>
@endpush
