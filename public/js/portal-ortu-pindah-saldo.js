(function () {
    function formatRp(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function parseAmount(input) {
        if (!input) {
            return 0;
        }

        return parseInt(String(input.value).replace(/[^0-9]/g, ''), 10) || 0;
    }

    function calculateInfaq(amount, infaqTiers, infaqMax) {
        if (amount <= 0) {
            return 0;
        }
        for (var i = 0; i < infaqTiers.length; i++) {
            var tier = infaqTiers[i];
            var tierMax = tier.max !== null ? tier.max : Infinity;
            if (amount >= tier.min && amount <= tierMax) {
                return Math.min(tier.amount, infaqMax);
            }
        }
        return 0;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ortu-pindah-saldo-form');
        if (!form) {
            return;
        }

        var siswaSelect = document.getElementById('ortu-pindah-siswa');
        var amountInput = document.getElementById('ortu-pindah-amount');
        var totalSpan = document.getElementById('ortu-pindah-total');
        var saldoInfo = document.getElementById('ortu-pindah-saldo-info');
        var saldoTersedia = document.getElementById('ortu-pindah-saldo-tersedia');
        var saldoCashless = document.getElementById('ortu-pindah-saldo-cashless');
        var estimasiCashless = document.getElementById('ortu-pindah-estimasi-cashless');
        var biayaAdmin = parseInt(form.dataset.biayaAdmin || '0', 10) || 0;
        var saldoUrl = form.dataset.saldoUrl || '';
        var currentCashless = 0;

        var infaqEnabled = form.dataset.infaqMode !== 'off' && form.dataset.infaqMode !== '';
        var infaqMode = form.dataset.infaqMode || 'off';
        var infaqMax = parseInt(form.dataset.infaqMax || '10000', 10) || 10000;
        var infaqTiers = [];
        try { infaqTiers = JSON.parse(form.dataset.infaqTiers || '[]'); } catch (e) { infaqTiers = []; }

        var infaqCheck = document.getElementById('ortu-infaq-check');
        var infaqFields = document.getElementById('ortu-infaq-fields');
        var infaqAmountInput = document.getElementById('ortu-pindah-infaq-amount');
        var infaqSummaryValue = document.getElementById('ortu-infaq-summary-value');

        function getInfaqAmount() {
            if (!infaqEnabled) {
                return 0;
            }
            if (infaqMode === 'optional' && infaqCheck && !infaqCheck.checked) {
                return 0;
            }
            if (infaqMode === 'optional' && infaqAmountInput) {
                var val = parseAmount(infaqAmountInput);
                return Math.min(val, infaqMax);
            }
            var val = parseAmount(amountInput);
            return calculateInfaq(val, infaqTiers, infaqMax);
        }

        var summarySection = document.getElementById('ortu-pindah-summary-section');

        function updateTotal() {
            var amount = parseAmount(amountInput);
            var infaq = getInfaqAmount();
            if (totalSpan) {
                totalSpan.textContent = formatRp(amount);
            }
            if (infaqSummaryValue) {
                infaqSummaryValue.textContent = 'Rp ' + formatRp(infaq);
            }
            if (infaqAmountInput && infaqMode === 'optional' && (!infaqAmountInput.value || infaqAmountInput.value === '0')) {
                infaqAmountInput.value = calculateInfaq(amount, infaqTiers, infaqMax);
            }
            // Show summary only when there's an amount and a student is selected
            var siswaId = siswaSelect?.value;
            var hasStudent = !!siswaId;
            if (summarySection) {
                summarySection.classList.toggle('hidden', !(amount > 0 && hasStudent));
            }
        }

        function updateEstimatedCashless() {
            if (!estimasiCashless) {
                return;
            }

            var infaq = getInfaqAmount();

            var amount = parseAmount(amountInput);
            estimasiCashless.textContent = formatRp(currentCashless + amount  - (infaq + biayaAdmin));
        }

        function setBalances(balanceKeuangan, balanceCashlessValue) {
            if (saldoTersedia) {
                saldoTersedia.textContent = 'Rp ' + formatRp(balanceKeuangan);
            }
            if (saldoCashless) {
                saldoCashless.textContent = 'Rp ' + formatRp(balanceCashlessValue);
            }
            if (saldoInfo) {
                saldoInfo.classList.remove('hidden');
            }
            currentCashless = Number(balanceCashlessValue || 0);
            updateEstimatedCashless();
            updateTotal();
        }

        function loadBalance(siswaId) {
            if (!siswaId || !saldoUrl) {
                return;
            }

            var option = siswaSelect?.querySelector('option[value="' + siswaId + '"]');
            if (option && option.dataset.balanceKeuangan !== undefined) {
                setBalances(option.dataset.balanceKeuangan, option.dataset.balanceCashless || 0);
            }

            fetch(saldoUrl + '?siswa_id=' + encodeURIComponent(siswaId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (payload?.success && payload.data?.balance !== undefined) {
                        setBalances(payload.data.balance, payload.data.cashless_balance || 0);
                        if (option) {
                            option.dataset.balanceKeuangan = String(payload.data.balance);
                            option.dataset.balanceCashless = String(payload.data.cashless_balance || 0);
                        }
                    }
                })
                .catch(function () {});
        }

        amountInput?.addEventListener('input', function () {
            var amount = parseAmount(amountInput);
            if (infaqAmountInput && infaqMode === 'optional') {
                infaqAmountInput.value = calculateInfaq(amount, infaqTiers, infaqMax);
            }
            updateTotal();
            updateEstimatedCashless();
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
        updateEstimatedCashless();

        siswaSelect?.addEventListener('change', function () {
            var siswaId = siswaSelect.value;
            if (!siswaId) {
                saldoInfo?.classList.add('hidden');
                currentCashless = 0;
                updateEstimatedCashless();
                updateTotal();
                return;
            }
            loadBalance(siswaId);
        });

        if (siswaSelect?.value) {
            loadBalance(siswaSelect.value);
        }

        form.addEventListener('fetch-success', function (event) {
            var data = event.detail?.data || {};
            if (data.saldo_keuangan === undefined || data.saldo_keuangan === null) {
                return;
            }

            setBalances(data.saldo_keuangan, data.saldo_cashless || currentCashless);
            var option = siswaSelect?.querySelector('option[value="' + (siswaSelect.value || '') + '"]');
            if (option) {
                option.dataset.balanceKeuangan = String(data.saldo_keuangan);
                option.dataset.balanceCashless = String(data.saldo_cashless || currentCashless);
            }
            updateTotal();
        });
    });
})();
