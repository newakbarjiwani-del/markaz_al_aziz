/**
 * Confirm dialog builders + balance preview for cashless top-up / withdraw forms.
 * Supports Cari Siswa and Scan RFID methods.
 */
(function () {
    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    }

    function parseAmount(input) {
        if (!input) return 0;
        if (typeof window.parseFormattedNumber === 'function') {
            return window.parseFormattedNumber(input.value);
        }
        var digits = String(input.value || '').replace(/\./g, '').replace(/[^0-9]/g, '');
        return digits === '' ? 0 : parseInt(digits, 10);
    }

    function sanitizeRfid(value) {
        return String(value || '').replace(/[\x00-\x1F\x7F]/g, '').trim();
    }

    function methodLabel(method) {
        if (method === 'rfid') return 'Scan RFID';
        if (method === 'face') return 'Deteksi Wajah';
        return 'Cari Siswa';
    }

    function fidbankLabel(method) {
        return method === 'rfid' ? 'RFID' : 'CASH';
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

    function getInfaqTiers() {
        var form = document.getElementById('topup-cashless-form');
        if (!form || !form.dataset.infaqTiers) return [];
        try { return JSON.parse(form.dataset.infaqTiers); } catch (e) { return []; }
    }

    function getInfaqMax() {
        var form = document.getElementById('topup-cashless-form');
        if (!form) return 10000;
        return parseInt(form.dataset.infaqMax || '10000', 10) || 10000;
    }

    function getInfaqMode() {
        var form = document.getElementById('topup-cashless-form');
        return form ? (form.dataset.infaqMode || 'off') : 'off';
    }

    function recalcTopupInfaq() {
        var form = document.getElementById('topup-cashless-form');
        if (!form) return;

        var amountInput = form.querySelector('[name="amount"]');
        var amount = parseAmount(amountInput);
        var infaqMode = getInfaqMode();
        var infaqAmount = 0;

        if (infaqMode === 'optional') {
            var check = document.getElementById('topup-infaq-check');
            var infaqAmtInput = document.getElementById('saldo-cashless-topup-infaq-amount');
            if (check && !check.checked) {
                infaqAmount = 0;
            } else if (infaqAmtInput) {
                infaqAmount = parseAmount(infaqAmtInput);
                infaqAmount = Math.min(infaqAmount, getInfaqMax());
            }
        } else if (infaqMode === 'on') {
            infaqAmount = calculateInfaq(amount, getInfaqTiers(), getInfaqMax());
        }

        updateTopupSummary(form, amount, infaqAmount);
    }

    function updateTopupSummary(form, amount, infaqAmount) {
        var summaryValue = document.getElementById('topup-infaq-summary-value');
        var netCredited = document.getElementById('topup-net-credited');
        var netAmount = amount - infaqAmount;

        if (summaryValue) {
            summaryValue.textContent = formatRupiah(infaqAmount);
        }
        if (netCredited) {
            netCredited.textContent = formatRupiah(netAmount);
        }

        // Set data attributes for confirm builder
        form.dataset.infaqAmount = String(infaqAmount);
        form.dataset.netAmount = String(netAmount);

        // Show the summary section when there's an amount
        var topupSummarySection = document.getElementById('topup-summary-section');
        if (topupSummarySection) {
            topupSummarySection.classList.toggle('hidden', !(amount > 0));
        }

        // Update Saldo Akhir
        var balance = currentBalanceFromForm(form);
        var saldoAkhirEl = document.getElementById('topup-saldo-akhir-value');
        if (saldoAkhirEl) {
            if (balance !== null && amount > 0) {
                saldoAkhirEl.textContent = formatRupiah(balance + netAmount);
            } else {
                saldoAkhirEl.textContent = formatRupiah(0);
            }
        }
    }

    function updateWithdrawSummary(form) {
        var balance = currentBalanceFromForm(form);
        var amountInput = form.querySelector('[name="amount"]');
        var amount = parseAmount(amountInput);

        var summarySection = document.getElementById('withdraw-summary-section');
        var balanceSummaryEl = document.getElementById('withdraw-saldo-tersedia-summary');
        var amountSummaryEl = document.getElementById('withdraw-amount-summary');
        var saldoAkhirEl = document.getElementById('withdraw-saldo-akhir-value');

        if (!balance || balance <= 0 || amount <= 0) {
            summarySection?.classList.add('hidden');
            return;
        }

        if (balanceSummaryEl) balanceSummaryEl.textContent = formatRupiah(balance);
        if (amountSummaryEl) amountSummaryEl.textContent = '- ' + formatRupiah(amount);
        if (saldoAkhirEl) saldoAkhirEl.textContent = formatRupiah(Math.max(0, balance - amount));
        summarySection?.classList.remove('hidden');
    }

    function pageEl() {
        return document.getElementById('saldo-cashless-page');
    }

    function showUrlBase() {
        var page = pageEl();
        return page ? (page.getAttribute('data-show-url') || '') : '';
    }

    function withdrawPreviewUrl() {
        var page = pageEl();
        return page ? (page.getAttribute('data-withdraw-preview-url') || '') : '';
    }

    function lookupRfidUrl() {
        var page = pageEl();
        return page ? (page.getAttribute('data-lookup-rfid-url') || '') : '';
    }

    function currentMethod(form) {
        var method = form?.dataset?.cashlessMethod;
        return (method === 'rfid' || method === 'face') ? method : 'search';
    }

    function selectedSiswaLabel(form) {
        var method = currentMethod(form);
        if (method === 'rfid') {
            return form.dataset.rfidLabel || form.querySelector('[name="rfid_uid"]')?.value || '-';
        }
        if (method === 'face') {
            return form.dataset.faceLabel || '-';
        }

        var select = form.querySelector('[name="siswa_id"]');
        if (!select) return '-';

        if (window.jQuery && select.classList.contains('select2-hidden-accessible')) {
            var data = window.jQuery(select).select2('data');
            if (data && data[0] && data[0].text) {
                return String(data[0].text).trim();
            }
        }

        var option = select.options?.[select.selectedIndex];
        return option ? String(option.text || '').trim() : '-';
    }

    function hasResolvedStudent(form) {
        var method = currentMethod(form);
        if (method === 'rfid') {
            return !!(form.dataset.rfidResolved === '1' && sanitizeRfid(form.querySelector('[name="rfid_uid"]')?.value));
        }
        if (method === 'face') {
            return !!(form.dataset.faceResolved === '1' && form.dataset.faceSiswaId);
        }

        var searchSelect = form.querySelector('[data-method-panel="search"][data-form="' + form.id + '"] [name="siswa_id"]')
            || form.querySelector('[name="siswa_id"]');
        return !!(searchSelect && searchSelect.value);
    }

    function currentBalanceFromForm(form) {
        if (!form || form.dataset.cashlessBalance === '' || form.dataset.cashlessBalance == null) {
            return null;
        }

        var balance = Number(form.dataset.cashlessBalance || 0);
        return Number.isNaN(balance) ? null : balance;
    }

    function setWithdrawPinVisibility(form, preview) {
        var pinWrap = document.getElementById('withdraw-cashless-pin-wrap');
        var limitInfo = document.getElementById('withdraw-cashless-limit-info');
        var limitText = document.getElementById('withdraw-cashless-limit-text');
        var pinHint = document.getElementById('withdraw-cashless-pin-hint');
        var pinInput = document.getElementById('saldo-cashless-withdraw-pin');
        var required = !!(preview && preview.pin_required);

        form.dataset.pinRequired = required ? '1' : '0';
        form.dataset.pinSet = preview && preview.pin_set ? '1' : '0';

        if (pinWrap) {
            pinWrap.classList.toggle('hidden', !required);
        }
        if (limitInfo) {
            limitInfo.classList.toggle('hidden', !required);
        }
        if (required && limitText) {
            limitText.textContent = 'Nominal melebihi limit harian cashless.';
        }
        if (required && pinHint) {
            pinHint.textContent = preview.pin_set
                ? 'Masukkan PIN cashless 4 digit siswa untuk melanjutkan.'
                : 'PIN belum diset. Set PIN dulu (ortu/admin/cashless) sebelum tarik over-limit.';
        }
        if (!required && pinInput) {
            pinInput.value = '';
        }
        if (pinInput) {
            pinInput.required = required && !!preview.pin_set;
        }
    }

    function setRfidSummary(form, siswa) {
        var prefix = form.id === 'withdraw-cashless-form' ? 'withdraw' : 'topup';
        var summary = document.getElementById(prefix + '-cashless-rfid-summary');
        var nameEl = document.getElementById(prefix + '-cashless-rfid-name');
        var metaEl = document.getElementById(prefix + '-cashless-rfid-meta');

        if (!siswa) {
            form.dataset.rfidResolved = '0';
            form.dataset.rfidLabel = '';
            summary?.classList.add('hidden');
            if (nameEl) nameEl.textContent = '-';
            if (metaEl) metaEl.textContent = '-';
            return;
        }

        form.dataset.rfidResolved = '1';
        form.dataset.rfidLabel = [siswa.nis, siswa.name].filter(Boolean).join(' — ');
        if (nameEl) nameEl.textContent = siswa.name || '-';
        if (metaEl) {
            metaEl.textContent = [
                siswa.nis ? ('NIS ' + siswa.nis) : null,
                siswa.kelas || null,
                siswa.rfid_uid ? ('RFID ' + siswa.rfid_uid) : null,
            ].filter(Boolean).join(' · ');
        }
        summary?.classList.remove('hidden');
    }

    function setFaceStudent(form, siswa) {
        if (!form || form.id !== 'withdraw-cashless-form') return;

        var summary = document.getElementById('withdraw-cashless-face-summary');
        var nameEl = document.getElementById('withdraw-cashless-face-name');
        var metaEl = document.getElementById('withdraw-cashless-face-meta');
        var hidden = document.getElementById('saldo-cashless-withdraw-face-siswa');
        var info = document.getElementById('withdraw-cashless-saldo-info');
        var balanceEl = document.getElementById('withdraw-cashless-saldo-tersedia');

        if (!siswa) {
            form.dataset.faceResolved = '0';
            form.dataset.faceSiswaId = '';
            form.dataset.faceLabel = '';
            if (hidden) {
                hidden.value = '';
                hidden.disabled = true;
            }
            summary?.classList.add('hidden');
            if (nameEl) nameEl.textContent = '-';
            if (metaEl) metaEl.textContent = '-';
            hideBalance(form, balanceEl, info, true);
            return;
        }

        form.dataset.faceResolved = '1';
        form.dataset.faceSiswaId = String(siswa.id);
        form.dataset.faceLabel = [siswa.nis, siswa.name].filter(Boolean).join(' — ');
        if (hidden) {
            hidden.disabled = false;
            hidden.value = String(siswa.id);
        }
        if (nameEl) nameEl.textContent = siswa.name || '-';
        if (metaEl) {
            metaEl.textContent = [
                siswa.nis ? ('NIS ' + siswa.nis) : null,
                siswa.kelas || null,
            ].filter(Boolean).join(' · ');
        }
        summary?.classList.remove('hidden');
        refreshWithdrawPreview(form);
    }

    window.setCashlessWithdrawFaceStudent = function (siswa) {
        var form = document.getElementById('withdraw-cashless-form');
        if (!form) return;
        if (currentMethod(form) !== 'face') {
            setMethod(form, 'face');
        }
        setFaceStudent(form, siswa);
    };

    window.clearCashlessWithdrawFaceStudent = function () {
        var form = document.getElementById('withdraw-cashless-form');
        if (form) setFaceStudent(form, null);
    };

    function setBalance(form, balance, balanceEl, info) {
        var amount = Number(balance || 0);
        form.dataset.cashlessBalance = String(amount);
        if (balanceEl) balanceEl.textContent = formatRupiah(amount);
        info?.classList.remove('hidden');
    }

    function hideBalance(form, balanceEl, info, isWithdraw) {
        form.dataset.cashlessBalance = '';
        if (balanceEl) balanceEl.textContent = 'Rp 0';
        info?.classList.add('hidden');
        if (isWithdraw) {
            setWithdrawPinVisibility(form, null);
        }
    }

    function refreshWithdrawPreview(form) {
        var previewUrl = withdrawPreviewUrl();
        var amount = parseAmount(form.querySelector('[name="amount"]'));
        var method = currentMethod(form);

        if (!previewUrl) {
            setWithdrawPinVisibility(form, null);
            return;
        }

        var url = new URL(previewUrl, window.location.origin);
        if (method === 'rfid') {
            var rfid = sanitizeRfid(form.querySelector('[name="rfid_uid"]')?.value);
            if (!rfid || form.dataset.rfidResolved !== '1') {
                setWithdrawPinVisibility(form, null);
                return;
            }
            url.searchParams.set('rfid_uid', rfid);
        } else if (method === 'face') {
            var faceSiswaId = form.dataset.faceSiswaId;
            if (!faceSiswaId || form.dataset.faceResolved !== '1') {
                setWithdrawPinVisibility(form, null);
                return;
            }
            url.searchParams.set('siswa_id', faceSiswaId);
        } else {
            var searchSelect = form.querySelector('[data-method-panel="search"][data-form="' + form.id + '"] [name="siswa_id"]')
                || form.querySelector('[name="siswa_id"]');
            var siswaId = searchSelect?.value;
            if (!siswaId) {
                setWithdrawPinVisibility(form, null);
                return;
            }
            url.searchParams.set('siswa_id', siswaId);
        }

        if (amount > 0) {
            url.searchParams.set('amount', String(amount));
        }

        fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            })
            .then(function (result) {
                if (result.ok && result.payload?.success && result.payload.data) {
                    if (result.payload.data.balance !== undefined) {
                        form.dataset.cashlessBalance = String(result.payload.data.balance);
                        var balanceEl = document.getElementById('withdraw-cashless-saldo-tersedia');
                        if (balanceEl) balanceEl.textContent = formatRupiah(result.payload.data.balance);
                        document.getElementById('withdraw-cashless-saldo-info')?.classList.remove('hidden');
                    }
                    setWithdrawPinVisibility(form, result.payload.data);
                    updateWithdrawSummary(form);
                    return;
                }
                setWithdrawPinVisibility(form, null);
                updateWithdrawSummary(form);
            })
            .catch(function () {
                setWithdrawPinVisibility(form, null);
                updateWithdrawSummary(form);
            });
    }

    function lookupRfid(form, config) {
        var input = form.querySelector('[name="rfid_uid"]');
        var uid = sanitizeRfid(input?.value);
        var url = lookupRfidUrl();
        var info = document.getElementById(config.infoId);
        var balanceEl = document.getElementById(config.balanceId);

        if (!uid || !url) {
            setRfidSummary(form, null);
            hideBalance(form, balanceEl, info, config.isWithdraw);
            return;
        }

        if (input) {
            input.value = uid;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ rfid_uid: uid }),
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.payload?.success || !result.payload.data?.siswa) {
                    setRfidSummary(form, null);
                    hideBalance(form, balanceEl, info, config.isWithdraw);
                    window.showToast?.(result.payload?.message || 'Kartu RFID tidak dikenali.', 'error');
                    return;
                }

                setRfidSummary(form, result.payload.data.siswa);
                setBalance(form, result.payload.data.balance, balanceEl, info);
                if (config.isWithdraw) {
                    refreshWithdrawPreview(form);
                    updateWithdrawSummary(form);
                }
            })
            .catch(function () {
                setRfidSummary(form, null);
                hideBalance(form, balanceEl, info, config.isWithdraw);
                window.showToast?.('Gagal memuat data RFID.', 'error');
            });
    }

    function setMethod(form, method) {
        var valid = { search: 1, rfid: 1, face: 1 };
        var next = valid[method] ? method : 'search';
        form.dataset.cashlessMethod = next;

        form.querySelectorAll('[data-cashless-method][data-target-form="' + form.id + '"]').forEach(function (btn) {
            var active = btn.getAttribute('data-cashless-method') === next;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        form.querySelectorAll('[data-method-panel][data-form="' + form.id + '"]').forEach(function (panel) {
            var match = panel.getAttribute('data-method-panel') === next;
            panel.classList.toggle('hidden', !match);
        });

        var select = form.querySelector('[data-method-panel="search"][data-form="' + form.id + '"] [name="siswa_id"]')
            || form.querySelector('[name="siswa_id"]');
        var rfidInput = form.querySelector('[name="rfid_uid"]');
        var infoId = form.id === 'withdraw-cashless-form' ? 'withdraw-cashless-saldo-info' : 'topup-cashless-saldo-info';
        var balanceId = form.id === 'withdraw-cashless-form' ? 'withdraw-cashless-saldo-tersedia' : 'topup-cashless-saldo-tersedia';
        var info = document.getElementById(infoId);
        var balanceEl = document.getElementById(balanceId);
        var isWithdraw = form.id === 'withdraw-cashless-form';

        // Reset every secondary state when switching method.
        setRfidSummary(form, null);
        if (isWithdraw) {
            setFaceStudent(form, null);
        }
        hideBalance(form, balanceEl, info, isWithdraw);

        // Notify face detector so it can stop/reset the camera when leaving face mode.
        if (window.cashlessWithdrawFace && typeof window.cashlessWithdrawFace.onMethodChange === 'function') {
            window.cashlessWithdrawFace.onMethodChange(next);
        }

        if (next === 'rfid') {
            if (select) {
                select.required = false;
                window.setAjaxSelectValue?.(select, null);
                select.disabled = true;
            }
            if (rfidInput) {
                rfidInput.disabled = false;
                rfidInput.required = true;
                rfidInput.value = '';
                window.setTimeout(function () {
                    rfidInput.focus();
                }, 50);
            }
            return;
        }

        if (next === 'face') {
            if (select) {
                select.required = false;
                window.setAjaxSelectValue?.(select, null);
                select.disabled = true;
            }
            if (rfidInput) {
                rfidInput.disabled = true;
                rfidInput.required = false;
                rfidInput.value = '';
            }
            return;
        }

        if (rfidInput) {
            rfidInput.disabled = true;
            rfidInput.required = false;
            rfidInput.value = '';
        }
        if (select) {
            select.disabled = false;
            select.required = true;
        }
    }

    function bindCashlessBalancePreview(config) {
        var form = document.getElementById(config.formId);
        if (!form || form.dataset.balanceBound === '1') return;

        form.dataset.balanceBound = '1';
        form.dataset.cashlessMethod = form.dataset.cashlessMethod || 'search';

        var select = form.querySelector('[name="siswa_id"]');
        var rfidInput = form.querySelector('[name="rfid_uid"]');
        var info = document.getElementById(config.infoId);
        var balanceEl = document.getElementById(config.balanceId);
        var requestToken = 0;

        function loadBalance(siswaId) {
            var base = showUrlBase();
            if (!siswaId || !base) {
                hideBalance(form, balanceEl, info, config.isWithdraw);
                return;
            }

            if (config.isWithdraw) {
                refreshWithdrawPreview(form);
                return;
            }

            var token = ++requestToken;
            fetch(base.replace(/\/$/, '') + '/' + encodeURIComponent(siswaId), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return { ok: response.ok, payload: payload };
                    });
                })
                .then(function (result) {
                    if (token !== requestToken) return;
                    if (result.ok && result.payload?.success && result.payload.data?.balance !== undefined) {
                        setBalance(form, result.payload.data.balance, balanceEl, info);
                        if (config.isWithdraw) {
                            updateWithdrawSummary(form);
                        }
                        return;
                    }
                    hideBalance(form, balanceEl, info, config.isWithdraw);
                })
                .catch(function () {
                    if (token !== requestToken) return;
                    hideBalance(form, balanceEl, info, config.isWithdraw);
                });
        }

        function onSiswaChange() {
            if (currentMethod(form) !== 'search') return;
            loadBalance(select?.value || '');
        }

        if (window.jQuery && select) {
            window.jQuery(select).on('change.select2 change', onSiswaChange);
        } else {
            select?.addEventListener('change', onSiswaChange);
        }

        var rfidDebounce = null;
        rfidInput?.addEventListener('input', function () {
            if (currentMethod(form) !== 'rfid') return;
            clearTimeout(rfidDebounce);
            form.dataset.rfidResolved = '0';
            rfidDebounce = setTimeout(function () {
                if (sanitizeRfid(rfidInput.value).length >= 4) {
                    lookupRfid(form, config);
                }
            }, 250);
        });
        rfidInput?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            if (currentMethod(form) !== 'rfid') return;
            clearTimeout(rfidDebounce);
            lookupRfid(form, config);
        });

        if (config.isWithdraw) {
            var amountInput = form.querySelector('[name="amount"]');
            var debounce = null;
            amountInput?.addEventListener('input', function () {
                clearTimeout(debounce);
                debounce = setTimeout(function () {
                    refreshWithdrawPreview(form);
                    updateWithdrawSummary(form);
                }, 250);
            });
            amountInput?.addEventListener('change', function () {
                refreshWithdrawPreview(form);
                updateWithdrawSummary(form);
            });
        } else {
            // Top-up form: recalc infaq on amount change
            var topupAmountInput = form.querySelector('[name="amount"]');
            var infaqDebounce = null;
            topupAmountInput?.addEventListener('input', function () {
                clearTimeout(infaqDebounce);
                infaqDebounce = setTimeout(recalcTopupInfaq, 250);
            });
            topupAmountInput?.addEventListener('change', recalcTopupInfaq);

            // Infaq optional: checkbox toggle and amount change
            var infaqCheck = document.getElementById('topup-infaq-check');
            var infaqAmountInput = document.getElementById('saldo-cashless-topup-infaq-amount');
            infaqCheck?.addEventListener('change', recalcTopupInfaq);
            infaqAmountInput?.addEventListener('input', function () {
                clearTimeout(infaqDebounce);
                infaqDebounce = setTimeout(recalcTopupInfaq, 250);
            });
            infaqAmountInput?.addEventListener('change', recalcTopupInfaq);
        }

        form.addEventListener('reset', function () {
            setMethod(form, 'search');
            hideBalance(form, balanceEl, info, config.isWithdraw);
        });

        // Handle modalReset event (dispatched by app.js when modal opens via [data-open-modal])
        form.addEventListener('modalReset', function () {
            setMethod(form, 'search');
            hideBalance(form, balanceEl, info, config.isWithdraw);

            // Hide summary sections
            var topupSummary = document.getElementById('topup-summary-section');
            if (topupSummary) topupSummary.classList.add('hidden');
            var withdrawSummary = document.getElementById('withdraw-summary-section');
            if (withdrawSummary) withdrawSummary.classList.add('hidden');

            // Reset infaq state for topup
            if (!config.isWithdraw) {
                var infaqCheck = document.getElementById('topup-infaq-check');
                if (infaqCheck) infaqCheck.checked = true;
                var infaqAmountInput = document.getElementById('saldo-cashless-topup-infaq-amount');
                if (infaqAmountInput) {
                    infaqAmountInput.value = '';
                    if (typeof window.clearFormattedNumber === 'function') {
                        window.clearFormattedNumber(infaqAmountInput);
                    }
                }
                recalcTopupInfaq();
            }
        });

        document.addEventListener('click', function (event) {
            var opener = event.target.closest('[data-open-modal="' + config.modalId + '"]');
            if (!opener) return;
            window.setTimeout(function () {
                setMethod(form, 'search');
                hideBalance(form, balanceEl, info, config.isWithdraw);
                if (!config.isWithdraw) {
                    recalcTopupInfaq();
                }
            }, 0);
        });
    }

    function bindMethodToggles() {
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-cashless-method][data-target-form]');
            if (!btn) return;

            var form = document.getElementById(btn.getAttribute('data-target-form'));
            if (!form) return;

            setMethod(form, btn.getAttribute('data-cashless-method'));
        });
    }

    function bindCashlessBalancePreviews() {
        bindMethodToggles();
        bindCashlessBalancePreview({
            formId: 'topup-cashless-form',
            infoId: 'topup-cashless-saldo-info',
            balanceId: 'topup-cashless-saldo-tersedia',
            modalId: 'topup-cashless-modal',
        });
        bindCashlessBalancePreview({
            formId: 'withdraw-cashless-form',
            infoId: 'withdraw-cashless-saldo-info',
            balanceId: 'withdraw-cashless-saldo-tersedia',
            modalId: 'withdraw-cashless-modal',
            isWithdraw: true,
        });

        ['topup-cashless-form', 'withdraw-cashless-form'].forEach(function (id) {
            var form = document.getElementById(id);
            if (form) {
                setMethod(form, 'search');
            }
        });
    }

    window.buildCashlessTopupConfirm = function (form) {
        var amountInput = form.querySelector('[name="amount"]');
        var descriptionInput = form.querySelector('[name="description"]');
        var amount = parseAmount(amountInput);
        var method = currentMethod(form);

        if (amount <= 0) {
            window.showAlert?.({
                title: 'Nominal Tidak Valid',
                message: 'Nominal top-up harus lebih dari 0.',
                variant: 'warning',
            });
            return false;
        }

        if (!hasResolvedStudent(form)) {
            window.showAlert?.({
                title: method === 'rfid' ? 'RFID Belum Valid' : 'Siswa Belum Dipilih',
                message: method === 'rfid'
                    ? 'Scan kartu RFID siswa yang valid terlebih dahulu.'
                    : 'Pilih siswa terlebih dahulu sebelum top-up.',
                variant: 'warning',
            });
            return false;
        }

        var description = String(descriptionInput?.value || '').trim();
        var currentBalance = currentBalanceFromForm(form);
        var infaqMode = getInfaqMode();
        var infaqAmount = 0;
        var netAmount = amount;

        if (infaqMode !== 'off') {
            infaqAmount = parseInt(form.dataset.infaqAmount || '0', 10) || 0;
            netAmount = parseInt(form.dataset.netAmount || String(amount), 10) || amount;
        }

        var detail = [
            { label: 'Metode', value: methodLabel(method) },
            { label: 'Siswa', value: selectedSiswaLabel(form) },
        ];

        if (currentBalance !== null) {
            detail.push({ label: 'Saldo tersedia', value: formatRupiah(currentBalance) });
        }

        detail.push(
            { label: 'Nominal', value: formatRupiah(amount) },
            { label: 'FIDBANK', value: fidbankLabel(method) }
        );

        if (infaqAmount > 0) {
            detail.push({ label: 'Infaq', value: '-' + formatRupiah(infaqAmount) });
            detail.push({ label: 'Saldo diterima', value: formatRupiah(netAmount) });
        }

        if (description !== '') {
            detail.push({ label: 'Keterangan', value: description });
        }

        return {
            title: 'Konfirmasi Top-up Cashless',
            message: infaqAmount > 0
                ? 'Saldo cashless siswa akan ditambah sebesar ' + formatRupiah(netAmount) + ' (dipotong infaq ' + formatRupiah(infaqAmount) + ').'
                : 'Saldo cashless siswa akan ditambah sebesar ' + formatRupiah(amount) + '.',
            detail: detail,
            confirmText: 'Ya, Top Up',
            tone: 'primary',
            confirmIcon: 'ti-wallet',
            headerIcon: 'ti-wallet',
            confirmHtml: '<i class="ti ti-wallet"></i> Ya, Top Up',
            footnote: infaqAmount > 0
                ? 'Top-up tercatat di ledger cashless. Infaq dipotong dari nominal top-up.'
                : 'Top-up tercatat di ledger cashless (TOP UP).',
        };
    };

    window.buildCashlessWithdrawConfirm = function (form) {
        var amountInput = form.querySelector('[name="amount"]');
        var descriptionInput = form.querySelector('[name="description"]');
        var pinInput = form.querySelector('[name="pin"]');
        var amount = parseAmount(amountInput);
        var method = currentMethod(form);

        if (amount <= 0) {
            window.showAlert?.({
                title: 'Nominal Tidak Valid',
                message: 'Nominal tarik saldo harus lebih dari 0.',
                variant: 'warning',
            });
            return false;
        }

        if (!hasResolvedStudent(form)) {
            var missingTitle = method === 'rfid'
                ? 'RFID Belum Valid'
                : (method === 'face' ? 'Wajah Belum Terdeteksi' : 'Siswa Belum Dipilih');
            var missingMessage = method === 'rfid'
                ? 'Scan kartu RFID siswa yang valid terlebih dahulu.'
                : (method === 'face'
                    ? 'Deteksi wajah siswa terlebih dahulu sebelum tarik saldo.'
                    : 'Pilih siswa terlebih dahulu sebelum tarik saldo.');
            window.showAlert?.({
                title: missingTitle,
                message: missingMessage,
                variant: 'warning',
            });
            return false;
        }

        if (form.dataset.pinRequired === '1') {
            if (form.dataset.pinSet !== '1') {
                window.showAlert?.({
                    title: 'PIN Belum Diset',
                    message: 'Tarik saldo melebihi limit harian. Set PIN cashless siswa terlebih dahulu.',
                    variant: 'warning',
                });
                return false;
            }
            var pin = String(pinInput?.value || '').trim();
            if (!/^\d{4}$/.test(pin)) {
                window.showAlert?.({
                    title: 'PIN Wajib',
                    message: 'Masukkan PIN cashless 4 digit untuk tarik saldo over-limit.',
                    variant: 'warning',
                });
                pinInput?.focus();
                return false;
            }
        }

        var description = String(descriptionInput?.value || '').trim();
        var currentBalance = currentBalanceFromForm(form);
        var detail = [
            { label: 'Metode', value: methodLabel(method) },
            { label: 'Siswa', value: selectedSiswaLabel(form) },
        ];

        if (currentBalance !== null) {
            detail.push({ label: 'Saldo tersedia', value: formatRupiah(currentBalance) });
        }

        detail.push(
            { label: 'Nominal', value: formatRupiah(amount) },
            { label: 'FIDBANK', value: fidbankLabel(method) }
        );

        if (form.dataset.pinRequired === '1') {
            detail.push({ label: 'PIN over-limit', value: 'Ya' });
        }

        if (description !== '') {
            detail.push({ label: 'Keterangan', value: description });
        }

        return {
            title: 'Konfirmasi Tarik Saldo Cashless',
            message: 'Saldo cashless siswa akan dikurangi sebesar ' + formatRupiah(amount) + '.',
            detail: detail,
            confirmText: 'Ya, Tarik Saldo',
            tone: 'warning',
            confirmIcon: 'ti-cash',
            headerIcon: 'ti-cash',
            confirmHtml: '<i class="ti ti-cash"></i> Ya, Tarik Saldo',
            footnote: form.dataset.pinRequired === '1'
                ? 'Penarikan melebihi limit harian; PIN wajib diverifikasi.'
                : 'Penarikan tercatat di ledger cashless (TARIK SALDO).',
        };
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindCashlessBalancePreviews);
    } else {
        bindCashlessBalancePreviews();
    }
})();
