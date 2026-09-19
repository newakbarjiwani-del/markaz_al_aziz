(function () {
    var EXPORT_MAX_ROWS = 3000;
    var EXPORT_CHUNK_SIZE = 100;
    var isExporting = false;

    if (typeof pdfMake !== 'undefined' && pdfMake.vfs === undefined && typeof pdfFonts !== 'undefined') {
        pdfMake.vfs = pdfFonts.pdfMake?.vfs || pdfFonts.vfs;
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    function sanitizeFilename(name) {
        return String(name || '')
            .trim()
            .replace(/[\\/:*?"<>|]+/g, '-')
            .replace(/\s+/g, ' ')
            || 'export';
    }

    function resolveExportFilename(table) {
        var configured = table?.getAttribute('data-export-filename');
        var base = configured && configured.trim() !== '' ? configured : (document.title || 'export');
        return sanitizeFilename(base);
    }

    function setExportButtonsLoading(loading) {
        var dropdown = document.getElementById('export-excel')?.closest('[data-dropdown-button]');
        if (dropdown) {
            setDropdownExportLoading(dropdown, loading);
            return;
        }

        ['export-excel', 'export-pdf'].forEach(function (id) {
            var button = document.getElementById(id);
            if (!button) return;

            if (!button.dataset.defaultHtml) {
                button.dataset.defaultHtml = button.innerHTML;
            }

            button.disabled = loading;
            if (loading) {
                button.classList.add('opacity-60', 'cursor-not-allowed');
                button.innerHTML = 'Mengekspor...';
            } else {
                button.classList.remove('opacity-60', 'cursor-not-allowed');
                button.innerHTML = button.dataset.defaultHtml || button.innerHTML;
            }
        });
    }

    function setDropdownExportLoading(dropdown, loading) {
        dropdown.classList.toggle('is-loading', loading);
        dropdown.setAttribute('aria-busy', loading ? 'true' : 'false');

        dropdown.querySelectorAll('[data-dropdown-button-toggle], .dropdown-button__split-main').forEach(function (el) {
            el.disabled = loading;
            el.setAttribute('aria-busy', loading ? 'true' : 'false');
        });

        var label = dropdown.querySelector('[data-dropdown-button-label]');
        if (label) {
            if (!label.dataset.defaultLabel) {
                label.dataset.defaultLabel = label.textContent.trim();
            }
            label.textContent = loading ? 'Mengekspor...' : (label.dataset.defaultLabel || 'Export');
        }

        ['export-excel', 'export-pdf'].forEach(function (id) {
            var item = document.getElementById(id);
            if (item) {
                item.disabled = loading;
            }
        });

        if (loading) {
            window.closeDropdownButtons?.();
        }
    }

    function readColumnOptions(table, headers) {
        var raw = table.getAttribute('data-column-options');
        if (!raw) {
            return headers.map(function (title) {
                return { exportable: title !== 'Aksi' };
            });
        }

        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) throw new Error('invalid column options');
            return headers.map(function (title, index) {
                var item = parsed[index] || {};
                return {
                    exportable: item.exportable !== undefined ? !!item.exportable : title !== 'Aksi',
                };
            });
        } catch (e) {
            return headers.map(function (title) {
                return { exportable: title !== 'Aksi' };
            });
        }
    }

    function collectAppliedFilters() {
        var filters = [];
        var form = document.getElementById('filter-form');
        if (!form) return filters;

        Array.from(form.elements || []).forEach(function (el) {
            if (!el.name || el.disabled) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            var value = (el.value || '').toString().trim();
            if (!value) return;

            var label = '';
            if (el.id) {
                label = form.querySelector('label[for="' + el.id + '"]')?.textContent?.trim() || '';
            }
            if (!label) {
                var wrapperLabel = el.closest('div')?.querySelector('label');
                label = wrapperLabel?.textContent?.trim() || el.name;
            }

            var displayValue = value;
            if (el.tagName === 'SELECT') {
                displayValue = el.options?.[el.selectedIndex]?.text?.trim() || value;
            }

            filters.push({
                label: label,
                value: displayValue,
            });
        });

        if (window.mainTable && typeof window.mainTable.search === 'function') {
            var searchValue = (window.mainTable.search() || '').trim();
            if (searchValue) {
                filters.push({
                    label: 'Pencarian tabel',
                    value: searchValue,
                });
            }
        }

        return filters;
    }

    function buildDatatableRequestPayload(table, start, length) {
        var payload = {
            draw: 1,
            start: start,
            length: length,
        };

        var form = document.getElementById('filter-form');
        if (form) {
            new FormData(form).forEach(function (value, key) {
                payload[key] = value;
            });
        }

        if (window.mainTable) {
            if (typeof window.mainTable.search === 'function') {
                payload['search[value]'] = window.mainTable.search() || '';
            }

            if (typeof window.mainTable.order === 'function') {
                var order = window.mainTable.order();
                if (Array.isArray(order) && order[0]) {
                    payload['order[0][column]'] = order[0][0];
                    payload['order[0][dir]'] = order[0][1];
                }
            }
        }

        return payload;
    }

    async function fetchFilteredRows(table) {
        var ajaxUrl = table?.getAttribute('data-ajax-url');
        if (!ajaxUrl) return { rows: [], total: 0, truncated: false };

        var rows = [];
        var start = 0;
        var total = 0;
        var truncated = false;

        while (rows.length < EXPORT_MAX_ROWS) {
            var payload = buildDatatableRequestPayload(table, start, EXPORT_CHUNK_SIZE);
            var url = new URL(ajaxUrl, window.location.origin);
            Object.keys(payload).forEach(function (key) {
                url.searchParams.set(key, payload[key]);
            });

            var response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) {
                throw new Error('Gagal mengambil data export.');
            }

            var json = await response.json();
            var pageRows = Array.isArray(json.data) ? json.data : [];
            total = Number(json.recordsFiltered || 0);
            rows = rows.concat(pageRows);

            if (pageRows.length < EXPORT_CHUNK_SIZE) break;

            start += EXPORT_CHUNK_SIZE;
            if (start >= total) break;
        }

        if (rows.length > EXPORT_MAX_ROWS) {
            rows = rows.slice(0, EXPORT_MAX_ROWS);
            truncated = true;
        } else if (total > EXPORT_MAX_ROWS) {
            truncated = true;
        }

        return { rows: rows, total: total, truncated: truncated };
    }

    function extractValueForExport(cellData) {
        if (cellData && typeof cellData === 'object' && 'display' in cellData && 'raw' in cellData) {
            var raw = cellData.raw;
            var type = cellData.type || 'text';

            if (type === 'number') {
                var numberValue = Number(raw);
                return {
                    value: Number.isFinite(numberValue) ? numberValue : 0,
                    type: 'number',
                };
            }

            if (type === 'date' || type === 'datetime') {
                var parsedDate = new Date(String(raw).replace(' ', 'T'));
                return {
                    value: isNaN(parsedDate.getTime()) ? String(raw) : parsedDate,
                    type: isNaN(parsedDate.getTime()) ? 'text' : 'date',
                };
            }

            return {
                value: raw == null ? '' : String(raw),
                type: 'text',
            };
        }

        if (typeof cellData === 'number') {
            return {
                value: cellData,
                type: 'number',
            };
        }

        return {
            value: cellData == null ? '' : String(cellData),
            type: 'text',
        };
    }

    function applyExcelCellBorder(cell, color) {
        cell.border = {
            top: { style: 'thin', color: { argb: color } },
            left: { style: 'thin', color: { argb: color } },
            bottom: { style: 'thin', color: { argb: color } },
            right: { style: 'thin', color: { argb: color } },
        };
    }

    async function exportExcel() {
        const exportButton = document.getElementById('export-excel');
        const serverExportUrl = exportButton?.dataset.serverExportUrl;
        if (serverExportUrl) {
            const form = document.getElementById('filter-form');
            const query = form ? new URLSearchParams(new FormData(form)).toString() : '';
            window.location.href = query ? serverExportUrl + '?' + query : serverExportUrl;
            return;
        }

        const table = document.getElementById('main_table');
        if (!table || !window.mainTable || typeof ExcelJS === 'undefined') return;
        if (isExporting) {
            window.showToast?.('Export sedang diproses, mohon tunggu.', 'info');
            return;
        }
        isExporting = true;
        setExportButtonsLoading(true);
        try {
            const filters = collectAppliedFilters();
            const filenameBase = resolveExportFilename(table);

            let fetched;
            try {
                fetched = await fetchFilteredRows(table);
            } catch (error) {
                window.showToast?.('Gagal memuat data export dari server.', 'error');
                return;
            }

            const workbook = new ExcelJS.Workbook();
            const sheet = workbook.addWorksheet('Export');
            const headers = [];
            table.querySelectorAll('thead th').forEach(function (th) { headers.push(th.textContent.trim()); });
            var columnOptions = readColumnOptions(table, headers);
            var exportIndexes = headers
                .map(function (_, index) { return index; })
                .filter(function (index) { return columnOptions[index]?.exportable; });
            var exportHeaders = exportIndexes.map(function (index) { return headers[index]; });
            if (filters.length) {
                sheet.addRow(['Filter aktif']);
                filters.forEach(function (item) { sheet.addRow([item.label, item.value]); });
                sheet.addRow([]);
            }

            var headerRow = sheet.addRow(exportHeaders);
            headerRow.eachCell(function (cell) {
                cell.font = { bold: true };
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFE3EBE0' },
                };
                applyExcelCellBorder(cell, 'FF436137');
            });

            fetched.rows.forEach(function (rowData) {
                if (!Array.isArray(rowData)) return;

                const rowValues = [];
                const rowTypes = [];
                exportIndexes.forEach(function (index) {
                    var cellData = rowData[index];
                    var extracted = extractValueForExport(cellData);
                    rowValues.push(extracted.value);
                    rowTypes.push(extracted.type);
                });
                if (!rowValues.length) return;

                var excelRow = sheet.addRow(rowValues);
                rowTypes.forEach(function (type, index) {
                    var cell = excelRow.getCell(index + 1);
                    if (type === 'number') {
                        cell.numFmt = '#,##0';
                    } else if (type === 'date' && cell.value instanceof Date) {
                        cell.numFmt = 'dd/mm/yyyy hh:mm';
                    }
                    applyExcelCellBorder(cell, 'FF9CB38F');
                });
            });
            exportHeaders.forEach(function (_, i) { sheet.getColumn(i + 1).width = 20; });
            const buffer = await workbook.xlsx.writeBuffer();
            downloadBlob(new Blob([buffer]), filenameBase + '.xlsx');
            if (fetched.truncated) {
                window.showToast?.('Excel diekspor (dibatasi 3000 baris).', 'warning');
            } else {
                window.showToast?.('Excel berhasil diekspor.', 'success');
            }
        } catch (error) {
            window.showToast?.('Terjadi kesalahan saat export Excel.', 'error');
        } finally {
            isExporting = false;
            setExportButtonsLoading(false);
        }
    }

    function buildPdfDocumentContent(exportHeaders, rows, filters, truncated) {
        var body = [exportHeaders.map(function (h) { return { text: h, style: 'tableHeader' }; })];

        rows.forEach(function (rowData) {
            if (!Array.isArray(rowData)) return;
            var row = rowData.map(function (value) {
                return value == null ? '' : String(value);
            });
            if (row.length) body.push(row);
        });

        var content = [{ text: document.title || 'Laporan', style: 'header', margin: [0, 0, 0, 10] }];
        if (filters.length) {
            var filterBody = [];
            filters.forEach(function (item) {
                filterBody.push([item.label, item.value]);
            });

            content.push({
                table: {
                    headerRows: 0,
                    widths: ['35%', '*'],
                    body: filterBody,
                },
                layout: 'lightHorizontalLines',
                margin: [0, 0, 0, 8],
            });
        }
        if (truncated) {
            content.push({
                text: 'Catatan: Export dibatasi maksimal ' + EXPORT_MAX_ROWS + ' baris.',
                margin: [0, 0, 0, 8],
                fontSize: 8,
            });
        }
        content.push({
            table: {
                headerRows: 1,
                widths: exportHeaders.map(function () { return '*'; }),
                body: body,
            },
            layout: {
                hLineWidth: function () { return 0.5; },
                vLineWidth: function () { return 0.5; },
                hLineColor: function () { return '#c6d3c0'; },
                vLineColor: function () { return '#c6d3c0'; },
            },
        });

        var defaultFontSize = exportHeaders.length > 10 ? 7 : 9;

        return {
            pageOrientation: 'landscape',
            pageMargins: exportHeaders.length > 10 ? [18, 24, 18, 24] : [24, 32, 24, 32],
            content: content,
            styles: { header: { fontSize: 16, bold: true }, tableHeader: { bold: true, fillColor: '#d0f0e1' } },
            defaultStyle: { fontSize: defaultFontSize },
        };
    }

    async function exportPdf() {
        const table = document.getElementById('main_table');
        if (!table || !window.mainTable || typeof pdfMake === 'undefined') return;
        if (isExporting) {
            window.showToast?.('Export sedang diproses, mohon tunggu.', 'info');
            return;
        }
        isExporting = true;
        setExportButtonsLoading(true);
        try {
            const filters = collectAppliedFilters();
            const filenameBase = resolveExportFilename(table);

            let fetched;
            try {
                fetched = await fetchFilteredRows(table);
            } catch (error) {
                window.showToast?.('Gagal memuat data export dari server.', 'error');
                return;
            }

            const headers = [];
            table.querySelectorAll('thead th').forEach(function (th) { headers.push(th.textContent.trim()); });
            var columnOptions = readColumnOptions(table, headers);
            var exportIndexes = headers
                .map(function (_, index) { return index; })
                .filter(function (index) { return columnOptions[index]?.exportable; });
            var exportHeaders = exportIndexes.map(function (index) { return headers[index]; });
            pdfMake.createPdf(
                buildPdfDocumentContent(exportHeaders, fetched.rows.map(function (rowData) {
                    if (!Array.isArray(rowData)) return [];
                    return exportIndexes.map(function (index) {
                        var extracted = extractValueForExport(rowData[index]);
                        if (extracted.value instanceof Date) {
                            var d = String(extracted.value.getDate()).padStart(2, '0');
                            var m = String(extracted.value.getMonth() + 1).padStart(2, '0');
                            var y = extracted.value.getFullYear();
                            var formattedDate = d + '/' + m + '/' + y;
                            if (extracted.type === 'datetime') {
                                var hh = String(extracted.value.getHours()).padStart(2, '0');
                                var mm = String(extracted.value.getMinutes()).padStart(2, '0');
                                formattedDate += ' ' + hh + ':' + mm;
                            }
                            return formattedDate;
                        }
                        return extracted.value == null ? '' : String(extracted.value);
                    });
                }), filters, fetched.truncated)
            ).download(filenameBase + '.pdf');
            if (fetched.truncated) {
                window.showToast?.('PDF diekspor (dibatasi 3000 baris).', 'warning');
            } else {
                window.showToast?.('PDF berhasil diekspor.', 'success');
            }
        } catch (error) {
            window.showToast?.('Terjadi kesalahan saat export PDF.', 'error');
        } finally {
            isExporting = false;
            setExportButtonsLoading(false);
        }
    }

    window.initExportButtons = function () {
        document.getElementById('export-excel')?.addEventListener('click', exportExcel);
        document.getElementById('export-pdf')?.addEventListener('click', exportPdf);
    };
})();
