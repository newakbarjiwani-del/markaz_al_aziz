(function () {
    var STATUS_LABELS = {
        0: { text: 'Tidak dapat disimpan', className: 'import-preview-badge import-preview-badge--invalid' },
        1: { text: 'Akan ditambahkan', className: 'import-preview-badge import-preview-badge--create' },
        2: { text: 'Akan diperbarui', className: 'import-preview-badge import-preview-badge--update' },
    };

    var DEFAULT_PAGE_SIZE = 25;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function getFormSekolahId(uploadForm) {
        var select = uploadForm.querySelector('[name="sekolah_id"]');
        return select ? select.value : '';
    }

    function syncHiddenSekolah(uploadForm, confirmForm) {
        var hidden = confirmForm?.querySelector('[data-spreadsheet-import-sekolah-hidden]');
        if (!hidden) return;
        hidden.value = getFormSekolahId(uploadForm);
    }

    function renderSummary(container, summary) {
        if (!container || !summary) return;

        var items = [
            { label: 'Total baris', value: summary.total || 0 },
            { label: 'Akan ditambahkan', value: summary.will_create || 0 },
            { label: 'Akan diperbarui', value: summary.will_update || 0 },
            { label: 'Tidak valid', value: summary.invalid || 0 },
        ];

        container.innerHTML = items.map(function (item) {
            return '<div class="import-preview-stat"><p class="import-preview-stat__label">' + item.label + '</p><p class="import-preview-stat__value">' + item.value + '</p></div>';
        }).join('');
    }

    function buildRowHtml(row, importType) {
        var status = STATUS_LABELS[row.status] || STATUS_LABELS[0];
        var display = row.display || {};

        if (importType === 'guru') {
            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.nip || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.nama || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.jabatan || '-') + '</td>'
                + '<td class="px-4 py-3"><span class="' + status.className + '">' + status.text + '</span></td>'
                + '<td class="px-4 py-3 text-slate-500">' + (row.description || '-') + '</td>'
                + '</tr>';
        }

        if (importType === 'buku') {
            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.kode_buku || '-') + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.isbn || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.judul || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.pengarang || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.penerbit || '-') + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.jumlah || '0') + '</td>'
                + '<td class="px-4 py-3"><span class="' + status.className + '">' + status.text + '</span></td>'
                + '<td class="px-4 py-3 text-slate-500">' + (row.description || '-') + '</td>'
                + '</tr>';
        }

        if (importType === 'tagihan') {
            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.nis || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.nama || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.jenis || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.periode || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.tahun_akademik || '-') + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.tagihan || '-') + '</td>'
                + '<td class="px-4 py-3"><span class="' + status.className + '">' + status.text + '</span></td>'
                + '<td class="px-4 py-3 text-slate-500">' + (row.description || '-') + '</td>'
                + '</tr>';
        }

        if (importType === 'prestasi' || importType === 'pelanggaran') {
            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.nis || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.nama || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.kelas || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.judul || '-') + '</td>'
                + '<td class="px-4 py-3">' + (display.tanggal || '-') + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + (display.point || '-') + '</td>'
                + '<td class="px-4 py-3"><span class="' + status.className + '">' + status.text + '</span></td>'
                + '<td class="px-4 py-3 text-slate-500">' + (row.description || '-') + '</td>'
                + '</tr>';
        }

        return '<tr>'
            + '<td class="px-4 py-3">' + row.row_number + '</td>'
            + '<td class="px-4 py-3 font-mono text-xs">' + (display.nis || '-') + '</td>'
            + '<td class="px-4 py-3">' + (display.nama || '-') + '</td>'
            + '<td class="px-4 py-3">' + (display.kelas || '-') + '</td>'
            + '<td class="px-4 py-3"><span class="' + status.className + '">' + status.text + '</span></td>'
            + '<td class="px-4 py-3 text-slate-500">' + (row.description || '-') + '</td>'
            + '</tr>';
    }

    function createPreviewTableState(panel) {
        return {
            rows: [],
            importType: 'siswa',
            page: 1,
            pageSize: DEFAULT_PAGE_SIZE,
            panel: panel,
        };
    }

    function totalPages(state) {
        if (!state.rows.length) return 1;
        return Math.ceil(state.rows.length / state.pageSize);
    }

    function renderPreviewTable(state) {
        var tbody = state.panel.querySelector('[data-spreadsheet-import-rows]');
        var pagination = state.panel.querySelector('[data-spreadsheet-import-pagination]');
        var pageInfo = state.panel.querySelector('[data-spreadsheet-import-page-info]');
        var prevBtn = state.panel.querySelector('[data-spreadsheet-import-prev]');
        var nextBtn = state.panel.querySelector('[data-spreadsheet-import-next]');
        var pageSizeSelect = state.panel.querySelector('[data-spreadsheet-import-page-size]');
        var colCount = state.panel.querySelectorAll('thead th').length || 6;

        if (!tbody) return;

        if (!state.rows.length) {
            tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="px-4 py-6 text-center text-slate-500">Tidak ada data pratinjau.</td></tr>';
            pagination?.classList.add('hidden');
            return;
        }

        var pages = totalPages(state);
        if (state.page > pages) state.page = pages;
        if (state.page < 1) state.page = 1;

        var start = (state.page - 1) * state.pageSize;
        var pageRows = state.rows.slice(start, start + state.pageSize);

        tbody.innerHTML = pageRows.map(function (row) {
            return buildRowHtml(row, state.importType);
        }).join('');

        if (pagination) {
            pagination.classList.remove('hidden');
        }

        if (pageInfo) {
            var from = start + 1;
            var to = start + pageRows.length;
            pageInfo.textContent = 'Menampilkan ' + from + '–' + to + ' dari ' + state.rows.length + ' baris · Halaman ' + state.page + ' / ' + pages;
        }

        if (prevBtn) prevBtn.disabled = state.page <= 1;
        if (nextBtn) nextBtn.disabled = state.page >= pages;

        if (pageSizeSelect && String(pageSizeSelect.value) !== String(state.pageSize)) {
            pageSizeSelect.value = String(state.pageSize);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDateTimeLocal(value) {
        if (!value) return '-';

        var date = new Date(value);
        if (isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(date).replace(/\./g, ':');
    }

    function useSiswaImportReview(panel, importType) {
        return importType === 'siswa' && panel?.dataset.siswaImportReview === '1';
    }

    function buildSiswaCreateRows(rows) {
        if (!rows.length) {
            return '<tr><td colspan="6" class="px-4 py-4 text-center text-slate-500">Tidak ada data baru.</td></tr>';
        }

        return rows.map(function (row) {
            var display = row.display || {};
            var after = row.comparison?.after || {};

            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + escapeHtml(display.nis || '-') + '</td>'
                + '<td class="px-4 py-3">' + escapeHtml(display.nama || '-') + '</td>'
                + '<td class="px-4 py-3">' + escapeHtml(display.kelas || after.kelas || '-') + '</td>'
                + '<td class="px-4 py-3">' + escapeHtml(after.birth_place || '-') + '</td>'
                + '<td class="px-4 py-3">' + escapeHtml(after.birth_date || display.tanggal_lahir || '-') + '</td>'
                + '</tr>';
        }).join('');
    }

    function buildSiswaInvalidRows(rows) {
        if (!rows.length) {
            return '<tr><td colspan="4" class="px-4 py-4 text-center text-slate-500">Tidak ada data gagal.</td></tr>';
        }

        return rows.map(function (row) {
            var display = row.display || {};

            return '<tr>'
                + '<td class="px-4 py-3">' + row.row_number + '</td>'
                + '<td class="px-4 py-3 font-mono text-xs">' + escapeHtml(display.nis || '-') + '</td>'
                + '<td class="px-4 py-3">' + escapeHtml(display.nama || '-') + '</td>'
                + '<td class="px-4 py-3 text-rose-700 dark:text-rose-300">' + escapeHtml(row.description || '-') + '</td>'
                + '</tr>';
        }).join('');
    }

    function buildUpdateDiffRows(row) {
        var before = row.comparison?.before || {};
        var after = row.comparison?.after || {};
        var labels = {
            name: 'Nama',
            kelas: 'Kelas',
            gender: 'Gender',
            birth_place: 'Tempat Lahir',
            birth_date: 'Tanggal Lahir',
            address: 'Alamat',
            status: 'Status',
            wali: 'Wali',
        };

        var changed = Object.keys(labels).map(function (key) {
            var oldValue = before[key] ?? '-';
            var newValue = after[key] ?? '-';
            if (String(oldValue) === String(newValue)) return null;

            return '<tr>'
                + '<td class="px-3 py-2 text-slate-500">' + labels[key] + '</td>'
                + '<td class="px-3 py-2">' + escapeHtml(oldValue) + '</td>'
                + '<td class="px-3 py-2 font-medium text-emerald-700 dark:text-emerald-300">' + escapeHtml(newValue) + '</td>'
                + '</tr>';
        }).filter(Boolean);

        if (!changed.length) {
            changed.push('<tr><td colspan="3" class="px-3 py-2 text-slate-500">Tidak ada perubahan nilai.</td></tr>');
        }

        return changed.join('');
    }

    function buildSiswaUpdateRows(rows) {
        if (!rows.length) {
            return '<div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900/60">Tidak ada data update.</div>';
        }

        return rows.map(function (row) {
            var display = row.display || {};

            return '<article class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/60">'
                + '<div class="mb-3 flex flex-wrap items-center justify-between gap-2">'
                + '<p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Baris ' + row.row_number + ' · ' + escapeHtml(display.nis || '-') + ' · ' + escapeHtml(display.nama || '-') + '</p>'
                + '<span class="' + STATUS_LABELS[2].className + '">' + STATUS_LABELS[2].text + '</span>'
                + '</div>'
                + '<div class="overflow-x-auto rounded-md border border-slate-200 dark:border-slate-700">'
                + '<table class="min-w-full text-sm">'
                + '<thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">'
                + '<tr><th class="px-3 py-2 font-medium">Field</th><th class="px-3 py-2 font-medium">Sebelum</th><th class="px-3 py-2 font-medium">Sesudah</th></tr>'
                + '</thead>'
                + '<tbody class="divide-y divide-slate-200 dark:divide-slate-800">'
                + buildUpdateDiffRows(row)
                + '</tbody>'
                + '</table>'
                + '</div>'
                + '</article>';
        }).join('');
    }

    function renderSiswaImportReview(panel, rows, importType) {
        var siswaReview = panel?.querySelector('[data-siswa-import-review]');
        var genericReview = panel?.querySelector('[data-generic-import-review]');
        if (!siswaReview || !genericReview) return;

        if (!useSiswaImportReview(panel, importType)) {
            siswaReview.classList.add('hidden');
            genericReview.classList.remove('hidden');
            return;
        }

        siswaReview.classList.remove('hidden');
        genericReview.classList.add('hidden');

        var createRows = rows.filter(function (row) { return Number(row.status) === 1; });
        var updateRows = rows.filter(function (row) { return Number(row.status) === 2; });
        var invalidRows = rows.filter(function (row) { return Number(row.status) === 0; });

        var createBody = panel.querySelector('[data-siswa-import-create-rows]');
        var updateContainer = panel.querySelector('[data-siswa-import-update-rows]');
        var invalidBody = panel.querySelector('[data-siswa-import-invalid-rows]');

        if (createBody) createBody.innerHTML = buildSiswaCreateRows(createRows);
        if (updateContainer) updateContainer.innerHTML = buildSiswaUpdateRows(updateRows);
        if (invalidBody) invalidBody.innerHTML = buildSiswaInvalidRows(invalidRows);
    }

    function renderPreview(panel, preview, importType, tableState) {
        if (!panel || !preview) return;

        panel.classList.remove('hidden');

        var meta = panel.querySelector('[data-spreadsheet-import-meta]');
        if (meta) {
            meta.textContent = 'File: ' + (preview.file_name || '-') + ' · Sekolah: ' + (preview.sekolah_name || '-') + ' · Data sementara hingga ' + formatDateTimeLocal(preview.expires_at);
        }

        renderSummary(panel.querySelector('[data-spreadsheet-import-summary]'), preview.summary);

        tableState.rows = preview.rows || [];
        tableState.importType = importType;
        tableState.page = 1;
        renderSiswaImportReview(panel, tableState.rows, importType);
        renderPreviewTable(tableState);

        var confirmBtn = panel.querySelector('[data-spreadsheet-import-confirm]');
        if (confirmBtn) {
            confirmBtn.disabled = !(preview.summary && (preview.summary.will_create + preview.summary.will_update) > 0);
        }
    }

    function clearPreviewPanel(panel, tableState) {
        if (!panel) return;
        panel.classList.add('hidden');
        var meta = panel.querySelector('[data-spreadsheet-import-meta]');
        if (meta) meta.textContent = 'Belum ada pratinjau.';
        renderSummary(panel.querySelector('[data-spreadsheet-import-summary]'), { total: 0, will_create: 0, will_update: 0, invalid: 0 });
        tableState.rows = [];
        tableState.page = 1;
        renderSiswaImportReview(panel, [], tableState.importType);
        renderPreviewTable(tableState);
        var confirmBtn = panel.querySelector('[data-spreadsheet-import-confirm]');
        if (confirmBtn) confirmBtn.disabled = true;
    }

    async function requestJson(url, options) {
        var response = await fetch(url, options);
        var data = await response.json();

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Permintaan gagal.');
        }

        return data;
    }

    function bindImportPage(uploadForm) {
        var panel = document.querySelector('[data-spreadsheet-import-preview-panel]');
        var confirmForm = panel?.querySelector('[data-spreadsheet-import-confirm-form]');
        var importType = uploadForm.dataset.importType || 'siswa';
        var previewRoute = uploadForm.dataset.previewRoute;
        var confirmRoute = uploadForm.dataset.confirmRoute;
        var clearRoute = uploadForm.dataset.clearPreviewRoute;
        var cachedRoute = uploadForm.dataset.cachedPreviewRoute;
        var tableState = createPreviewTableState(panel);

        if (!previewRoute || !confirmForm) return;

        panel.querySelector('[data-spreadsheet-import-prev]')?.addEventListener('click', function () {
            if (tableState.page <= 1) return;
            tableState.page -= 1;
            renderPreviewTable(tableState);
        });

        panel.querySelector('[data-spreadsheet-import-next]')?.addEventListener('click', function () {
            if (tableState.page >= totalPages(tableState)) return;
            tableState.page += 1;
            renderPreviewTable(tableState);
        });

        panel.querySelector('[data-spreadsheet-import-page-size]')?.addEventListener('change', function (event) {
            tableState.pageSize = parseInt(event.target.value, 10) || DEFAULT_PAGE_SIZE;
            tableState.page = 1;
            renderPreviewTable(tableState);
        });

        uploadForm.querySelector('[data-spreadsheet-import-preview]')?.addEventListener('click', async function () {
            if (!(await window.validateFormWithDialog?.(uploadForm))) {
                return;
            }

            var file = window.getSpreadsheetImportFile?.(uploadForm);
            if (!file) {
                window.showToast?.('Pilih file Excel terlebih dahulu.', 'warning');
                return;
            }

            var formData = new FormData();
            formData.append('_token', csrfToken());
            formData.append('file', file, file.name);

            var sekolahId = getFormSekolahId(uploadForm);
            if (sekolahId) {
                formData.append('sekolah_id', sekolahId);
            }
            syncHiddenSekolah(uploadForm, confirmForm);

            var button = uploadForm.querySelector('[data-spreadsheet-import-preview]');
            var original = button?.innerHTML;
            if (button) {
                button.disabled = true;
                button.innerHTML = 'Memproses pratinjau...';
            }

            try {
                var data = await requestJson(previewRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                renderPreview(panel, data.data?.preview, importType, tableState);
                window.showToast?.(data.message || 'Pratinjau siap.', 'success');
            } catch (error) {
                window.showToast?.(error.message, 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = original;
                }
            }
        });

        confirmForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!(await window.validateFormWithDialog?.(confirmForm))) {
                return;
            }

            var summary = tableState.rows.reduce(function (acc, row) {
                if (Number(row.status) === 1) acc.create++;
                if (Number(row.status) === 2) acc.update++;
                if (Number(row.status) === 0) acc.invalid++;
                return acc;
            }, { create: 0, update: 0, invalid: 0 });

            var proceed = await window.showConfirm?.({
                title: 'Approve Import',
                message: 'Proses import sekarang?',
                detail: summary.create + ' data baru, ' + summary.update + ' data diupdate, ' + summary.invalid + ' data gagal (dilewati).',
                confirmText: 'Approve',
                tone: 'primary',
            });

            if (!proceed) return;

            syncHiddenSekolah(uploadForm, confirmForm);

            var formData = new FormData(confirmForm);
            var button = confirmForm.querySelector('[data-spreadsheet-import-confirm]');
            var original = button?.innerHTML;

            if (button) {
                button.disabled = true;
                button.innerHTML = 'Menyimpan...';
            }

            try {
                var data = await requestJson(confirmRoute, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                clearPreviewPanel(panel, tableState);
                window.resetSpreadsheetImportUpload?.(uploadForm);
                window.showToast?.(data.message || 'Import selesai.', 'success');
            } catch (error) {
                window.showToast?.(error.message, 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = original;
                }
            }
        });

        panel.querySelector('[data-spreadsheet-import-clear]')?.addEventListener('click', async function () {
            var proceed = await window.showConfirm?.({
                title: 'Hapus Data Import Sementara',
                message: 'Data import sementara di server akan dihapus. File Excel tidak ikut terhapus.',
                confirmText: 'Ya, Hapus',
                tone: 'warning',
                confirmIcon: 'ti-trash',
                headerIcon: 'ti-file-off',
                footnote: 'Anda dapat mengunggah ulang file untuk membuat pratinjau baru.',
            });

            if (!proceed) return;

            try {
                await requestJson(clearRoute, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                clearPreviewPanel(panel, tableState);
                window.resetSpreadsheetImportUpload?.(uploadForm);
                window.showToast?.('Data import sementara dihapus.', 'success');
            } catch (error) {
                window.showToast?.(error.message, 'error');
            }
        });

        uploadForm.querySelector('[name="sekolah_id"]')?.addEventListener('change', function () {
            syncHiddenSekolah(uploadForm, confirmForm);
        });

        if (cachedRoute) {
            requestJson(cachedRoute, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(function (data) {
                if (data.data?.preview) {
                    renderPreview(panel, data.data.preview, importType, tableState);
                    syncHiddenSekolah(uploadForm, confirmForm);
                }
            }).catch(function () {
                // ignore missing cache on load
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-spreadsheet-import-form]').forEach(bindImportPage);
    });
})();
