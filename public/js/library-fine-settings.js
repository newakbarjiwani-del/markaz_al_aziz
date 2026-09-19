(function () {
    var addButton = document.getElementById('add-kondisi-fine-row');
    var rowsContainer = document.getElementById('kondisi-fine-rows');
    var template = document.getElementById('kondisi-fine-row-template');

    if (!addButton || !rowsContainer || !template) {
        return;
    }

    function nextIndex() {
        return rowsContainer.querySelectorAll('[data-fine-row]').length;
    }

    function usedKondisiValues() {
        var used = new Set();
        rowsContainer.querySelectorAll('select[name*="[kondisi]"]').forEach(function (select) {
            if (select.value) {
                used.add(select.value);
            }
        });
        return used;
    }

    function firstAvailableKondisi(row) {
        var used = usedKondisiValues();
        var select = row.querySelector('select[name*="[kondisi]"]');
        if (!select) {
            return;
        }

        var available = Array.from(select.options).find(function (option) {
            return option.value && !used.has(option.value);
        });

        if (available) {
            select.value = available.value;
        }
    }

    function removeEmptyState() {
        document.getElementById('kondisi-fine-empty')?.remove();
    }

    function ensureEmptyState() {
        if (rowsContainer.querySelector('[data-fine-row]')) {
            return;
        }

        if (document.getElementById('kondisi-fine-empty')) {
            return;
        }

        var empty = document.createElement('p');
        empty.id = 'kondisi-fine-empty';
        empty.className = 'rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-600';
        empty.innerHTML = 'Belum ada denda kondisi. Klik <strong>Tambah Denda</strong> untuk menambahkan.';
        rowsContainer.appendChild(empty);
    }

    function reindexRows() {
        rowsContainer.querySelectorAll('[data-fine-row]').forEach(function (row, index) {
            row.querySelectorAll('[name^="kondisi_fines["]').forEach(function (input) {
                input.name = input.name.replace(/kondisi_fines\[\d+\]/, 'kondisi_fines[' + index + ']');
            });
        });
    }

    function syncLabelFromKondisi(row) {
        var select = row.querySelector('select[name*="[kondisi]"]');
        var labelInput = row.querySelector('input[name*="[label]"]');
        if (!select || !labelInput || labelInput.dataset.manual === '1') {
            return;
        }

        labelInput.value = select.selectedOptions[0]?.textContent?.trim() || '';
    }

    addButton.addEventListener('click', function () {
        var used = usedKondisiValues();
        var templateSelect = template.content.querySelector('select[name*="[kondisi]"]');
        var hasAvailable = templateSelect
            ? Array.from(templateSelect.options).some(function (option) {
                return option.value && !used.has(option.value);
            })
            : true;

        if (!hasAvailable) {
            window.showAlert?.({
                title: 'Tidak Dapat Menambah',
                message: 'Semua kondisi buku sudah punya aturan denda.',
                variant: 'warning',
            });
            return;
        }

        removeEmptyState();

        var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var row = wrapper.firstElementChild;
        rowsContainer.appendChild(row);
        firstAvailableKondisi(row);
        syncLabelFromKondisi(row);
        window.initFormattedAmountFields?.(row);

        var amountInput = row.querySelector('.formattedNumber');
        amountInput?.focus();
    });

    rowsContainer.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('[data-remove-fine-row]');
        if (!removeBtn) {
            return;
        }

        removeBtn.closest('[data-fine-row]')?.remove();
        reindexRows();
        ensureEmptyState();
    });

    rowsContainer.addEventListener('change', function (event) {
        var select = event.target.closest('select[name*="[kondisi]"]');
        if (!select) {
            return;
        }

        var row = select.closest('[data-fine-row]');
        var duplicates = Array.from(rowsContainer.querySelectorAll('select[name*="[kondisi]"]'))
            .filter(function (other) {
                return other !== select && other.value === select.value;
            });

        if (duplicates.length > 0) {
            window.showAlert?.({
                title: 'Kondisi Duplikat',
                message: 'Kondisi ini sudah dipakai di baris lain. Pilih kondisi yang belum ada.',
                variant: 'warning',
            });
            firstAvailableKondisi(row);
        }

        syncLabelFromKondisi(row);
    });

    rowsContainer.addEventListener('input', function (event) {
        var labelInput = event.target.closest('input[name*="[label]"]');
        if (labelInput) {
            labelInput.dataset.manual = '1';
        }
    });
})();
