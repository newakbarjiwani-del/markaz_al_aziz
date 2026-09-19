(function () {
    function htmlEscape(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var ID_DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var ID_MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    function parseDateValue(value) {
        if (value == null || value === '' || value === '-') {
            return null;
        }

        if (value instanceof Date) {
            return isNaN(value.getTime()) ? null : value;
        }

        var text = String(value).trim();
        if (!text) {
            return null;
        }

        // Prefer ISO / "Y-m-d H:i:s" from backend.
        var normalized = text.indexOf('T') === -1 && /^\d{4}-\d{2}-\d{2}/.test(text)
            ? text.replace(' ', 'T')
            : text;
        var parsed = new Date(normalized);

        return isNaN(parsed.getTime()) ? null : parsed;
    }

    /**
     * Indonesian display: "Senin, 6 Juli 2026" or with time "Senin, 6 Juli 2026 11:00".
     */
    function formatIndonesianDate(value, withTime) {
        var date = parseDateValue(value);
        if (!date) {
            return value == null || value === '' ? '-' : String(value);
        }

        var out = ID_DAYS[date.getDay()]
            + ', '
            + date.getDate()
            + ' '
            + ID_MONTHS[date.getMonth()]
            + ' '
            + date.getFullYear();

        if (withTime) {
            out += ' '
                + String(date.getHours()).padStart(2, '0')
                + ':'
                + String(date.getMinutes()).padStart(2, '0');
        }

        return out;
    }

    function formatStructuredDateDisplay(data) {
        if (!data || typeof data !== 'object') {
            return null;
        }

        if (data.type === 'datetime') {
            return formatIndonesianDate(data.raw != null ? data.raw : data.display, true);
        }

        if (data.type === 'date') {
            return formatIndonesianDate(data.raw != null ? data.raw : data.display, false);
        }

        return null;
    }

    function readColumnTitles(table) {
        return Array.from(table.querySelectorAll('thead th'))
            .map(function (th) { return th.textContent.trim(); });
    }

    function buildColumns(titles, columnOptions) {
        return titles.map(function (title, index) {
            var options = columnOptions[index] || {};
            var isAction = title === 'Aksi';
            var isHtml = !!options.html;
            var column = {
                data: index,
                title: title,
                orderable: options.orderable !== undefined ? !!options.orderable : (!isAction && !isHtml),
                searchable: options.searchable !== undefined ? !!options.searchable : !isAction,
                defaultContent: '',
            };

            if (options.className) {
                column.className = options.className;
            }

            function resolveStructuredCell(data, type) {
                if (!data || typeof data !== 'object' || !('display' in data) || !('raw' in data)) {
                    return null;
                }

                if (data.type === 'student-list') {
                    if (type === 'display' || type === 'filter') {
                        try {
                            var parsed = JSON.parse(data.display);
                            var list = (parsed.students || []).map(function (s) {
                                var text = htmlEscape(s.name);
                                if (s.kelas) text += ' (' + htmlEscape(s.kelas) + ')';
                                return text;
                            });
                            var html = list.length
                                ? list.map(function (t) { return '• ' + t; }).join('<br>')
                                : '-';
                            if (parsed.more > 0) {
                                html += '<br><span class="text-xs text-slate-400">• + ' + parsed.more + ' lainnya</span>';
                            }
                            return html;
                        } catch (e) {
                            return data.display;
                        }
                    }
                    return data.raw;
                }

                if (data.type === 'action' && data.raw && data.raw.actions) {
                    if (type !== 'display' && type !== 'filter') {
                        return '';
                    }

                    var edit = data.raw.actions.edit || null;
                    var reset = data.raw.actions.reset || null;
                    var del = data.raw.actions.delete || null;
                    var html = '<div class="action-group">';

                    if (edit) {
                        var recordJson = JSON.stringify(edit.record || {});
                        var record = (typeof window.btoa === 'function')
                            ? window.btoa(unescape(encodeURIComponent(recordJson)))
                            : recordJson
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/'/g, '&#039;');
                        var editAttrs = '<button type="button"'
                            + ' data-edit-record="' + record + '"'
                            + ' data-update-url="' + (edit.update_url || '') + '"'
                            + ' data-form-target="' + (edit.form_target || '') + '"'
                            + ' data-modal-target="' + (edit.modal_target || '') + '"'
                            + ' data-modal-title="' + (edit.modal_title || '') + '"';
                        if (edit.partial_edit) {
                            editAttrs += ' data-partial-edit="1"';
                        }
                        if (edit.edit_self) {
                            editAttrs += ' data-edit-self="1"';
                        }
                        if (edit.can_change_password === false) {
                            editAttrs += ' data-can-change-password="0"';
                        }
                        if (edit.editable_fields) {
                            editAttrs += ' data-editable-fields="' + edit.editable_fields + '"';
                        }
                        html += editAttrs
                            + ' class="btn-action btn-action-edit" title="Edit">'
                            + '<i class="ti ti-pencil"></i>'
                            + '<span class="btn-action-label">Edit</span></button>';
                    }

                    if (reset) {
                        var resetDetailVal = reset.confirm_detail;
                        var resetDetailAttr = '';
                        if (Array.isArray(resetDetailVal)) {
                            resetDetailAttr = JSON.stringify(resetDetailVal)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                        } else if (resetDetailVal) {
                            resetDetailAttr = String(resetDetailVal)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/'/g, '&#039;');
                        }

                        html += '<button type="button"'
                            + ' data-fetch-post="' + (reset.url || '') + '"'
                            + ' data-confirm-title="' + (reset.confirm_title || 'Konfirmasi') + '"'
                            + ' data-confirm-message="' + (reset.confirm_message || 'Yakin melanjutkan?') + '"'
                            + ' data-confirm-detail="' + resetDetailAttr + '"'
                            + ' data-confirm-text="' + (reset.confirm_text || 'Ya, Reset Password') + '"'
                            + ' data-confirm-tone="' + (reset.confirm_tone || 'warning') + '"'
                            + ' data-confirm-icon="' + (reset.confirm_icon || 'ti-key') + '"'
                            + ' data-confirm-header-icon="' + (reset.confirm_header_icon || 'ti-key') + '"'
                            + ' data-confirm-footnote="' + (reset.confirm_footnote || 'Password lama tidak bisa dipakai lagi setelah direset.') + '"'
                            + ' class="btn-action btn-action-reset"'
                            + ' title="Reset Password"'
                            + ' aria-label="Reset Password">'
                            + '<i class="ti ti-key"></i>'
                            + '<span class="btn-action-label">Reset</span></button>';
                    }

                    if (del) {
                        var detailVal = del.confirm_detail;
                        var detailAttr = '';
                        if (Array.isArray(detailVal)) {
                            detailAttr = JSON.stringify(detailVal)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                        } else if (detailVal) {
                            detailAttr = String(detailVal)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/'/g, '&#039;');
                        }

                        html += '<button type="button"'
                            + ' data-fetch-delete="' + (del.url || '') + '"'
                            + ' data-confirm-title="' + (del.confirm_title || 'Konfirmasi Hapus') + '"'
                            + ' data-confirm-message="' + (del.confirm_message || 'Apakah Anda yakin ingin menghapus data ini?') + '"'
                            + ' data-confirm-detail="' + detailAttr + '"'
                            + ' data-confirm-text="' + (del.confirm_text || 'Ya, Hapus') + '"'
                            + ' data-confirm-icon="' + (del.confirm_icon || 'ti-trash') + '"'
                            + ' class="btn-action btn-action-delete" title="Hapus">'
                            + '<i class="ti ti-trash"></i>'
                            + '<span class="btn-action-label">Hapus</span></button>';
                    }

                    var view = data.raw.actions.view || null;
                    if (view) {
                        html += '<button type="button"'
                            + ' data-view-url="' + (view.url || '') + '"'
                            + ' data-view-modal="' + (view.modal_target || '') + '"'
                            + ' class="btn-action btn-action-view" title="Lihat">'
                            + '<i class="ti ti-eye"></i>'
                            + '<span class="btn-action-label">Detail</span></button>';
                    }

                    var cicilanView = data.raw.actions.cicilan_view || null;
                    if (cicilanView) {
                        var cicilanCount = parseInt(cicilanView.count || '0', 10);
                        var cicilanTitle = cicilanCount > 0
                            ? 'Riwayat Cicilan (' + cicilanCount + ' pembayaran)'
                            : 'Riwayat Cicilan';
                        var cicilanBadge = cicilanCount > 0
                            ? '<span class="btn-action-cicilan-count">' + cicilanCount + '</span>'
                            : '';

                        html += '<button type="button"'
                            + ' data-tagihan-cicilan-view="1"'
                            + ' data-cicilan-show-url="' + (cicilanView.show_url || '') + '"'
                            + ' data-cicilan-cancel-url="' + (cicilanView.cancel_url || '') + '"'
                            + ' data-modal-target="' + (cicilanView.modal_target || '') + '"'
                            + ' class="btn-action btn-action-cicilan"'
                            + ' title="' + cicilanTitle + '"'
                            + ' aria-label="' + cicilanTitle + '">'
                            + '<i class="ti ti-history"></i>'
                            + '<span class="btn-action-label">Cicilan</span>'
                            + cicilanBadge
                            + '</button>';
                    }

                    var cicilanEnable = data.raw.actions.cicilan_enable || null;
                    if (cicilanEnable) {
                        var enableDetailAttr = '';
                        if (Array.isArray(cicilanEnable.confirm_detail)) {
                            enableDetailAttr = JSON.stringify(cicilanEnable.confirm_detail)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                        }

                        html += '<button type="button"'
                            + ' data-fetch-post="' + (cicilanEnable.url || '') + '"'
                            + ' data-confirm-title="' + (cicilanEnable.confirm_title || 'Aktifkan Cicilan') + '"'
                            + ' data-confirm-message="' + (cicilanEnable.confirm_message || '') + '"'
                            + ' data-confirm-detail="' + enableDetailAttr + '"'
                            + ' data-confirm-text="' + (cicilanEnable.confirm_text || 'Aktifkan') + '"'
                            + ' data-confirm-tone="' + (cicilanEnable.confirm_tone || 'info') + '"'
                            + ' data-confirm-icon="' + (cicilanEnable.confirm_icon || 'ti-stairs') + '"'
                            + ' class="btn-action btn-action-edit"'
                            + ' title="Aktifkan Cicilan"'
                            + ' aria-label="Aktifkan Cicilan">'
                            + '<i class="ti ti-stairs"></i>'
                            + '<span class="btn-action-label">Cicilan</span></button>';
                    }

                    var potonganDetail = data.raw.actions.potongan_detail || null;
                    if (potonganDetail) {
                        html += '<button type="button"'
                            + ' data-tagihan-potongan-detail="1"'
                            + ' data-show-url="' + (potonganDetail.show_url || '') + '"'
                            + ' class="btn-action btn-action-view"'
                            + ' title="Detail Potongan"'
                            + ' aria-label="Detail Potongan">'
                            + '<i class="ti ti-receipt-discount"></i>'
                            + '<span class="btn-action-label">Potongan</span></button>';
                    }

                    var potonganApply = data.raw.actions.potongan_apply || null;
                    if (potonganApply) {
                        var applyDetailAttr = '';
                        if (Array.isArray(potonganApply.confirm_detail)) {
                            applyDetailAttr = JSON.stringify(potonganApply.confirm_detail)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                        }

                        html += '<button type="button"'
                            + ' data-tagihan-potongan-apply="1"'
                            + ' data-apply-url="' + (potonganApply.apply_url || '') + '"'
                            + ' data-confirm-title="' + (potonganApply.confirm_title || 'Terapkan Potongan') + '"'
                            + ' data-confirm-message="' + (potonganApply.confirm_message || '') + '"'
                            + ' data-confirm-detail="' + applyDetailAttr + '"'
                            + ' data-confirm-text="' + (potonganApply.confirm_text || 'Terapkan') + '"'
                            + ' data-confirm-tone="' + (potonganApply.confirm_tone || 'info') + '"'
                            + ' class="btn-action btn-action-edit"'
                            + ' title="Terapkan Potongan"'
                            + ' aria-label="Terapkan Potongan">'
                            + '<i class="ti ti-percentage"></i>'
                            + '<span class="btn-action-label">Potongan</span></button>';
                    }

                    var loanDetail = data.raw.actions.loan_detail || null;
                    if (loanDetail) {
                        html += '<button type="button"'
                            + ' data-loan-detail="' + (loanDetail.loan_id || '') + '"'
                            + ' class="btn-action btn-action-view"'
                            + ' title="Detail"'
                            + ' aria-label="Detail peminjaman">'
                            + '<i class="ti ti-eye"></i>'
                            + '<span class="btn-action-label">Detail</span></button>';
                    }

                    var loanExtend = data.raw.actions.loan_extend || null;
                    if (loanExtend) {
                        html += '<button type="button"'
                            + ' data-loan-extend="' + (loanExtend.loan_id || '') + '"'
                            + ' data-extend-url="' + (loanExtend.url || '') + '"'
                            + ' data-loan-label="' + (loanExtend.label || '') + '"'
                            + ' data-loan-due="' + (loanExtend.due_date || '') + '"'
                            + ' class="btn-action btn-action--extend"'
                            + ' title="Perpanjang"'
                            + ' aria-label="Perpanjang peminjaman">'
                            + '<i class="ti ti-calendar-plus"></i>'
                            + '<span class="btn-action-label">Perpanjang</span></button>';
                    }

                    var loanReturn = data.raw.actions.loan_return || null;
                    if (loanReturn) {
                        html += '<button type="button"'
                            + ' data-loan-return="' + (loanReturn.loan_id || '') + '"'
                            + ' data-loan-label="' + (loanReturn.label || '') + '"'
                            + ' data-loan-loan="' + (loanReturn.loan_date || '') + '"'
                            + ' data-loan-due="' + (loanReturn.due_date || '') + '"'
                            + ' class="btn-action btn-action--return"'
                            + ' title="Kembalikan"'
                            + ' aria-label="Kembalikan buku">'
                            + '<i class="ti ti-book-upload"></i>'
                            + '<span class="btn-action-label">Kembalikan</span></button>';
                    }

                    var checkin = data.raw.actions.checkin || null;
                    if (checkin) {
                        html += '<button type="button"'
                            + ' data-action="checkin-perizinan"'
                            + ' data-url="' + (checkin.url || '') + '"'
                            + ' data-siswa-name="' + htmlEscape(checkin.siswa_name || 'Santri') + '"'
                            + ' class="btn-action btn-action--return"'
                            + ' title="Konfirmasi Kembali"'
                            + ' aria-label="Konfirmasi santri kembali">'
                            + '<i class="ti ti-door-enter"></i>'
                            + '<span class="btn-action-label">Kembali</span></button>';
                    }

                    html += '</div>';

                    return html;
                }

                if (type === 'display') {
                    var dateDisplay = formatStructuredDateDisplay(data);
                    if (dateDisplay !== null) {
                        return dateDisplay;
                    }

                    return data.display;
                }

                if (type === 'filter') {
                    var dateFilter = formatStructuredDateDisplay(data);
                    if (dateFilter !== null) {
                        return dateFilter;
                    }

                    if (isHtml && typeof data.raw === 'string') {
                        return data.raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    }

                    return data.raw;
                }

                if (type === 'sort' || type === 'type') {
                    if (isHtml && typeof data.raw === 'string') {
                        return data.raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    }

                    return data.raw;
                }

                return data.raw;
            }

            if (isHtml) {
                column.render = function (data, type) {
                    var structured = resolveStructuredCell(data, type);
                    if (structured !== null) return structured;

                    if (type === 'display' || type === 'filter') {
                        return data;
                    }

                    return typeof data === 'string' ? data.replace(/<[^>]*>/g, '').trim() : data;
                };
            } else {
                column.render = function (data, type) {
                    var structured = resolveStructuredCell(data, type);
                    if (structured !== null) return structured;

                    return data;
                };
            }

            return column;
        });
    }

    function getDatatableWrapper(table) {
        if (!table) return null;

        return table.closest('.dt-container')
            || table.closest('.dataTables_wrapper')
            || table.closest('[id$="_wrapper"]');
    }

    function enhanceDatatableControls(wrapper) {
        if (!wrapper) return;

        wrapper.querySelectorAll('.dt-search input, .dataTables_filter input').forEach(function (input) {
            input.classList.add('form-input', 'datatable-search');
            input.setAttribute('placeholder', 'Cari data...');
        });

        wrapper.querySelectorAll('.dt-length select, .dataTables_length select').forEach(function (select) {
            select.classList.add('form-input', 'datatable-length');
        });
    }

    function ensureTableScroll(table) {
        if (!table || !table.parentElement) return null;

        if (table.parentElement.classList.contains('table-scroll')) {
            return table.parentElement;
        }

        var scroll = document.createElement('div');
        scroll.className = 'table-scroll';
        table.parentNode.insertBefore(scroll, table);
        scroll.appendChild(table);

        return scroll;
    }

    function ensureTableScrollArea(tableEl) {
        var wrapper = getDatatableWrapper(tableEl);
        if (!wrapper) {
            return ensureTableScroll(tableEl);
        }

        var layoutTable = wrapper.querySelector('.dt-layout-table');
        if (!layoutTable) {
            return ensureTableScroll(tableEl);
        }

        var existingScroll = layoutTable.closest('.table-scroll');
        if (existingScroll && wrapper.contains(existingScroll)) {
            return existingScroll;
        }

        var scroll = document.createElement('div');
        scroll.className = 'table-scroll';
        layoutTable.parentNode.insertBefore(scroll, layoutTable);
        scroll.appendChild(layoutTable);

        return scroll;
    }

    function getTableScrollElement(tableEl) {
        return tableEl.closest('.table-scroll') || ensureTableScrollArea(tableEl);
    }

    function syncTableScrollOverlaySize(tableEl) {
        var scroll = tableEl && tableEl.closest('.table-scroll');
        if (!scroll) {
            return;
        }

        var table = scroll.querySelector('table.dataTable, table.datatable-main');
        var contentWidth = table
            ? Math.max(table.offsetWidth, table.scrollWidth, scroll.clientWidth)
            : Math.max(scroll.scrollWidth, scroll.clientWidth);
        var contentHeight = table
            ? Math.max(table.offsetHeight, table.scrollHeight, scroll.clientHeight)
            : Math.max(scroll.scrollHeight, scroll.clientHeight);

        scroll.style.setProperty('--table-scroll-content-width', contentWidth + 'px');
        scroll.style.setProperty('--table-scroll-content-height', contentHeight + 'px');
    }

    function getProcessingMarkup() {
        return '<div class="datatable-loading" role="status" aria-live="polite" aria-busy="true">'
            + '<span class="datatable-loading__spinner" aria-hidden="true"></span>'
            + '<span class="datatable-loading__text">Memuat data...</span>'
            + '</div>';
    }

    function enhanceProcessingIndicator(tableEl) {
        var wrapper = getDatatableWrapper(tableEl);
        var scroll = getTableScrollElement(tableEl);
        var processingEl = wrapper && wrapper.querySelector('.dt-processing');

        if (!processingEl || !scroll) return;

        if (processingEl.parentElement !== scroll) {
            scroll.appendChild(processingEl);
        }

        if (processingEl.dataset.enhanced !== '1') {
            processingEl.dataset.enhanced = '1';
            processingEl.innerHTML = getProcessingMarkup();
        }
    }

    function bindProcessingState(tableEl, table) {
        var wrapper = getDatatableWrapper(tableEl);

        function setLoading(processing) {
            if (processing) {
                enhanceProcessingIndicator(tableEl);
                syncTableScrollOverlaySize(tableEl);
            }

            var scroll = tableEl.closest('.table-scroll');
            scroll?.classList.toggle('is-loading', processing);
            wrapper?.classList.toggle('dt-container--busy', processing);
        }

        if (table && typeof table.on === 'function') {
            table.on('processing', function (e, settings, processing) {
                setLoading(processing);
            });
            return;
        }

        if (window.jQuery) {
            window.jQuery(tableEl).on('processing.dt', function (e, settings, processing) {
                setLoading(processing);
            });
        }
    }

    function enhanceDatatableChrome(wrapper) {
        if (!wrapper) return;

        enhanceDatatableControls(wrapper);
    }

    function clearPartialEditLock(form) {
        if (!form) return;

        form.querySelectorAll('.partial-edit-locked').forEach(function (el) {
            el.disabled = false;
            el.readOnly = false;
            el.classList.remove('partial-edit-locked');
        });

        var noticeId = form.dataset.partialEditNotice;
        if (noticeId) {
            document.getElementById(noticeId)?.classList.add('hidden');
        }

        delete form.dataset.partialEditActive;
    }

    function applyPartialEditLock(form, btn) {
        clearPartialEditLock(form);
        if (!btn.dataset.partialEdit) return;

        var editable = (btn.dataset.editableFields || '')
            .split(',')
            .map(function (name) { return name.trim(); })
            .filter(Boolean);

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.name === '_token' || el.type === 'hidden') return;
            if (editable.indexOf(el.name) !== -1) return;

            el.disabled = true;
            el.classList.add('partial-edit-locked');
        });

        var noticeId = form.dataset.partialEditNotice;
        if (noticeId) {
            document.getElementById(noticeId)?.classList.remove('hidden');
        }

        form.dataset.partialEditActive = '1';
    }

    window.clearPartialEditLock = clearPartialEditLock;

    window.parseEditRecord = function (raw) {
        if (!raw) {
            return null;
        }

        var text = String(raw).trim();

        try {
            if (text.charAt(0) === '{' || text.charAt(0) === '[') {
                return JSON.parse(text);
            }
        } catch (e) { /* try base64 below */ }

        try {
            var decoded = window.atob(text);
            return JSON.parse(decoded);
        } catch (e2) {
            return null;
        }
    };

    window.initEditRecordButtons = function (root) {
        (root || document).querySelectorAll('[data-edit-record]').forEach(function (btn) {
            if (btn.dataset.editBound) return;
            btn.dataset.editBound = '1';
            btn.addEventListener('click', function () {
                var form = document.getElementById(btn.dataset.formTarget);
                if (!form) return;
                var record = window.parseEditRecord(btn.dataset.editRecord);
                if (!record) {
                    window.showAlert?.({
                        title: 'Gagal',
                        message: 'Data edit tidak dapat dibaca. Muat ulang halaman lalu coba lagi.',
                        variant: 'danger',
                    });
                    return;
                }
                if (!btn.dataset.updateUrl) {
                    window.showAlert?.({
                        title: 'Gagal',
                        message: 'URL pembaruan tidak ditemukan.',
                        variant: 'danger',
                    });
                    return;
                }
                var deferredAjaxSelects = [];
                var deferredOfflineSelects = [];
                Object.entries(record).forEach(function (entry) {
                    var key = entry[0];
                    var value = entry[1];
                    var input = form.querySelector('[name="' + key + '"][type="checkbox"]')
                        || form.querySelector('[name="' + key + '"]');
                    if (!input) return;
                    if (input.matches('[data-ajax-select]')) {
                        deferredAjaxSelects.push({ input: input, value: value });
                        return;
                    }
                    if (input.matches('[data-s2]')) {
                        deferredOfflineSelects.push({ input: input, value: value });
                        return;
                    }
                    if (input.type === 'checkbox') {
                        input.checked = !!value && value !== '0' && value !== 0;
                    } else if (input.type === 'date' && value) {
                        input.value = String(value).substring(0, 10);
                    } else if (input.type === 'datetime-local' && value) {
                        var parsed = new Date(value);
                        if (!isNaN(parsed.getTime())) {
                            input.value = parsed.toISOString().slice(0, 16);
                        }
                    } else if (input.classList.contains('formattedNumber')) {
                        input.value = window.formatNumberId ? window.formatNumberId(value) : (value ?? '');
                    } else {
                        input.value = value ?? '';
                    }
                });
                form.action = btn.dataset.updateUrl;
                form.dataset.method = 'PUT';
                applyPartialEditLock(form, btn);
                var modal = document.getElementById(btn.dataset.modalTarget);
                modal?.classList.remove('hidden');
                window.applyModalTitle?.(btn.dataset.modalTarget, 'edit', btn.dataset.modalTitle);
                setTimeout(function () {
                    window.initAjaxSelects?.(modal);
                    window.initOfflineSelect2s?.(modal);
                    deferredOfflineSelects.forEach(function (item) {
                        item.input.value = item.value ?? '';
                        if (window.jQuery) {
                            window.jQuery(item.input).trigger('change');
                        }
                    });
                    deferredAjaxSelects.forEach(function (item) {
                        if (item.value !== null && item.value !== undefined && item.value !== '') {
                            window.setAjaxSelectValue?.(item.input, item.value);
                        }
                    });
                    document.dispatchEvent(new CustomEvent('edit-record-populated', {
                        detail: {
                            form: form,
                            record: record,
                            button: btn,
                            modal: modal,
                        },
                    }));
                }, 0);
            });
        });
    };

    function destroyExistingTable(tableEl) {
        if (!tableEl) return;

        if (typeof DataTable !== 'undefined' && typeof DataTable.isDataTable === 'function' && DataTable.isDataTable(tableEl)) {
            if (typeof DataTable.api === 'function') {
                DataTable.api(tableEl).destroy();
            } else if (typeof DataTable.getInstance === 'function') {
                DataTable.getInstance(tableEl).destroy();
            }
            return;
        }

        var $table = window.jQuery ? window.jQuery(tableEl) : null;
        if ($table && $table.DataTable && $table.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }
    }

    window.ensureTableScrollArea = ensureTableScrollArea;

    window.initDataTables = function () {
        var tableEl = document.getElementById('main_table');
        var ajaxUrl = tableEl && tableEl.getAttribute('data-ajax-url');

        if (!tableEl || !ajaxUrl) {
            return;
        }

        var titles = readColumnTitles(tableEl);
        if (!titles.length) {
            return;
        }

        var extraData = {};
        var extraRaw = tableEl.getAttribute('data-extra-data');
        if (extraRaw) {
            try {
                extraData = JSON.parse(extraRaw);
            } catch (e) {
                extraData = {};
            }
        }

        var columnOptions = [];
        var columnOptionsRaw = tableEl.getAttribute('data-column-options');
        if (columnOptionsRaw) {
            try {
                columnOptions = JSON.parse(columnOptionsRaw);
            } catch (e) {
                columnOptions = [];
            }
        }

        destroyExistingTable(tableEl);

        var defaultOrder = [[0, 'asc']];
        var orderRaw = tableEl.getAttribute('data-default-order');
        if (orderRaw) {
            try {
                defaultOrder = JSON.parse(orderRaw);
            } catch (e) {
                defaultOrder = [[0, 'asc']];
            }
        }

        var thead = tableEl.querySelector('thead');
        if (thead) {
            thead.parentNode.removeChild(thead);
        }

        var options = {
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxUrl,
                data: function (d) {
                    Object.assign(d, extraData);
                    var filterForm = document.getElementById('filter-form');
                    if (filterForm) {
                        new FormData(filterForm).forEach(function (value, key) {
                            d[key] = value;
                        });
                    }
                },
            },
            columns: buildColumns(titles, columnOptions),
            order: defaultOrder,
            pageLength: 25,
            autoWidth: false,
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            language: {
                processing: getProcessingMarkup(),
                search: 'Cari',
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    first: '«',
                    last: '»',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya',
                },
            },
            initComplete: function () {
                ensureTableScrollArea(tableEl);
                enhanceDatatableChrome(getDatatableWrapper(tableEl));
                enhanceProcessingIndicator(tableEl);
                syncTableScrollOverlaySize(tableEl);
            },
            drawCallback: function () {
                enhanceDatatableControls(getDatatableWrapper(tableEl));
                syncTableScrollOverlaySize(tableEl);
                window.initEditRecordButtons?.(tableEl);
            },
        };

        if (typeof DataTable !== 'undefined') {
            window.mainTable = new DataTable(tableEl, options);
            ensureTableScrollArea(tableEl);
            enhanceProcessingIndicator(tableEl);
            bindProcessingState(tableEl, window.mainTable);
        } else if (window.jQuery) {
            window.mainTable = window.jQuery(tableEl).DataTable(options);
            ensureTableScrollArea(tableEl);
            enhanceProcessingIndicator(tableEl);
            bindProcessingState(tableEl, window.mainTable);
        }

        window.initFilterForms?.();
    };

    function reloadMainTable() {
        if (!window.mainTable) return;

        if (typeof window.mainTable.ajax?.reload === 'function') {
            window.mainTable.ajax.reload(null, false);
            return;
        }

        if (window.jQuery && window.jQuery.fn.DataTable && window.mainTable.ajax) {
            window.mainTable.ajax.reload(null, false);
        }
    }

    window.reloadMainTable = reloadMainTable;

    window.initFilterForms = function () {
        document.querySelectorAll('#filter-form, form[data-filter-mode="navigate"]').forEach(function (form) {
            if (form.dataset.filterFormBound) return;
            form.dataset.filterFormBound = '1';

            var mode = form.dataset.filterMode || 'datatable';

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (mode === 'navigate') {
                    var params = new URLSearchParams(new FormData(form));
                    var url = new URL(window.location.href);
                    url.search = params.toString();
                    window.location.href = url.toString();
                    return;
                }

                reloadMainTable();
            });

            form.addEventListener('reset', function () {
                setTimeout(function () {
                    window.resetAjaxSelects?.(form);

                    if (mode === 'navigate') {
                        window.location.href = window.location.pathname;
                        return;
                    }

                    reloadMainTable();
                }, 10);
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initFilterForms?.();
    });
})();
