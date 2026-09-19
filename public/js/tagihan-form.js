(function () {
    const SPP_MONTH_MAP = {
        JANUARI: 1,
        FEBRUARI: 2,
        MARET: 3,
        APRIL: 4,
        MEI: 5,
        JUNI: 6,
        JULI: 7,
        AGUSTUS: 8,
        SEPTEMBER: 9,
        OKTOBER: 10,
        NOVEMBER: 11,
        DESEMBER: 12,
    };

    function readJson(id, fallback) {
        const el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || '') ?? fallback;
        } catch (error) {
            return fallback;
        }
    }

    function formatAmount(value) {
        if (!value) return '';
        return window.formatNumberId ? window.formatNumberId(value) : String(value);
    }

    function initCreateForm(config) {
        const form = document.getElementById('tagihan-form');
        if (!form) return;

        const jenisSelect = document.getElementById('tagihan-jenis');
        const tahunSelect = form.querySelector('[name="tahun_akademik_id"]');
        const amountInput = document.getElementById('tagihan-amount');
        const periodeInput = form.querySelector('[name="periode"]');

        function extractSppMonth(option) {
            const label = String(option?.textContent || '').trim().toUpperCase();
            if (!label.startsWith('SPP ')) return null;
            const monthName = label.replace(/^SPP\s+/, '').trim();
            return SPP_MONTH_MAP[monthName] || null;
        }

        function resolvePeriodeFromSppMonth() {
            const selectedJenis = jenisSelect?.selectedOptions?.[0];
            const selectedTahun = tahunSelect?.selectedOptions?.[0];
            const sppMonth = extractSppMonth(selectedJenis);
            if (!sppMonth || !selectedTahun) return null;

            const tahunName = String(selectedTahun.textContent || '');
            const matches = tahunName.match(/(\d{4})\/(\d{4})/);
            if (!matches) return null;

            const startYear = parseInt(matches[1], 10);
            const endYear = parseInt(matches[2], 10);
            const year = sppMonth >= 7 ? startYear : endYear;

            return year + '-' + String(sppMonth).padStart(2, '0');
        }

        function syncPeriodeWithJenis() {
            if (!periodeInput) return;

            const autoPeriode = resolvePeriodeFromSppMonth();
            if (autoPeriode) {
                periodeInput.value = autoPeriode;
                periodeInput.readOnly = true;
                periodeInput.classList.add('bg-slate-100', 'dark:bg-slate-800');
                return;
            }

            const wasReadOnly = periodeInput.readOnly;
            periodeInput.readOnly = false;
            periodeInput.classList.remove('bg-slate-100', 'dark:bg-slate-800');
            if (wasReadOnly) {
                periodeInput.value = config.defaultPeriode || '';
            }
        }

        function applyDefaultAmountFromJenis() {
            if (!jenisSelect || !amountInput) return;
            const option = jenisSelect.selectedOptions[0];
            const defaultAmount = parseInt(option?.dataset.defaultAmount || '0', 10);
            if (defaultAmount > 0) {
                amountInput.value = formatAmount(defaultAmount);
            }
        }

        jenisSelect?.addEventListener('change', function () {
            applyDefaultAmountFromJenis();
            syncPeriodeWithJenis();
        });
        tahunSelect?.addEventListener('change', syncPeriodeWithJenis);

        document.querySelector('[data-open-modal="tagihan-modal"]')?.addEventListener('click', function () {
            setTimeout(function () {
                applyDefaultAmountFromJenis();
                if (amountInput && (!amountInput.value || amountInput.value === '0')) {
                    amountInput.value = formatAmount(config.defaultSpp);
                }
                const dueDateInput = form.querySelector('[name="due_date"]');
                if (periodeInput) periodeInput.value = config.defaultPeriode;
                if (dueDateInput) dueDateInput.value = config.defaultDueDate;
                syncPeriodeWithJenis();
            }, 0);
        });
    }

    function initGenerateForm(config) {
        const form = document.getElementById('generate-spp-form');
        if (!form) return;

        const previewBox = document.getElementById('generate-spp-preview');
        const submitButton = form.querySelector('button[type="submit"]');
        const tahunSelect = form.querySelector('[name="tahun_akademik_id"]');
        const jenisSelect = form.querySelector('[name="jenis_tagihan_id"]');
        const kelasSelect = form.querySelector('[name="kelas_id"]');
        const periodeInput = form.querySelector('[name="periode"]');
        const amountInput = form.querySelector('[name="amount"]');

        function updatePeriodeRange() {
            if (!periodeInput || !tahunSelect) return;

            const selected = config.tahunAkademikList.find(function (item) {
                return String(item.id) === String(tahunSelect.value);
            });
            if (!selected || !selected.start_year || !selected.end_year) {
                periodeInput.removeAttribute('min');
                periodeInput.removeAttribute('max');
                return;
            }

            periodeInput.setAttribute('min', selected.start_year + '-07');
            periodeInput.setAttribute('max', selected.end_year + '-06');
        }

        function applyDefaultAmountFromJenis() {
            if (!amountInput || !jenisSelect) return;
            const selected = jenisSelect.selectedOptions[0];
            const amount = parseInt(selected?.dataset.defaultAmount || '0', 10);
            if (amount > 0) {
                amountInput.value = formatAmount(amount);
            }
        }

        function extractSppMonth(option) {
            const label = String(option?.textContent || '').trim().toUpperCase();
            if (!label.startsWith('SPP ')) return null;
            const monthName = label.replace(/^SPP\s+/, '').trim();
            return SPP_MONTH_MAP[monthName] || null;
        }

        function resolvePeriodeFromJenisAndTahun() {
            if (!jenisSelect || !tahunSelect) return null;
            const sppMonth = extractSppMonth(jenisSelect.selectedOptions?.[0]);
            if (!sppMonth) return null;

            const selected = config.tahunAkademikList.find(function (item) {
                return String(item.id) === String(tahunSelect.value);
            });
            if (!selected || !selected.start_year || !selected.end_year) return null;

            const year = sppMonth >= 7 ? selected.start_year : selected.end_year;
            return year + '-' + String(sppMonth).padStart(2, '0');
        }

        function syncPeriodeInputForJenis() {
            if (!periodeInput) return;

            const autoPeriode = resolvePeriodeFromJenisAndTahun();
            if (autoPeriode) {
                periodeInput.value = autoPeriode;
                periodeInput.readOnly = true;
                periodeInput.classList.add('bg-slate-100', 'dark:bg-slate-800');
                return;
            }

            const wasReadOnly = periodeInput.readOnly;
            periodeInput.readOnly = false;
            periodeInput.classList.remove('bg-slate-100', 'dark:bg-slate-800');
            if (wasReadOnly) {
                periodeInput.value = config.defaultPeriode || '';
            }
        }

        function isKelasRequired() {
            return kelasSelect && kelasSelect.options.length > 1;
        }

        function renderPreview(summary) {
            if (!previewBox) return;

            if (summary.total === 0) {
                previewBox.className = 'rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200';
                previewBox.textContent = 'Tidak ada siswa aktif pada filter kelas ini.';
                if (submitButton) submitButton.disabled = true;
                return;
            }

            if (summary.all_exist) {
                previewBox.className = 'rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100';
                previewBox.textContent = 'Semua ' + summary.total + ' siswa sudah memiliki tagihan '
                    + (summary.jenis_name || '-') + ' untuk ' + summary.tahun_akademik + ' periode ' + summary.periode_label + '.';
                if (submitButton) submitButton.disabled = true;
                return;
            }

            previewBox.className = 'rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm text-primary-900 dark:border-primary-900/40 dark:bg-primary-950/20 dark:text-primary-100';
            previewBox.textContent = summary.eligible + ' siswa siap digenerate. '
                + summary.skipped + ' siswa dilewati karena tagihan jenis yang sama periode '
                + summary.periode_label + ' pada ' + summary.tahun_akademik + ' sudah ada.'
                + (summary.potongan_will_apply > 0
                    ? ' Potongan otomatis akan diterapkan pada ' + summary.potongan_will_apply + ' siswa.'
                    : (summary.potongan_skipped > 0
                        ? ' Tidak ada potongan aktif untuk ' + summary.potongan_skipped + ' siswa eligible.'
                        : ''));
            if (submitButton) submitButton.disabled = summary.eligible === 0;
        }

        function refreshPreview() {
            if (!previewBox) return;

            if (isKelasRequired() && (!kelasSelect || !kelasSelect.value)) {
                previewBox.className = 'rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400';
                previewBox.textContent = 'Pilih kelas terlebih dahulu untuk melihat pratinjau.';
                if (submitButton) submitButton.disabled = true;
                return;
            }

            previewBox.className = 'rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300';
            previewBox.textContent = 'Memeriksa tagihan yang sudah ada...';

            const formData = new FormData(form);
            window.stripFormattedFields?.(formData, form);
            fetch(config.previewUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: formData,
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload.success) {
                        previewBox.textContent = payload.message || 'Gagal memeriksa data generate tagihan.';
                        if (submitButton) submitButton.disabled = true;
                        return;
                    }
                    renderPreview(payload.data || {});
                })
                .catch(function () {
                    previewBox.textContent = 'Gagal memeriksa data generate tagihan.';
                    if (submitButton) submitButton.disabled = true;
                });
        }

        tahunSelect?.addEventListener('change', function () {
            applyDefaultAmountFromJenis();
            updatePeriodeRange();
            syncPeriodeInputForJenis();
            refreshPreview();
        });
        jenisSelect?.addEventListener('change', function () {
            applyDefaultAmountFromJenis();
            syncPeriodeInputForJenis();
            refreshPreview();
        });

        periodeInput?.addEventListener('change', refreshPreview);

        if (kelasSelect && window.jQuery) {
            window.jQuery(kelasSelect).on('change', refreshPreview);
        }

        document.querySelector('[data-open-modal="generate-spp-modal"]')?.addEventListener('click', function () {
            setTimeout(function () {
                applyDefaultAmountFromJenis();
                updatePeriodeRange();
                syncPeriodeInputForJenis();
                refreshPreview();
            }, 0);
        });
    }

    function initEditForm() {
        const siswaLabel = document.getElementById('tagihan-edit-siswa');
        const jenisLabel = document.getElementById('tagihan-edit-jenis');
        const tahunLabel = document.getElementById('tagihan-edit-tahun');
        const enableCicilanInput = document.getElementById('tagihan-edit-enable-cicilan');
        const cicilanLockNote = document.getElementById('tagihan-edit-cicilan-lock-note');

        document.addEventListener('edit-record-populated', function (event) {
            const form = event.detail?.form;
            if (!form || form.id !== 'tagihan-edit-form') return;

            const record = event.detail.record || {};
            if (siswaLabel) siswaLabel.textContent = record._siswa_label || '-';
            if (jenisLabel) jenisLabel.textContent = record._jenis_label || '-';
            if (tahunLabel) tahunLabel.textContent = record._tahun_label || '-';

            const cicilanInProgress = !!record._cicilan_in_progress;
            if (enableCicilanInput) {
                enableCicilanInput.disabled = cicilanInProgress;
                if (cicilanInProgress) {
                    enableCicilanInput.checked = true;
                }
            }
            if (cicilanLockNote) {
                cicilanLockNote.classList.toggle('hidden', !cicilanInProgress);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('tagihan-page-config');
        if (!root) return;

        initCreateForm({
            defaultSpp: parseInt(root.dataset.defaultSpp || '0', 10),
            defaultJenisTagihanId: root.dataset.defaultJenisTagihanId || '',
            defaultDueDate: root.dataset.defaultDueDate || '',
            defaultPeriode: root.dataset.defaultPeriode || '',
        });

        initGenerateForm({
            previewUrl: root.dataset.generatePreviewUrl || '',
            tahunAkademikList: readJson('tagihan-tahun-flat', []),
            defaultPeriode: root.dataset.defaultPeriode || '',
        });

        initEditForm();
    });
})();
