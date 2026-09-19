@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="card mx-auto max-w-2xl p-6">
        <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">Pindah Saldo Keuangan → Cashless</h2>
        <p class="mb-6 text-sm text-slate-500">
            Pindahkan saldo dari saldo keuangan (SPP) ke saldo cashless siswa.
            Saldo akan dikurangi sebesar nominal + biaya admin
            @if ($infaqEnabled)
                + infaq
            @endif.
        </p>

        <form data-fetch-form action="{{ route('admin.keuangan.pindah-saldo.store') }}" method="POST" class="grid gap-5"
            data-biaya-admin="{{ $biayaAdmin }}" data-infaq-mode="{{ $infaqMode }}" data-infaq-max="{{ $infaqMax }}"
            data-infaq-tiers="{{ json_encode($infaqTiers) }}">
            @csrf

            <x-siswa-select :status="null" id="pindah-siswa" />

            <div id="saldo-info"
                class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Saldo keuangan tersedia</span>
                    <span id="saldo-tersedia" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
                </div>
            </div>

            <div>
                <label class="form-label" for="pindah-amount">Nominal Pindah (Rp)</label>
                <x-form.amount name="amount" id="pindah-amount" :min="0" required />
            </div>

            @if($infaqEnabled && $infaqMode === 'optional')
                <div id="infaq-section"
                    class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="infaq-check" name="infaq_check" checked
                            class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <span class="font-medium text-slate-900 dark:text-white">Saya ingin berinfaq</span>
                    </label>
                    <div id="infaq-fields" class="mt-3">
                        <label class="form-label" for="pindah-infaq-amount">Nominal Infaq (Rp)</label>
                        <x-form.amount name="infaq_amount" id="pindah-infaq-amount" :min="0" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Maksimal Rp
                            {{ number_format($infaqMax, 0, ',', '.') }}</p>
                    </div>
                </div>
            @endif

            <div id="admin-pindah-summary-section"
                class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900/50">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Biaya admin</span>
                    <span class="font-semibold text-slate-900 dark:text-white">Rp
                        {{ number_format($biayaAdmin, 0, ',', '.') }}</span>
                </div>
                @if($infaqEnabled)
                    <div id="infaq-summary-row" class="mt-1 flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Infaq</span>
                        <span id="infaq-summary-value" class="font-semibold text-slate-900 dark:text-white">Rp 0</span>
                    </div>
                @endif
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Total dipotong dari saldo keuangan</span>
                    <span class="font-semibold text-slate-900 dark:text-white">Rp <span id="total-potongan">0</span></span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 dark:border-slate-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Estimasi Saldo Cashless setelah pindah</span>
                    <span class="font-bold text-slate-900 dark:text-white">Rp <span id="admin-pindah-estimasi-cashless">0</span></span>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Pindahkan Saldo</button>
            </div>
        </form>

        @if($infaqEnabled && count($infaqTiers) > 0)
            <div
                class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-900/50">
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
                                        – {{ $tier['max'] !== null ? 'Rp ' . number_format($tier['max'], 0, ',', '.') : 'lebih' }}
                                    </td>
                                    <td class="py-1">Rp {{ number_format($tier['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var amountInput = document.querySelector('input[name="amount"]');
            var totalSpan = document.getElementById('total-potongan');
            var saldoInfo = document.getElementById('saldo-info');
            var saldoTersedia = document.getElementById('saldo-tersedia');
            var estimasiCashless = document.getElementById('admin-pindah-estimasi-cashless');
            var currentCashless = 0;
            var biayaAdmin = {{ $biayaAdmin }};
            var infaqEnabled = {{ $infaqEnabled ? 'true' : 'false' }};
            var infaqMode = '{{ $infaqMode }}';
            var infaqMax = {{ $infaqMax }};
            var infaqTiers = @json($infaqTiers);
            var infaqCheck = document.getElementById('infaq-check');
            var infaqFields = document.getElementById('infaq-fields');
            var infaqAmountInput = document.getElementById('pindah-infaq-amount');
            var infaqSummaryValue = document.getElementById('infaq-summary-value');

            function calculateInfaq(amount) {
                if (!infaqEnabled || amount <= 0) return 0;
                for (var i = 0; i < infaqTiers.length; i++) {
                    var tier = infaqTiers[i];
                    var tierMax = tier.max !== null ? tier.max : Infinity;
                    if (amount >= tier.min && amount <= tierMax) {
                        return Math.min(tier.amount, infaqMax);
                    }
                }
                return 0;
            }

            function getInfaqAmount() {
                if (!infaqEnabled) return 0;
                if (infaqMode === 'optional' && infaqCheck && !infaqCheck.checked) return 0;
                if (infaqMode === 'optional' && infaqAmountInput) {
                    var val = parseInt(infaqAmountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                    return Math.min(val, infaqMax);
                }
                var val = parseInt(amountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                return calculateInfaq(val);
            }

            var summarySection = document.getElementById('admin-pindah-summary-section');

            function updateEstimatedCashless() {
                if (!estimasiCashless) return;
                var val = parseInt(amountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                var infaq = getInfaqAmount();
                estimasiCashless.textContent = (currentCashless + val - (infaq + biayaAdmin)).toLocaleString('id-ID');
            }

            function updateTotal() {
                var val = parseInt(amountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                var infaq = getInfaqAmount();
                totalSpan.textContent = (val).toLocaleString('id-ID');
                if (infaqSummaryValue) {
                    infaqSummaryValue.textContent = 'Rp ' + infaq.toLocaleString('id-ID');
                }
                if (infaqAmountInput && infaqMode === 'optional' && (!infaqAmountInput.value || infaqAmountInput.value === '0')) {
                    infaqAmountInput.value = calculateInfaq(val);
                }
                // Show summary only when there's an amount and a student is selected
                var hasStudent = !saldoInfo.classList.contains('hidden');
                if (summarySection) {
                    summarySection.classList.toggle('hidden', !(val > 0 && hasStudent));
                }
                updateEstimatedCashless();
            }

            amountInput?.addEventListener('input', function () {
                var val = parseInt(amountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                if (infaqAmountInput && infaqMode === 'optional') {
                    infaqAmountInput.value = calculateInfaq(val);
                }
                updateTotal();
            });

            if (infaqCheck) {
                infaqCheck.addEventListener('change', function () {
                    if (infaqFields) {
                        infaqFields.classList.toggle('hidden', !infaqCheck.checked);
                    }
                    if (!infaqCheck.checked && infaqAmountInput) {
                        infaqAmountInput.value = '0';
                    }
                    updateTotal();
                });
            }

            if (infaqAmountInput) {
                infaqAmountInput.addEventListener('input', updateTotal);
            }

            updateTotal();

            var siswaSelect = document.querySelector('[data-ajax-select][name="siswa_id"]');
            if (siswaSelect && window.jQuery) {
                window.jQuery(siswaSelect).on('select2:select', function (event) {
                    var siswaId = event.params?.data?.id;
                    if (!siswaId) return;
                    fetch('{{ url('admin/keuangan/saldo-siswa') }}/' + siswaId)
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res.success && res.data?.balance !== undefined) {
                                var balance = res.data.balance;
                                saldoTersedia.textContent = 'Rp ' + Number(balance).toLocaleString('id-ID');
                                saldoInfo.classList.remove('hidden');
                                currentCashless = Number(res.data.cashless_balance || 0);
                                // Re-evaluate summary visibility now that student is selected
                                var val = parseInt(amountInput.value.replace(/[^0-9]/g, ''), 10) || 0;
                                if (summarySection) {
                                    summarySection.classList.toggle('hidden', !(val > 0));
                                }
                                updateEstimatedCashless();
                            }
                        });
                });
                window.jQuery(siswaSelect).on('select2:clear', function () {
                    saldoInfo.classList.add('hidden');
                    saldoTersedia.textContent = 'Rp 0';
                    currentCashless = 0;
                    if (estimasiCashless) estimasiCashless.textContent = '0';
                    if (summarySection) {
                        summarySection.classList.add('hidden');
                    }
                });
            }
        });
    </script>
@endpush