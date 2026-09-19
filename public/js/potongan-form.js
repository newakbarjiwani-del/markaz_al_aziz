(function () {
    'use strict';

    var MIN_TAGIHAN = 1000;
    var DEFAULT_SAMPLE_AMOUNT = 300000;

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    }

    function getBillRows() {
        return Array.from(document.querySelectorAll('.potongan-bill-cut-row--meta'));
    }

    function getBillFieldsRow(metaRow) {
        var next = metaRow?.nextElementSibling;

        return next?.classList.contains('potongan-bill-cut-row--fields') ? next : null;
    }

    function resolveMetaRow(el) {
        var meta = el?.closest('.potongan-bill-cut-row--meta');
        if (meta) {
            return meta;
        }

        var fields = el?.closest('.potongan-bill-cut-row--fields');
        var previous = fields?.previousElementSibling;

        return previous?.classList.contains('potongan-bill-cut-row--meta') ? previous : null;
    }

    function queryBillField(metaRow, selector) {
        var fieldsRow = getBillFieldsRow(metaRow);

        return fieldsRow?.querySelector(selector) || metaRow?.querySelector(selector) || null;
    }

    function rowEnabled(metaRow) {
        return metaRow.querySelector('.potongan-bill-cut-enabled')?.checked === true;
    }

    function rowUsesDefault(metaRow) {
        return queryBillField(metaRow, '.potongan-bill-cut-use-default')?.checked === true;
    }

    function rowUsesDefaultMax(metaRow) {
        return queryBillField(metaRow, '.potongan-bill-cut-use-default-max')?.checked === true;
    }

    function getMasterDefaultMax() {
        return document.getElementById('potongan-siswa-max')?.value || '12';
    }

    function getMasterDefault() {
        var tipe = document.getElementById('potongan-siswa-tipe');
        var nilai = document.getElementById('potongan-siswa-nilai');

        return {
            tipe: tipe?.value || 'percent',
            nilai: nilai ? parseNilai(nilai.value) : 0,
        };
    }

    function getKatalogDefaults() {
        var select = document.getElementById('potongan-siswa-jenis');
        var option = select?.selectedOptions?.[0];

        if (!option || !option.value) {
            return null;
        }

        return {
            tipe: option.getAttribute('data-tipe') || 'percent',
            nilai: option.getAttribute('data-nilai') || '',
        };
    }

    function parseNilai(value) {
        if (typeof window.parseFormattedNumber === 'function') {
            return window.parseFormattedNumber(value);
        }

        var parsed = parseInt(String(value || '').replace(/\./g, ''), 10);

        return isNaN(parsed) ? 0 : parsed;
    }

    function remainingAfterCut(bruto, cut) {
        var remaining = Math.max(0, bruto - cut);

        if (remaining === 0) {
            return 0;
        }

        return Math.max(MIN_TAGIHAN, remaining);
    }

    function syncNilaiInputMode(input, tipe) {
        if (!input) {
            return;
        }

        var current = parseNilai(input.value);

        if (tipe === 'percent') {
            input.classList.remove('formattedNumber');
            input.classList.add('onlyNumber');
            input.setAttribute('max', '100');
            input.setAttribute('min', '1');
            input.placeholder = '1–100';
            if (current > 100) {
                current = 100;
            }
            input.value = current > 0 ? String(current) : '';
        } else {
            input.classList.remove('onlyNumber');
            input.classList.add('formattedNumber');
            input.removeAttribute('max');
            input.setAttribute('min', '1');
            input.placeholder = 'Nominal rupiah';
            if (current > 0) {
                input.value = typeof window.formatNumberId === 'function'
                    ? window.formatNumberId(current)
                    : String(current);
            }
        }
    }

    function calculateCut(tipe, nilai, bruto) {
        var amount = parseNilai(nilai);
        if (!amount || amount <= 0) {
            return 0;
        }

        if (tipe === 'percent') {
            amount = Math.min(100, amount);
            if (amount >= 100) {
                return bruto;
            }

            return Math.floor(bruto * amount / 100);
        }

        return Math.min(amount, Math.max(0, bruto - MIN_TAGIHAN));
    }

    function getEffectiveCutForRow(metaRow) {
        if (rowUsesDefault(metaRow)) {
            return getMasterDefault();
        }

        var tipe = queryBillField(metaRow, '.potongan-bill-cut-tipe');
        var nilai = queryBillField(metaRow, '.potongan-bill-cut-nilai');

        return {
            tipe: tipe?.value || 'percent',
            nilai: nilai ? parseNilai(nilai.value) : 0,
        };
    }

    function syncMasterDefaultHint() {
        var tipe = document.getElementById('potongan-siswa-tipe');
        var nilai = document.getElementById('potongan-siswa-nilai');

        if (!tipe || !nilai) {
            return;
        }

        syncNilaiInputMode(nilai, tipe.value);

        var hint = document.getElementById('potongan-siswa-nilai-hint');
        if (hint) {
            hint.textContent = tipe.value === 'percent'
                ? 'Persen: 1–100. Isi 100 untuk membebaskan tagihan (sisa Rp 0).'
                : 'Nominal rupiah. Tagihan bersisa minimal Rp 1.000 kecuali potongan 100%.';
        }
    }

    function updateDefaultPreview() {
        var cutLabel = document.getElementById('potongan-default-preview-cut');
        var netLabel = document.getElementById('potongan-default-preview-net');
        var master = getMasterDefault();

        if (!cutLabel || !netLabel) {
            return;
        }

        var cut = calculateCut(master.tipe, master.nilai, DEFAULT_SAMPLE_AMOUNT);
        var net = remainingAfterCut(DEFAULT_SAMPLE_AMOUNT, cut);

        cutLabel.textContent = cut > 0 ? formatRupiah(cut) : '—';
        netLabel.textContent = cut > 0 ? formatRupiah(net) : '—';
    }

    function updateRowPreview(metaRow) {
        var preview = metaRow.querySelector('.potongan-bill-cut-preview');
        if (!preview) {
            return;
        }

        if (!rowEnabled(metaRow)) {
            preview.textContent = '—';
            return;
        }

        var bruto = parseInt(metaRow.getAttribute('data-default-amount') || '0', 10) || 0;
        var cutValues = getEffectiveCutForRow(metaRow);
        var cut = calculateCut(cutValues.tipe, cutValues.nilai, bruto);
        var net = remainingAfterCut(bruto, cut);
        var suffix = (rowUsesDefault(metaRow) ? ' · def. nilai' : ' · nilai khusus')
            + (rowUsesDefaultMax(metaRow) ? ' · def. kuota ' + getMasterDefaultMax() + 'x' : ' · kuota ' + (queryBillField(metaRow, '.potongan-bill-cut-max')?.value || '—') + 'x');

        preview.textContent = cut > 0
            ? 'Potongan ' + formatRupiah(cut) + ' · sisa ' + formatRupiah(net) + suffix
            : '—';
    }

    function setGroupOpacity(metaRow, enabled) {
        metaRow.classList.toggle('opacity-50', !enabled);
        var fieldsRow = getBillFieldsRow(metaRow);
        if (fieldsRow) {
            fieldsRow.classList.toggle('opacity-50', !enabled);
        }
    }

    function syncRowInputs(metaRow) {
        var enabled = rowEnabled(metaRow);
        var useDefault = queryBillField(metaRow, '.potongan-bill-cut-use-default');
        var useDefaultMax = queryBillField(metaRow, '.potongan-bill-cut-use-default-max');
        var tipe = queryBillField(metaRow, '.potongan-bill-cut-tipe');
        var nilai = queryBillField(metaRow, '.potongan-bill-cut-nilai');
        var maxInput = queryBillField(metaRow, '.potongan-bill-cut-max');

        if (useDefault) {
            useDefault.disabled = !enabled;
        }
        if (useDefaultMax) {
            useDefaultMax.disabled = !enabled;
        }

        var usesDefault = enabled && rowUsesDefault(metaRow);
        var usesDefaultMax = enabled && rowUsesDefaultMax(metaRow);

        if (tipe) {
            tipe.disabled = !enabled || usesDefault;
            if (usesDefault) {
                tipe.value = getMasterDefault().tipe;
            }
        }

        if (nilai) {
            nilai.disabled = !enabled || usesDefault;
            if (usesDefault) {
                nilai.value = getMasterDefault().nilai;
            }
            syncNilaiInputMode(nilai, tipe ? tipe.value : 'percent');
        }

        if (maxInput) {
            maxInput.disabled = !enabled || usesDefaultMax;
            if (usesDefaultMax) {
                maxInput.value = getMasterDefaultMax();
            }
        }

        setGroupOpacity(metaRow, enabled);
        updateRowPreview(metaRow);
    }

    function syncToggleAllCheckbox() {
        var toggle = document.getElementById('potongan-bill-cut-toggle-all');
        if (!toggle) {
            return;
        }

        var rows = getBillRows();
        var enabledCount = rows.filter(rowEnabled).length;

        toggle.checked = enabledCount > 0 && enabledCount === rows.length;
        toggle.indeterminate = enabledCount > 0 && enabledCount < rows.length;
    }

    function syncAllRows() {
        getBillRows().forEach(syncRowInputs);
        syncToggleAllCheckbox();
    }

    function setAllBillRowsEnabled(enabled) {
        getBillRows().forEach(function (metaRow) {
            var checkbox = metaRow.querySelector('.potongan-bill-cut-enabled');
            if (checkbox) {
                checkbox.checked = enabled;
            }
            syncRowInputs(metaRow);
        });

        if (enabled) {
            applyDefaultToCheckedRows();
        }

        syncToggleAllCheckbox();
    }

    function applyDefaultToCheckedRows() {
        getBillRows().forEach(function (metaRow) {
            if (!rowEnabled(metaRow)) {
                return;
            }

            var useDefault = queryBillField(metaRow, '.potongan-bill-cut-use-default');
            var useDefaultMax = queryBillField(metaRow, '.potongan-bill-cut-use-default-max');
            if (useDefault) {
                useDefault.checked = true;
                delete useDefault.dataset.touched;
            }
            if (useDefaultMax) {
                useDefaultMax.checked = true;
                delete useDefaultMax.dataset.touched;
            }

            syncRowInputs(metaRow);
        });
    }

    function fillMasterFromKatalog() {
        var defaults = getKatalogDefaults();
        var tipe = document.getElementById('potongan-siswa-tipe');
        var nilai = document.getElementById('potongan-siswa-nilai');

        if (!defaults || !tipe || !nilai) {
            return;
        }

        tipe.value = defaults.tipe;
        nilai.value = defaults.nilai;
        syncMasterDefaultHint();
        updateDefaultPreview();
    }

    function updateKatalogHint() {
        var select = document.getElementById('potongan-siswa-jenis');
        var hint = document.getElementById('potongan-jenis-katalog-hint');
        var hintText = document.getElementById('potongan-jenis-katalog-hint-text');
        var defaults = getKatalogDefaults();

        if (!hint || !hintText) {
            return;
        }

        if (!defaults) {
            hint.classList.add('hidden');
            hintText.textContent = '';
            return;
        }

        var nilaiLabel = defaults.tipe === 'fixed'
            ? formatRupiah(defaults.nilai)
            : defaults.nilai + '%';
        var option = select.selectedOptions[0];
        var keterangan = option.getAttribute('data-keterangan');

        hintText.textContent = (defaults.tipe === 'fixed' ? 'Nominal ' : 'Persen ')
            + nilaiLabel
            + (keterangan ? ' — ' + keterangan : '');
        hint.classList.remove('hidden');
    }

    function autoEnableSppOnFirstSelect() {
        var hasChecked = getBillRows().some(rowEnabled);
        if (hasChecked) {
            return;
        }

        var sppRow = getBillRows().find(function (metaRow) {
            return metaRow.getAttribute('data-is-spp') === '1';
        }) || getBillRows()[0];

        if (!sppRow) {
            return;
        }

        var checkbox = sppRow.querySelector('.potongan-bill-cut-enabled');
        if (checkbox) {
            checkbox.checked = true;
        }

        applyDefaultToCheckedRows();
        syncToggleAllCheckbox();
    }

    function applyBillCutsRecord(billCuts) {
        var byId = {};

        (billCuts || []).forEach(function (cut) {
            byId[String(cut.jenis_tagihan_id)] = cut;
        });

        getBillRows().forEach(function (metaRow) {
            var jenisId = metaRow.getAttribute('data-jenis-id');
            var cut = byId[jenisId];
            var enabled = metaRow.querySelector('.potongan-bill-cut-enabled');
            var useDefault = queryBillField(metaRow, '.potongan-bill-cut-use-default');
            var useDefaultMax = queryBillField(metaRow, '.potongan-bill-cut-use-default-max');
            var tipe = queryBillField(metaRow, '.potongan-bill-cut-tipe');
            var nilai = queryBillField(metaRow, '.potongan-bill-cut-nilai');
            var maxInput = queryBillField(metaRow, '.potongan-bill-cut-max');

            if (enabled) {
                enabled.checked = !!(cut && cut.enabled);
            }

            if (useDefault) {
                useDefault.checked = cut ? !!cut.use_default : true;
                if (cut && !cut.use_default) {
                    useDefault.dataset.touched = '1';
                } else {
                    delete useDefault.dataset.touched;
                }
            }

            if (useDefaultMax) {
                useDefaultMax.checked = cut ? !!cut.use_default_max : true;
                if (cut && !cut.use_default_max) {
                    useDefaultMax.dataset.touched = '1';
                } else {
                    delete useDefaultMax.dataset.touched;
                }
            }

            if (tipe && cut && !cut.use_default) {
                tipe.value = cut.tipe || 'percent';
            }

            if (nilai && cut && cut.nilai != null && !cut.use_default) {
                nilai.value = cut.nilai;
            }

            if (maxInput && cut && cut.max_pemakaian != null && !cut.use_default_max) {
                maxInput.value = cut.max_pemakaian;
            }

            syncRowInputs(metaRow);
        });

        syncToggleAllCheckbox();
    }

    function resetBillCutsForm() {
        getBillRows().forEach(function (metaRow) {
            var enabled = metaRow.querySelector('.potongan-bill-cut-enabled');
            var useDefault = queryBillField(metaRow, '.potongan-bill-cut-use-default');
            var useDefaultMax = queryBillField(metaRow, '.potongan-bill-cut-use-default-max');
            var tipe = queryBillField(metaRow, '.potongan-bill-cut-tipe');
            var nilai = queryBillField(metaRow, '.potongan-bill-cut-nilai');
            var maxInput = queryBillField(metaRow, '.potongan-bill-cut-max');

            if (enabled) {
                enabled.checked = false;
            }
            if (useDefault) {
                useDefault.checked = true;
                delete useDefault.dataset.touched;
            }
            if (useDefaultMax) {
                useDefaultMax.checked = true;
                delete useDefaultMax.dataset.touched;
            }
            if (tipe) {
                tipe.value = 'percent';
            }
            if (nilai) {
                nilai.value = '';
            }
            if (maxInput) {
                maxInput.value = '';
            }

            syncRowInputs(metaRow);
        });

        var hint = document.getElementById('potongan-jenis-katalog-hint');
        if (hint) {
            hint.classList.add('hidden');
        }

        updateDefaultPreview();
        syncToggleAllCheckbox();
    }

    document.addEventListener('change', function (event) {
        if (event.target.id === 'potongan-bill-cut-toggle-all') {
            setAllBillRowsEnabled(event.target.checked);
            return;
        }

        if (event.target.classList.contains('potongan-bill-cut-enabled')) {
            var enabledRow = resolveMetaRow(event.target);
            if (enabledRow && event.target.checked) {
                var useDefault = queryBillField(enabledRow, '.potongan-bill-cut-use-default');
                var useDefaultMax = queryBillField(enabledRow, '.potongan-bill-cut-use-default-max');
                if (useDefault && !useDefault.dataset.touched) {
                    useDefault.checked = true;
                }
                if (useDefaultMax && !useDefaultMax.dataset.touched) {
                    useDefaultMax.checked = true;
                }
            }
            if (enabledRow) {
                syncRowInputs(enabledRow);
            }
            syncToggleAllCheckbox();
            return;
        }

        if (event.target.classList.contains('potongan-bill-cut-use-default')) {
            if (event.target.checked) {
                delete event.target.dataset.touched;
            } else {
                event.target.dataset.touched = '1';
            }

            var customRow = resolveMetaRow(event.target);
            if (customRow) {
                syncRowInputs(customRow);
            }
            return;
        }

        if (event.target.classList.contains('potongan-bill-cut-use-default-max')) {
            if (event.target.checked) {
                delete event.target.dataset.touched;
            } else {
                event.target.dataset.touched = '1';
            }

            var maxRow = resolveMetaRow(event.target);
            if (maxRow) {
                syncRowInputs(maxRow);
            }
            return;
        }

        if (event.target.classList.contains('potongan-bill-cut-tipe')
            || event.target.classList.contains('potongan-bill-cut-nilai')
            || event.target.classList.contains('potongan-bill-cut-max')) {
            var valueRow = resolveMetaRow(event.target);
            if (valueRow) {
                syncRowInputs(valueRow);
            }
            return;
        }

        if (event.target.id === 'potongan-siswa-tipe' || event.target.id === 'potongan-siswa-nilai') {
            syncMasterDefaultHint();
            updateDefaultPreview();
            syncAllRows();
            return;
        }

        if (event.target.id === 'potongan-siswa-max') {
            syncAllRows();
            return;
        }

        if (event.target.id === 'potongan-siswa-jenis') {
            fillMasterFromKatalog();
            updateKatalogHint();
            autoEnableSppOnFirstSelect();
            applyDefaultToCheckedRows();
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target.id === 'potongan-siswa-nilai') {
            updateDefaultPreview();
            syncAllRows();
            return;
        }

        if (event.target.classList.contains('potongan-bill-cut-nilai')) {
            var previewRow = resolveMetaRow(event.target);
            if (previewRow) {
                updateRowPreview(previewRow);
            }
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.id === 'potongan-bill-apply-default-all') {
            applyDefaultToCheckedRows();
        }
    });

    document.addEventListener('edit-record-populated', function (event) {
        if (event.detail?.form?.id !== 'potongan-siswa-form') {
            return;
        }

        var record = event.detail.record || {};
        var selectWrap = document.getElementById('potongan-siswa-select-wrap');
        var readonlyWrap = document.getElementById('potongan-siswa-readonly');
        var readonlyLabel = document.getElementById('potongan-siswa-readonly-label');
        var hiddenLabel = document.getElementById('potongan-siswa-label-hidden');

        if (selectWrap) {
            selectWrap.classList.add('hidden');
        }
        if (readonlyWrap) {
            readonlyWrap.classList.remove('hidden');
        }
        if (readonlyLabel) {
            readonlyLabel.value = record._siswa_label || '';
        }
        if (hiddenLabel) {
            hiddenLabel.value = record._siswa_label || '';
        }

        syncMasterDefaultHint();
        updateDefaultPreview();
        applyBillCutsRecord(record.bill_cuts);
        updateKatalogHint();
    });

    document.addEventListener('reset', function (event) {
        if (event.target.id !== 'potongan-siswa-form') {
            return;
        }

        var selectWrap = document.getElementById('potongan-siswa-select-wrap');
        var readonlyWrap = document.getElementById('potongan-siswa-readonly');

        if (selectWrap) {
            selectWrap.classList.remove('hidden');
        }
        if (readonlyWrap) {
            readonlyWrap.classList.add('hidden');
        }

        var methodInput = document.getElementById('potongan-siswa-method');
        if (methodInput) {
            methodInput.value = 'POST';
        }

        resetBillCutsForm();
    });

    document.addEventListener('DOMContentLoaded', function () {
        syncMasterDefaultHint();
        updateDefaultPreview();
        syncAllRows();
    });

    document.addEventListener('change', function (event) {
        var tipeSelect = event.target.closest('#katalog-potongan-tipe');
        if (!tipeSelect) {
            return;
        }

        var nilaiInput = document.getElementById(tipeSelect.id.replace('-tipe', '-nilai'));
        if (!nilaiInput) {
            return;
        }

        nilaiInput.max = tipeSelect.value === 'percent' ? '100' : '';
        nilaiInput.placeholder = tipeSelect.value === 'percent' ? '1–100' : 'Nominal rupiah';
        syncNilaiInputMode(nilaiInput, tipeSelect.value);
    });
})();
