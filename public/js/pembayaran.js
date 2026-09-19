(function () {
    var MIN_CICILAN_AMOUNT = 1000;

    function formatRp(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function isInstallmentBill(item) {
        return Number(item.is_cicilan) === 1
            || item.is_cicilan === true
            || item.is_cicilan === '1'
            || Number(item.status) === 2;
    }

    function statusBadge(status, label) {
        var badgeClass = Number(status) === 1
            ? 'badge-success'
            : (Number(status) === 2 ? 'badge-info' : 'badge-warning');
        var text = label || (Number(status) === 1 ? 'Lunas' : (Number(status) === 2 ? 'Cicilan' : 'Belum Lunas'));

        return '<span class="badge ' + badgeClass + '">' + text + '</span>';
    }

    function parseAmount(value) {
        return window.parseFormattedNumber ? window.parseFormattedNumber(value) : Number(String(value || '').replace(/\./g, '') || 0);
    }

    function initPembayaranPage(config) {
        var searchContainer = document.getElementById(config.searchContainerId);
        var tagihanSection = document.getElementById('tagihan-section');
        var tagihanTableBody = document.getElementById('tagihan-table-body');
        var tagihanEmpty = document.getElementById('tagihan-empty');
        var tagihanStudentLabel = document.getElementById('tagihan-student-label');
        var selectionBar = document.getElementById('tagihan-selection-bar');
        var selectionSummary = document.getElementById('tagihan-selection-summary');
        var paymentFormCard = document.getElementById('payment-form-card');
        var paymentForm = document.getElementById('payment-form');
        var paymentItemsContainer = document.getElementById('payment-items-container');
        var paymentSummaryList = document.getElementById('payment-summary-list');
        var paymentTotalLabel = document.getElementById('payment-total-label');
        var selectAllCheckbox = document.getElementById('select-all-tagihan');
        var openPaymentBtn = document.getElementById('open-payment-btn');
        var fidbankSelect = document.getElementById('payment-fidbank');
        var referenceInput = document.getElementById('payment-reference');
        var referenceHint = document.getElementById('payment-reference-hint');
        var autoReferencePrefixes = {};

        if (config.fidbankTunai) {
            autoReferencePrefixes[config.fidbankTunai] = 'KW';
        }
        if (config.fidbankSaldo) {
            autoReferencePrefixes[config.fidbankSaldo] = 'SK';
        }

        function generatePaymentReference(prefix) {
            var now = new Date();
            var datePart = now.getFullYear()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var random = '';
            var index = 0;

            for (index = 0; index < 6; index += 1) {
                random += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            return prefix + '-' + datePart + '-' + random;
        }

        function syncReferenceField() {
            if (!fidbankSelect || !referenceInput) {
                return;
            }

            var prefix = autoReferencePrefixes[fidbankSelect.value];
            if (prefix) {
                referenceInput.value = generatePaymentReference(prefix);
                referenceInput.readOnly = true;
                referenceInput.dataset.autoFilled = '1';
                referenceInput.classList.add('pembayaran-reference--auto');
                if (referenceHint) {
                    referenceHint.textContent = 'Referensi dibuat otomatis untuk metode ini.';
                }
                return;
            }

            if (referenceInput.dataset.autoFilled === '1') {
                referenceInput.value = '';
            }

            referenceInput.readOnly = false;
            referenceInput.removeAttribute('readonly');
            delete referenceInput.dataset.autoFilled;
            referenceInput.classList.remove('pembayaran-reference--auto');
            if (referenceHint) {
                referenceHint.textContent = 'Isi no. bukti transfer atau kosongkan untuk dibuat otomatis.';
            }
        }

        function resetReferenceField() {
            if (!referenceInput) {
                return;
            }

            referenceInput.value = '';
            referenceInput.readOnly = false;
            referenceInput.removeAttribute('readonly');
            delete referenceInput.dataset.autoFilled;
            referenceInput.classList.remove('pembayaran-reference--auto');
        }

        var selectedStudent = null;
        var tagihanItems = [];
        var selectedIds = new Set();

        function getSelectedItems() {
            return tagihanItems.filter(function (item) {
                return selectedIds.has(String(item.id));
            });
        }

        function getItemRemaining(item) {
            return Number(item.remaining ?? item.payable_amount ?? 0);
        }

        function getDefaultAmount(item) {
            return getItemRemaining(item);
        }

        function getSelectedTotal() {
            if (paymentFormCard && !paymentFormCard.classList.contains('hidden')) {
                return getSubmittedAmountInputs().reduce(function (sum, input) {
                    return sum + readInputAmount(input);
                }, 0);
            }

            return getSelectedItems().reduce(function (sum, item) {
                return sum + getDefaultAmount(item);
            }, 0);
        }

        function hidePaymentForm() {
            paymentFormCard?.classList.add('hidden');
            paymentItemsContainer.innerHTML = '';
            paymentSummaryList.innerHTML = '';
            paymentTotalLabel.textContent = '-';
            resetReferenceField();
        }

        function getSubmittedAmountInputs() {
            return Array.from(paymentFormCard?.querySelectorAll('input[name$="[amount]"]') || []);
        }

        function isCicilanAmountInput(input) {
            return input.getAttribute('data-is-cicilan') === '1'
                || input.dataset.isCicilan === '1';
        }

        function readInputAmount(input) {
            if (isCicilanAmountInput(input) && window.readBoundedFormattedAmount) {
                return window.readBoundedFormattedAmount(input);
            }

            return parseAmount(input.value);
        }

        function schedulePaymentTotalsUpdate() {
            window.setTimeout(updatePaymentTotals, 0);
        }

        function updatePaymentTotals() {
            var selected = getSelectedItems();
            paymentTotalLabel.textContent = formatRp(getSelectedTotal());

            paymentSummaryList.innerHTML = selected.map(function (item) {
                var input = paymentFormCard?.querySelector('[name$="[amount]"][data-tagihan-id="' + item.id + '"]');
                var amount = input ? readInputAmount(input) : getDefaultAmount(item);
                var cicilanNote = isInstallmentBill(item) && item.cicilan_urutan
                    ? ' · cicilan ke-' + item.cicilan_urutan
                    : '';

                return '<li class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/60">'
                    + '<span>' + item.jenis + ' · ' + (item.periode || '-') + cicilanNote + '</span>'
                    + '<span class="font-medium text-slate-900 dark:text-white">' + formatRp(amount) + '</span>'
                    + '</li>';
            }).join('');
        }

        function bindAmountInputs() {
            paymentFormCard?.querySelectorAll('.pembayaran-item-amount--editable').forEach(function (input) {
                input.addEventListener('input', schedulePaymentTotalsUpdate);
                input.addEventListener('change', updatePaymentTotals);
                input.addEventListener('formatted-amount-updated', updatePaymentTotals);
            });
        }

        function setTagihanSelected(id, selected) {
            var idStr = String(id);
            var checkbox = tagihanTableBody?.querySelector('.tagihan-checkbox[value="' + idStr + '"]');

            if (selected) {
                selectedIds.add(idStr);
            } else {
                selectedIds.delete(idStr);
            }

            if (checkbox) {
                checkbox.checked = selected;
            }

            hidePaymentForm();
            updateSelectionUi();
        }

        function bindTagihanRows() {
            tagihanTableBody?.querySelectorAll('[data-tagihan-row]').forEach(function (row) {
                var checkbox = row.querySelector('.tagihan-checkbox');
                if (!checkbox) {
                    return;
                }

                row.classList.add('pembayaran-tagihan-row--selectable');
                row.setAttribute('role', 'button');
                row.setAttribute('aria-pressed', checkbox.checked ? 'true' : 'false');
                row.tabIndex = 0;

                checkbox.addEventListener('click', function (e) {
                    e.stopPropagation();
                });

                checkbox.addEventListener('change', function () {
                    setTagihanSelected(checkbox.value, checkbox.checked);
                });

                row.addEventListener('click', function () {
                    setTagihanSelected(checkbox.value, !checkbox.checked);
                });

                row.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' && e.key !== ' ') {
                        return;
                    }

                    e.preventDefault();
                    setTagihanSelected(checkbox.value, !checkbox.checked);
                });
            });
        }

        function updateSelectionUi() {
            var selected = getSelectedItems();
            var count = selected.length;

            if (selectionBar) {
                selectionBar.classList.toggle('hidden', count === 0);
            }

            if (selectionSummary) {
                selectionSummary.textContent = count + ' tagihan dipilih · Estimasi ' + formatRp(getSelectedTotal());
            }

            if (selectAllCheckbox) {
                var payable = tagihanItems.filter(function (item) { return item.can_pay; });
                selectAllCheckbox.indeterminate = count > 0 && count < payable.length;
                selectAllCheckbox.checked = payable.length > 0 && count === payable.length;
            }

            tagihanTableBody?.querySelectorAll('[data-tagihan-row]').forEach(function (row) {
                var id = row.dataset.tagihanId;
                var isSelected = selectedIds.has(id);

                row.classList.toggle('bg-primary-50/60', isSelected);
                row.classList.toggle('dark:bg-primary-950/30', isSelected);

                if (row.classList.contains('pembayaran-tagihan-row--selectable')) {
                    row.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                }
            });
        }

        function syncPaymentForm() {
            var selected = getSelectedItems();
            if (!selected.length) {
                hidePaymentForm();
                return;
            }

            paymentItemsContainer.innerHTML = selected.map(function (item, index) {
                var remaining = getItemRemaining(item);
                var defaultAmount = getDefaultAmount(item);
                var isCicilan = isInstallmentBill(item);
                var minAmount = isCicilan ? Math.min(MIN_CICILAN_AMOUNT, remaining) : remaining;
                var formattedDefault = window.formatNumberId
                    ? window.formatNumberId(defaultAmount)
                    : String(defaultAmount);
                var hint = isCicilan
                    ? 'Sisa ' + (item.remaining_label || formatRp(remaining)) + ' · min ' + formatRp(minAmount)
                    : 'Lunas penuh · ' + (item.remaining_label || formatRp(remaining));

                var amountField = isCicilan
                    ? '<input type="text"'
                        + ' id="payment-item-amount-' + item.id + '"'
                        + ' name="items[' + index + '][amount]"'
                        + ' class="form-input formattedNumber pembayaran-item-amount pembayaran-item-amount--editable"'
                        + ' inputmode="numeric"'
                        + ' autocomplete="off"'
                        + ' required'
                        + ' data-format-on="live"'
                        + ' data-min="' + minAmount + '"'
                        + ' data-max="' + remaining + '"'
                        + ' value="' + formattedDefault + '"'
                        + ' data-tagihan-id="' + item.id + '"'
                        + ' data-is-cicilan="1"'
                        + '>'
                    : '<input type="hidden"'
                        + ' name="items[' + index + '][amount]"'
                        + ' value="' + defaultAmount + '"'
                        + ' data-min="' + remaining + '"'
                        + ' data-max="' + remaining + '"'
                        + ' data-tagihan-id="' + item.id + '"'
                        + ' data-is-cicilan="0">'
                        + '<input type="text"'
                        + ' id="payment-item-amount-' + item.id + '"'
                        + ' class="form-input pembayaran-item-amount pembayaran-item-amount--locked"'
                        + ' inputmode="numeric"'
                        + ' readonly'
                        + ' tabindex="-1"'
                        + ' aria-readonly="true"'
                        + ' value="' + formattedDefault + '"'
                        + ' data-tagihan-id="' + item.id + '"'
                        + ' data-is-cicilan="0">';

                return '<div class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/40" data-payment-item data-tagihan-id="' + item.id + '">'
                    + '<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">'
                    + '<div class="min-w-0 flex-1">'
                    + '<p class="font-medium text-slate-900 dark:text-white">' + item.jenis + '</p>'
                    + '<p class="mt-1 text-xs text-slate-500">' + (item.periode || '-') + ' · ' + hint + '</p>'
                    + (isCicilan && item.cicilan_urutan
                        ? '<p class="mt-1 text-xs text-cyan-700 dark:text-cyan-300">Pembayaran cicilan ke-' + item.cicilan_urutan + '</p>'
                        : '')
                    + '</div>'
                    + '<div class="w-full sm:w-52">'
                    + '<label class="form-label" for="payment-item-amount-' + item.id + '">Nominal Bayar</label>'
                    + amountField
                    + '<input type="hidden" name="items[' + index + '][tagihan_id]" value="' + item.id + '">'
                    + '</div>'
                    + '</div>'
                    + '</div>';
            }).join('');

            paymentFormCard?.querySelectorAll('.pembayaran-item-amount--editable').forEach(function (input) {
                input.readOnly = false;
                input.removeAttribute('readonly');
                input.disabled = false;
                input.tabIndex = 0;
            });

            window.initFormattedNumbers?.(paymentFormCard);
            bindAmountInputs();
            updatePaymentTotals();
        }

        function validatePaymentAmounts() {
            var errors = [];

            getSubmittedAmountInputs().forEach(function (input) {
                var amount = readInputAmount(input);
                var min = parseInt(input.getAttribute('data-min') || '0', 10);
                var max = parseInt(input.getAttribute('data-max') || '0', 10);
                var isCicilan = input.getAttribute('data-is-cicilan') === '1'
                    || input.dataset.isCicilan === '1';

                if (!isCicilan) {
                    if (max > 0 && amount !== max) {
                        errors.push('Tagihan non-cicilan harus dibayar lunas (' + formatRp(max) + ').');
                    }
                    return;
                }

                var fieldLabel = input.id
                    ? (document.querySelector('label[for="' + input.id + '"]')?.closest('[data-payment-item]')?.querySelector('.font-medium')?.textContent || 'Nominal bayar')
                    : 'Nominal bayar';

                if (!amount || amount < min) {
                    errors.push(fieldLabel + ': minimal ' + formatRp(min));
                    return;
                }

                if (amount > max) {
                    errors.push(fieldLabel + ': maksimal ' + formatRp(max));
                }
            });

            if (errors.length) {
                window.showAlert?.({
                    title: 'Periksa Nominal Bayar',
                    message: errors.join('\n'),
                    variant: 'warning',
                });
                return false;
            }

            return true;
        }

        function openPaymentForm() {
            if (!getSelectedItems().length) return;
            syncPaymentForm();
            syncReferenceField();
            paymentFormCard?.classList.remove('hidden');
            paymentFormCard?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function renderTagihan(items) {
            tagihanItems = items || [];
            selectedIds.clear();
            hidePaymentForm();
            updateSelectionUi();

            if (!items.length) {
                tagihanTableBody.innerHTML = '';
                tagihanEmpty?.classList.remove('hidden');
                return;
            }

            tagihanEmpty?.classList.add('hidden');
            tagihanTableBody.innerHTML = items.map(function (item) {
                var checkbox = item.can_pay
                    ? '<input type="checkbox" class="form-checkbox tagihan-checkbox" value="' + item.id + '">'
                    : '<span class="text-slate-300 dark:text-slate-600">—</span>';

                var cicilanNote = isInstallmentBill(item)
                    ? '<div class="text-xs text-cyan-700 dark:text-cyan-300">Cicilan' + (item.cicilan_count ? ' · ' + item.cicilan_count + ' pembayaran' : '') + '</div>'
                    : '';

                return '<tr data-tagihan-row data-tagihan-id="' + item.id + '" class="border-t border-slate-100 dark:border-slate-800">'
                    + '<td class="px-4 py-3">' + checkbox + '</td>'
                    + '<td class="px-4 py-3 font-medium text-slate-900 dark:text-white">' + item.jenis + cicilanNote + '</td>'
                    + '<td class="px-4 py-3 text-slate-600 dark:text-slate-300">' + (item.periode || '-') + '</td>'
                    + '<td class="px-4 py-3 text-slate-600 dark:text-slate-300">' + (item.due_date || '-') + '</td>'
                    + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + item.amount_label + '</td>'
                    + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + item.paid_label + '</td>'
                    + '<td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">' + item.remaining_label + '</td>'
                    + '<td class="px-4 py-3">' + statusBadge(item.status, item.status_label) + '</td>'
                    + '</tr>';
            }).join('');

            bindTagihanRows();
        }

        function loadStudentTagihan(student) {
            selectedStudent = student;
            hidePaymentForm();
            tagihanSection?.classList.remove('hidden');
            tagihanStudentLabel.textContent = student.nis + ' — ' + student.name + (student.kelas ? ' · ' + student.kelas : '');
            tagihanTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">Memuat tagihan...</td></tr>';

            fetch(config.tagihanUrlBase + '/' + student.id + '/tagihan', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    if (!payload.success) throw new Error(payload.message || 'Gagal memuat tagihan');
                    var siswa = payload.data.siswa || student;
                    selectedStudent = siswa;
                    var label = siswa.nis + ' — ' + siswa.name + (siswa.kelas ? ' · ' + siswa.kelas : '');
                    if (siswa.virtual_account) {
                        label += ' · No. VA ' + siswa.virtual_account;
                    }
                    tagihanStudentLabel.textContent = label;
                    renderTagihan(payload.data.tagihan || []);
                })
                .catch(function (err) {
                    tagihanTableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-sm text-red-600">' + (err.message || 'Gagal memuat tagihan.') + '</td></tr>';
                });
        }

        selectAllCheckbox?.addEventListener('change', function () {
            var payable = tagihanItems.filter(function (item) { return item.can_pay; });
            selectedIds.clear();
            if (selectAllCheckbox.checked) {
                payable.forEach(function (item) {
                    selectedIds.add(String(item.id));
                });
            }
            tagihanTableBody?.querySelectorAll('.tagihan-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            hidePaymentForm();
            updateSelectionUi();
        });

        openPaymentBtn?.addEventListener('click', openPaymentForm);

        fidbankSelect?.addEventListener('change', syncReferenceField);

        searchContainer?.addEventListener('student-selected', function (e) {
            loadStudentTagihan(e.detail);
        });

        document.getElementById('reset-student-btn')?.addEventListener('click', function () {
            selectedStudent = null;
            tagihanItems = [];
            selectedIds.clear();
            hidePaymentForm();
            tagihanSection?.classList.add('hidden');
            window.clearStudentSearch?.(searchContainer);
        });

        document.getElementById('cancel-payment-btn')?.addEventListener('click', hidePaymentForm);

        paymentForm?.addEventListener('submit', function (e) {
            var selected = getSelectedItems();
            if (!selected.length) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.showAlert?.({
                    title: 'Periksa Data',
                    message: 'Pilih minimal satu tagihan untuk dibayar.',
                    variant: 'warning',
                });
                return;
            }

            paymentFormCard?.querySelectorAll('.pembayaran-item-amount--editable').forEach(function (input) {
                input.dispatchEvent(new Event('blur'));
            });

            if (!validatePaymentAmounts()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
        });

        paymentForm?.addEventListener('fetch-success', async function (event) {
            var receipt = event.detail?.data?.receipt;

            if (selectedStudent) {
                loadStudentTagihan(selectedStudent);
            }
            hidePaymentForm();

            if (!receipt) {
                return;
            }

            var siswa = receipt.siswa || {};
            var confirmed = await window.showConfirm?.({
                title: 'Cetak Kuitansi?',
                message: 'Pembayaran berhasil dicatat. Cetak kuitansi sekarang?',
                detail: [
                    { label: 'Siswa', value: [siswa.name, siswa.nis].filter(Boolean).join(' · ') || '-' },
                    { label: 'Kelas', value: siswa.kelas || '-' },
                    { label: 'No. Kuitansi', value: receipt.reference || '-' },
                    { label: 'Metode', value: receipt.method_label || '-' },
                    { label: 'Total', value: receipt.total_label || formatRp(receipt.total_amount) },
                ],
                confirmText: 'Ya, Cetak',
                tone: 'primary',
                confirmIcon: 'ti-printer',
                headerIcon: 'ti-printer',
                footnote: 'Anda juga dapat mencetak ulang kuitansi dari Riwayat Pembayaran.',
            });

            if (confirmed) {
                window.printPaymentReceipt?.(receipt);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('pembayaran-page');
        if (!root) return;

        initPembayaranPage({
            searchContainerId: 'pembayaran-student-search',
            tagihanUrlBase: root.dataset.tagihanUrl,
            fidbankTunai: root.dataset.fidbankTunai || '1140000',
            fidbankSaldo: root.dataset.fidbankSaldo || '1140002',
        });
    });
})();
