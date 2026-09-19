(function () {
    var MIN_ROWS = 3;
    var MAX_ROWS = 50;

    function isEditMode(form) {
        return (form.dataset.method || 'POST').toUpperCase() === 'PUT';
    }

    function setPanelActive(panel, active) {
        if (!panel) {
            return;
        }

        panel.classList.toggle('hidden', !active);
        panel.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            if (active) {
                el.removeAttribute('disabled');
            } else {
                el.setAttribute('disabled', '');
            }
        });
    }

    function reindexEntryRows(container) {
        container.querySelectorAll('[data-entry-row]').forEach(function (row, index) {
            var dateInput = row.querySelector('[data-entry-date]');
            var nameInput = row.querySelector('[data-entry-name]');

            if (dateInput) {
                dateInput.name = 'entries[' + index + '][date]';
            }
            if (nameInput) {
                nameInput.name = 'entries[' + index + '][name]';
            }
        });
    }

    function createEntryRow(template, container, values) {
        if (container.querySelectorAll('[data-entry-row]').length >= MAX_ROWS) {
            window.showAlert?.({
                title: 'Batas baris',
                message: 'Maksimal ' + MAX_ROWS + ' tanggal libur per simpan.',
                variant: 'warning',
            });
            return null;
        }

        var fragment = template.content.cloneNode(true);
        var row = fragment.querySelector('[data-entry-row]');
        var dateInput = row.querySelector('[data-entry-date]');
        var nameInput = row.querySelector('[data-entry-name]');

        if (values?.date) {
            dateInput.value = values.date;
        }
        if (values?.name) {
            nameInput.value = values.name;
        }

        container.appendChild(row);
        reindexEntryRows(container);

        return row;
    }

    function resetEntryRows(container, template) {
        container.innerHTML = '';
        for (var i = 0; i < MIN_ROWS; i++) {
            createEntryRow(template, container);
        }
    }

    function pruneEmptyRows(container) {
        container.querySelectorAll('[data-entry-row]').forEach(function (row) {
            var dateInput = row.querySelector('[data-entry-date]');
            var nameInput = row.querySelector('[data-entry-name]');
            var hasDate = Boolean(dateInput?.value);
            var hasName = Boolean(nameInput?.value?.trim());

            if (!hasDate && !hasName) {
                row.remove();
            }
        });
        reindexEntryRows(container);
    }

    function parseIsoDate(value) {
        if (!value) {
            return null;
        }

        var parts = value.split('-').map(Number);
        if (parts.length !== 3) {
            return null;
        }

        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function formatIsoDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function eachDateInRange(fromValue, toValue) {
        var fromDate = parseIsoDate(fromValue);
        var toDate = parseIsoDate(toValue);
        var dates = [];

        if (!fromDate || !toDate || fromDate > toDate) {
            return dates;
        }

        var cursor = new Date(fromDate.getTime());
        while (cursor <= toDate) {
            dates.push(formatIsoDate(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }

        return dates;
    }

    window.initHariLiburForm = function () {
        var form = document.getElementById('hari-libur-form');
        if (!form || form.dataset.hariLiburFormBound) {
            return;
        }

        form.dataset.hariLiburFormBound = '1';

        var bulkPanel = document.getElementById('hari-libur-bulk-panel');
        var editPanel = document.getElementById('hari-libur-edit-panel');
        var entriesContainer = document.getElementById('hari-libur-entries');
        var template = document.getElementById('hari-libur-entry-template');
        var submitBtn = document.getElementById('hari-libur-submit');
        var bulkNotes = document.getElementById('hari-libur-bulk-notes');
        var editNotes = document.getElementById('hari-libur-edit-notes');
        var sekolahSelect = document.getElementById('hari-libur-sekolah_id');
        var appliesToSelect = document.getElementById('hari-libur-applies_to');
        var rangeFrom = document.getElementById('hari-libur-range-from');
        var rangeTo = document.getElementById('hari-libur-range-to');
        var rangeName = document.getElementById('hari-libur-range-name');

        function setCreateMode() {
            setPanelActive(bulkPanel, true);
            setPanelActive(editPanel, false);

            if (bulkNotes) {
                bulkNotes.setAttribute('name', 'notes');
                bulkNotes.removeAttribute('disabled');
            }
            if (editNotes) {
                editNotes.removeAttribute('name');
                editNotes.setAttribute('disabled', '');
            }

            resetEntryRows(entriesContainer, template);
            window.applyModalTitle?.('hari-libur-modal', 'create', 'Tambah Hari Libur');
            if (submitBtn) {
                submitBtn.textContent = 'Simpan';
            }
            if (sekolahSelect) {
                sekolahSelect.value = '';
                sekolahSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (appliesToSelect) {
                appliesToSelect.value = 'both';
            }
        }

        function setEditMode() {
            setPanelActive(bulkPanel, false);
            setPanelActive(editPanel, true);

            if (bulkNotes) {
                bulkNotes.removeAttribute('name');
                bulkNotes.setAttribute('disabled', '');
            }
            if (editNotes) {
                editNotes.setAttribute('name', 'notes');
                editNotes.removeAttribute('disabled');
            }

            entriesContainer.querySelectorAll('[data-entry-row]').forEach(function (row) {
                row.querySelectorAll('input').forEach(function (input) {
                    input.removeAttribute('name');
                    input.setAttribute('disabled', '');
                });
            });

            window.applyModalTitle?.('hari-libur-modal', 'edit', 'Ubah Hari Libur');
            if (submitBtn) {
                submitBtn.textContent = 'Simpan Perubahan';
            }
        }

        resetEntryRows(entriesContainer, template);
        setCreateMode();

        document.getElementById('hari-libur-add-row')?.addEventListener('click', function () {
            createEntryRow(template, entriesContainer);
        });

        entriesContainer.addEventListener('click', function (event) {
            var removeBtn = event.target.closest('[data-remove-entry]');
            if (!removeBtn) {
                return;
            }

            var rows = entriesContainer.querySelectorAll('[data-entry-row]');
            if (rows.length <= 1) {
                window.showAlert?.({
                    title: 'Minimal satu baris',
                    message: 'Isi minimal satu tanggal libur.',
                    variant: 'warning',
                });
                return;
            }

            removeBtn.closest('[data-entry-row]')?.remove();
            reindexEntryRows(entriesContainer);
        });

        document.getElementById('hari-libur-fill-range')?.addEventListener('click', function () {
            var name = rangeName?.value?.trim() || '';
            var dates = eachDateInRange(rangeFrom?.value, rangeTo?.value);

            if (!dates.length) {
                window.showAlert?.({
                    title: 'Rentang tidak valid',
                    message: 'Pilih tanggal mulai dan selesai yang benar.',
                    variant: 'warning',
                });
                return;
            }

            if (!name) {
                window.showAlert?.({
                    title: 'Nama libur wajib',
                    message: 'Isi nama libur untuk rentang tanggal ini.',
                    variant: 'warning',
                });
                return;
            }

            if (dates.length > MAX_ROWS) {
                window.showAlert?.({
                    title: 'Terlalu banyak tanggal',
                    message: 'Rentang menghasilkan ' + dates.length + ' hari. Maksimal ' + MAX_ROWS + ' per simpan.',
                    variant: 'warning',
                });
                return;
            }

            entriesContainer.innerHTML = '';
            dates.forEach(function (date) {
                createEntryRow(template, entriesContainer, { date: date, name: name });
            });
        });

        document.querySelector('[data-open-modal="hari-libur-modal"]')?.addEventListener('click', function () {
            setTimeout(setCreateMode, 0);
        });

        document.addEventListener('edit-record-populated', function (event) {
            if (event.detail?.form?.id !== 'hari-libur-form') {
                return;
            }

            var record = event.detail.record || {};
            setEditMode();

            var dateInput = editPanel.querySelector('[name="date"]');
            var nameInput = editPanel.querySelector('[name="name"]');

            if (dateInput && record.date) {
                dateInput.value = String(record.date).substring(0, 10);
            }
            if (nameInput) {
                nameInput.value = record.name ?? '';
            }
            if (editNotes) {
                editNotes.value = record.notes ?? '';
            }
            if (sekolahSelect) {
                sekolahSelect.value = record.sekolah_id == null ? '' : String(record.sekolah_id);
                sekolahSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (appliesToSelect && record.applies_to) {
                appliesToSelect.value = String(record.applies_to);
            }
        });

        form.addEventListener('submit', function (event) {
            if (isEditMode(form)) {
                return;
            }

            pruneEmptyRows(entriesContainer);

            if (!entriesContainer.querySelector('[data-entry-row]')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                window.showAlert?.({
                    title: 'Belum ada tanggal',
                    message: 'Tambahkan minimal satu tanggal libur.',
                    variant: 'warning',
                });
                createEntryRow(template, entriesContainer);
                return;
            }

            reindexEntryRows(entriesContainer);
        }, true);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initHariLiburForm?.();
    });
})();
