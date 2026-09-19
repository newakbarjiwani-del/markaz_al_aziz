(function () {
    var SPREADSHEET_RULES = {
        acceptedTypes: [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
        ],
        acceptedExtensions: ['xlsx', 'xls'],
        maxBytes: 10 * 1024 * 1024,
    };

    function hasSpreadsheetExtension(filename) {
        var lower = (filename || '').toLowerCase();
        return SPREADSHEET_RULES.acceptedExtensions.some(function (ext) {
            return lower.endsWith('.' + ext);
        });
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes <= 0) return '0 KB';
        if (bytes < 1024 * 1024) {
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function validateSpreadsheetFile(file) {
        if (!file) {
            return 'Pilih file Excel terlebih dahulu.';
        }

        if (!hasSpreadsheetExtension(file.name)) {
            return 'Gunakan file Excel .xlsx atau .xls.';
        }

        if (file.type && SPREADSHEET_RULES.acceptedTypes.indexOf(file.type) === -1 && !hasSpreadsheetExtension(file.name)) {
            return 'Format file tidak didukung.';
        }

        if (file.size > SPREADSHEET_RULES.maxBytes) {
            return 'Ukuran file melebihi 10 MB.';
        }

        return null;
    }

    function getInputFromContext(node) {
        if (!node) return null;
        if (node.matches?.('[data-spreadsheet-import]')) return node;
        return node.querySelector?.('[data-spreadsheet-import]') || null;
    }

    window.getSpreadsheetImportFile = function (context) {
        var input = getInputFromContext(context);
        return input?.files?.[0] || null;
    };

    window.resetSpreadsheetImportUpload = function (context) {
        var input = getInputFromContext(context);
        if (!input) return;

        input.value = '';
        syncSpreadsheetUploadZone(input);
    };

    function syncSpreadsheetUploadZone(input) {
        var zone = input.closest('[data-spreadsheet-upload-zone]');
        if (!zone) return;

        var nameEl = zone.querySelector('[data-spreadsheet-upload-name]');
        var sizeEl = zone.querySelector('[data-spreadsheet-upload-size]');
        var file = input.files?.[0];

        if (file) {
            zone.classList.add('is-filled');
            if (nameEl) nameEl.textContent = file.name;
            if (sizeEl) sizeEl.textContent = formatFileSize(file.size);
            return;
        }

        zone.classList.remove('is-filled', 'is-dragover');
        if (nameEl) nameEl.textContent = '';
        if (sizeEl) sizeEl.textContent = '';
    }

    function assignSpreadsheetFile(input, file) {
        var error = validateSpreadsheetFile(file);
        if (error) {
            window.showToast?.(error, 'error');
            input.value = '';
            syncSpreadsheetUploadZone(input);
            return false;
        }

        try {
            var transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
        } catch (e) {
            window.showToast?.('Browser tidak mendukung unggah file ini.', 'error');
            return false;
        }

        syncSpreadsheetUploadZone(input);
        return true;
    }

    function bindSpreadsheetUploadZone(zone) {
        if (zone.dataset.spreadsheetBound) return;
        zone.dataset.spreadsheetBound = '1';

        var input = zone.querySelector('[data-spreadsheet-import]');
        if (!input) return;

        zone.querySelector('[data-spreadsheet-upload-clear]')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            window.resetSpreadsheetImportUpload(input);
        });

        zone.querySelector('[data-spreadsheet-upload-replace]')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            input.click();
        });

        zone.querySelector('[data-spreadsheet-upload-browse]')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            input.click();
        });

        input.addEventListener('change', function () {
            var file = input.files?.[0];
            if (!file) {
                syncSpreadsheetUploadZone(input);
                return;
            }

            if (!assignSpreadsheetFile(input, file)) {
                return;
            }
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', function (event) {
            var file = event.dataTransfer?.files?.[0];
            if (!file) return;
            assignSpreadsheetFile(input, file);
        });

        syncSpreadsheetUploadZone(input);
    }

    window.initSpreadsheetImportUploads = function () {
        document.querySelectorAll('[data-spreadsheet-upload-zone]').forEach(bindSpreadsheetUploadZone);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initSpreadsheetImportUploads?.();
    });
})();
