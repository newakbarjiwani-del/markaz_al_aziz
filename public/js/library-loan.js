(function () {
    var configEl = document.getElementById('library-loan-config');
    if (!configEl) return;

    var config = {};
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch (e) {
        config = {};
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function formatRupiah(amount) {
        var value = Number(amount || 0);
        return 'Rp ' + value.toLocaleString('id-ID');
    }

    function addDays(dateString, days) {
        var date = new Date(dateString + 'T00:00:00');
        if (isNaN(date.getTime())) return '';
        date.setDate(date.getDate() + days);
        return date.toISOString().slice(0, 10);
    }

    function getSelectValue(name) {
        var input = document.querySelector('[name="' + name + '"]');
        return input ? input.value : '';
    }

    async function fetchJson(url, options) {
        var response = await fetch(url, Object.assign({
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }, options || {}));

        var data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Permintaan gagal.');
        }

        return data;
    }

    function renderDetailHtml(loan) {
        var headerRows = [
            ['Tipe', loan.borrower_type_label || '-'],
            ['Peminjam', loan.borrower_label || loan.borrower_name || '-'],
            ['Meta', loan.borrower_meta || '-'],
            ['Tgl Pinjam', formatDateId(loan.loan_date)],
            ['Jatuh Tempo', formatDateId(loan.due_date)],
            ['Status', loan.status_label || '-'],
        ];

        var html = '<dl class="library-loan-detail__list">'
            + headerRows.map(function (row) {
                return '<div class="library-loan-detail__row"><dt>' + row[0] + '</dt><dd>' + row[1] + '</dd></div>';
            }).join('')
            + '</dl>';

        // Catatan pinjam
        if (loan.catatan_pinjam) {
            html += '<div class="library-loan-detail__section"><h4 class="library-loan-detail__subtitle">Catatan Pinjam</h4>'
                + '<p>' + htmlEscape(loan.catatan_pinjam) + '</p></div>';
        }

        // Books table (transaction-level)
        var books = loan.books || [];
        if (books.length > 0) {
            html += '<h4 class="library-loan-detail__subtitle" style="margin-top:1rem">Daftar Buku (' + (loan.total_books || books.length) + ' eksemplar)</h4>'
                + '<table class="library-loan-detail__table"><thead><tr>'
                + '<th>Buku</th><th>Jml</th><th>Status</th><th>Tgl Kembali</th><th>Denda</th><th>Keterangan</th>'
                + '</tr></thead><tbody>';

            books.forEach(function (item) {
                var buku = item.buku || {};
                var fineLabel = item.fine_preview_label || formatRupiah(item.fine_amount || 0);
                var kondisiLabel = item.kondisi_kembali_label || '-';
                html += '<tr>'
                    + '<td><strong>' + htmlEscape(buku.judul || '?') + '</strong>'
                    + (buku.isbn ? '<br><span class="text-xs text-slate-400">ISBN ' + htmlEscape(buku.isbn) + '</span>' : '')
                    + '</td>'
                    + '<td>' + (item.qty > 1 ? item.qty + '×' : '1') + '</td>'
                    + '<td><span class="badge badge-' + (item.status === 'dipinjam' ? (item.is_overdue ? 'red' : 'blue') : 'green') + '">'
                    + htmlEscape(item.status_label || item.status) + '</span></td>'
                    + '<td>' + formatDateId(item.return_date) + '</td>'
                    + '<td>' + fineLabel + '</td>'
                    + '<td>' + (item.catatan_kembali ? htmlEscape(item.catatan_kembali) : kondisiLabel) + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';

            // Fine lines for each book
            books.forEach(function (item) {
                var lines = item.fine_lines || [];
                if (lines.length > 0 && (item.fine_preview?.total_fine || 0) > 0) {
                    html += '<div class="library-loan-detail__fine-block">'
                        + '<strong>' + htmlEscape(item.buku?.judul || 'Buku') + '</strong>';
                    lines.forEach(function (line) {
                        html += '<div><span>' + htmlEscape(line.label) + '</span><strong>' + (line.amount_label || formatRupiah(line.amount || 0)) + '</strong></div>';
                    });
                    html += '</div>';
                }
            });

            if (loan.total_fine > 0) {
                html += '<div class="library-loan-detail__total-fine">'
                    + 'Total Denda: <strong>' + (loan.total_fine_label || formatRupiah(loan.total_fine)) + '</strong>'
                    + '</div>';
            }
        } else {
            // Fallback: single-book format (legacy / child-level)
            html += '<dl class="library-loan-detail__list">'
                + [
                    ['Jumlah', (loan.qty > 1 ? loan.qty + 'x' : '1 eksemplar')],
                    ['Buku', loan.buku?.judul || '-'],
                    ['ISBN', loan.buku?.isbn || '-'],
                    ['Tgl Kembali', formatDateId(loan.return_date)],
                    ['Perpanjangan', (loan.perpanjangan_count || 0) + 'x'],
                    ['Kondisi Kembali', loan.kondisi_kembali_label || '-'],
                    ['Denda', loan.fine_amount_label || formatRupiah(0)],
                    ['Catatan Kembali', loan.catatan_kembali || '-'],
                    ['Petugas', loan.processed_by || '-'],
                ].map(function (row) {
                    return '<div class="library-loan-detail__row"><dt>' + row[0] + '</dt><dd>' + row[1] + '</dd></div>';
                }).join('')
                + '</dl>';
        }

        return html;
    }

    function htmlEscape(str) {
        if (typeof str !== 'string') return str;
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    async function openLoanDetail(loanId) {
        var modal = document.getElementById('loan-detail-modal');
        var content = document.getElementById('loan-detail-content');
        if (!modal || !content || !config.detailUrl) return;

        content.innerHTML = '<p class="text-sm text-slate-500">Memuat detail...</p>';
        modal.classList.remove('hidden');

        try {
            var payload = await fetchJson(config.detailUrl + '/' + loanId);
            content.innerHTML = renderDetailHtml(payload.data || {});
        } catch (err) {
            content.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
        }
    }

    async function openExtendModal(button) {
        var loanId = button.getAttribute('data-loan-extend');
        var url = button.getAttribute('data-extend-url');
        var label = button.getAttribute('data-loan-label') || 'Peminjaman';
        var currentDue = button.getAttribute('data-loan-due') || '';
        if (!loanId || !url) return;

        var modal = document.getElementById('loan-extend-modal');
        var form = document.getElementById('loan-extend-form');
        var loanIdInput = document.getElementById('extend_loan_id');
        var labelEl = document.getElementById('extend-loan-label');
        var currentDueEl = document.getElementById('extend-current-due');
        var dueInput = document.getElementById('extend_due_date');

        if (!modal || !form || !dueInput) return;

        if (loanIdInput) loanIdInput.value = loanId;
        if (labelEl) labelEl.textContent = label;
        if (currentDueEl) currentDueEl.textContent = currentDue ? formatDateId(currentDue) : '-';
        dueInput.min = currentDue || '';
        dueInput.value = currentDue ? addDays(currentDue, Number(config.extensionDays || 7)) : '';

        form.dataset.extendUrl = url;
        modal.classList.remove('hidden');
    }

    function formatDateId(dateString) {
        if (!dateString) return '-';
        var parts = dateString.split('-');
        if (parts.length !== 3) return dateString;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function bindExtendModal() {
        var form = document.getElementById('loan-extend-form');
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            var url = form.dataset.extendUrl;
            var dueDate = document.getElementById('extend_due_date')?.value;
            if (!url || !dueDate) return;

            var submitBtn = form.querySelector('[type="submit"]');
            var originalText = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Menyimpan...';
            }

            try {
                var body = new FormData();
                body.append('due_date', dueDate);

                var response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: body,
                });
                var data = await response.json();

                if (!response.ok) {
                    var items = data.errors ? Object.values(data.errors).flat() : [data.message || 'Gagal memperpanjang.'];
                    await window.showAlert?.({
                        title: 'Gagal',
                        message: data.message || 'Tidak dapat memperpanjang peminjaman.',
                        items: items,
                        variant: 'danger',
                    });
                    return;
                }

                document.getElementById('loan-extend-modal')?.classList.add('hidden');
                window.showToast?.(data.message || 'Peminjaman diperpanjang.', 'success');
                window.reloadMainTable?.();
            } catch (err) {
                await window.showAlert?.({
                    title: 'Koneksi Gagal',
                    message: 'Tidak dapat terhubung ke server.',
                    variant: 'danger',
                });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
    }

    function renderFinePreviewHtml(loan) {
        var fine = loan.fine_preview || {};
        var lines = loan.fine_lines || [];

        if (!lines.length && (fine.total_fine || 0) <= 0) {
            return '<p class="library-loan-summary__ok">Tidak ada denda untuk kondisi ini.</p>';
        }

        var linesHtml = lines.map(function (line) {
            return '<div><span>' + line.label + '</span><strong>' + (line.amount_label || formatRupiah(line.amount || 0)) + '</strong></div>';
        }).join('');

        return '<div class="library-loan-preview-fine">'
            + linesHtml
            + '<div class="library-loan-preview-fine__total"><span>Total estimasi</span><strong>'
            + (loan.fine_preview_label || formatRupiah(fine.total_fine || 0))
            + '</strong></div>'
            + '</div>';
    }

    function resolveReturnButton(event) {
        var returnBtn = event.target.closest('[data-loan-return]');
        if (returnBtn) {
            return returnBtn;
        }

        if (event.target.closest('[data-loan-detail]') || event.target.closest('[data-loan-extend]')) {
            return null;
        }

        var row = event.target.closest('.library-return-page tbody tr');
        return row ? row.querySelector('[data-loan-return]') : null;
    }

    function openReturnModal(button) {
        var loanId = button.getAttribute('data-loan-return');
        var modal = document.getElementById('loan-return-modal');
        var form = document.getElementById('loan-return-form');
        var loanIdInput = document.getElementById('return_peminjaman_id');
        var labelEl = document.getElementById('return-loan-label');
        var loanDateEl = document.getElementById('return-loan-date');
        var dueDateEl = document.getElementById('return-loan-due');
        var returnDateInput = document.getElementById('return_date');
        var kondisiSelect = document.getElementById('kondisi_kembali');
        var catatanInput = document.getElementById('catatan_kembali');

        if (!loanId || !modal || !form || !loanIdInput) {
            return;
        }

        loanIdInput.value = loanId;
        if (labelEl) labelEl.textContent = button.getAttribute('data-loan-label') || 'Peminjaman';
        if (loanDateEl) loanDateEl.textContent = formatDateId(button.getAttribute('data-loan-loan') || '');
        if (dueDateEl) dueDateEl.textContent = formatDateId(button.getAttribute('data-loan-due') || '');
        if (returnDateInput && !returnDateInput.value) {
            returnDateInput.value = new Date().toISOString().slice(0, 10);
        }
        if (kondisiSelect) kondisiSelect.value = 'baik';
        if (catatanInput) catatanInput.value = '';

        modal.classList.remove('hidden');
        if (typeof window.loadReturnPreview === 'function') {
            window.loadReturnPreview();
        }
    }

    async function extendLoan(button) {
        openExtendModal(button);
    }

    function bindTableActions() {
        document.addEventListener('click', function (event) {
            var detailBtn = event.target.closest('[data-loan-detail]');
            if (detailBtn) {
                event.preventDefault();
                openLoanDetail(detailBtn.getAttribute('data-loan-detail'));
                return;
            }

            var extendBtn = event.target.closest('[data-loan-extend]');
            if (extendBtn) {
                event.preventDefault();
                extendLoan(extendBtn);
                return;
            }

            var returnBtn = resolveReturnButton(event);
            if (returnBtn) {
                event.preventDefault();
                openReturnModal(returnBtn);
            }
        });
    }

    function initBorrowPage() {
        var loanDateInput = document.getElementById('loan_date');
        var dueDateInput = document.getElementById('due_date');
        var borrowerTypeInput = document.getElementById('loan_borrower_type');
        var siswaSelect = document.querySelector('[name="siswa_id"]');
        var guruSelect = document.querySelector('[name="guru_id"]');
        var tamuNama = document.getElementById('tamu_nama');
        var tamuAsal = document.getElementById('tamu_asal');
        var tamuTelepon = document.getElementById('tamu_telepon');
        var summaryPanel = document.getElementById('loan-borrower-summary')
            || document.getElementById('siswa-loan-summary');
        var methodWrap = document.querySelector('[data-loan-method-wrap]');
        var borrowerButtons = document.querySelectorAll('[data-loan-borrower]');
        var methodButtons = document.querySelectorAll('[data-loan-method]');
        var rfidPanel = document.querySelector('[data-loan-panel="rfid"]');
        var rfidInput = document.getElementById('loan-rfid-input');
        var rfidResolveBtn = document.getElementById('loan-rfid-resolve');
        var rfidStatus = document.getElementById('loan-rfid-status');
        var currentBorrower = 'siswa';
        var currentMethod = 'search';
        var tamuSummaryTimer = null;
        var selectedBooks = [];
        var remainingQuota = Number(config.maxBooks || 3);
        var bukuPicker = document.querySelector('[name="buku_picker"]');
        var bukuAddBtn = document.getElementById('loan-buku-add');
        var bukuList = document.getElementById('loan-buku-list');
        var bukuIdsBox = document.getElementById('loan-buku-ids');
        var borrowForm = document.getElementById('loan-borrow-form');

        function syncDueDate() {
            if (!loanDateInput || !dueDateInput) return;
            dueDateInput.value = addDays(loanDateInput.value, Number(config.loanDays || 7));
        }

        loanDateInput?.addEventListener('change', syncDueDate);
        syncDueDate();

        function syncBookHiddenInputs() {
            if (!bukuIdsBox) return;
            var html = '';
            selectedBooks.forEach(function (book) {
                for (var i = 0; i < book.qty; i++) {
                    html += '<input type="hidden" name="buku_ids[]" value="' + book.id + '">';
                }
            });
            bukuIdsBox.innerHTML = html;
        }

        function renderBookList() {
            if (!bukuList) return;
            if (!selectedBooks.length) {
                bukuList.hidden = true;
                bukuList.innerHTML = '';
                syncBookHiddenInputs();
                return;
            }

            bukuList.hidden = false;
            bukuList.innerHTML = selectedBooks.map(function (book) {
                return '<li class="library-loan-book-list__item" data-book-id="' + book.id + '">'
                    + '<span>' + (book.label || ('Buku #' + book.id)) + ' (' + book.qty + 'x)</span>'
                    + '<button type="button" class="library-loan-book-list__remove" data-loan-book-remove="'
                    + book.id + '" title="Hapus" aria-label="Hapus buku">'
                    + '<i class="ti ti-x"></i></button>'
                    + '</li>';
            }).join('');
            syncBookHiddenInputs();
        }

        function addSelectedBook() {
            if (!bukuPicker) return;
            var id = Number(bukuPicker.value || 0);
            if (!id) {
                window.showToast?.('Pilih buku terlebih dahulu.', 'warning');
                return;
            }

            var qtyEl = document.getElementById('loan-buku-qty');
            var qty = qtyEl ? Number(qtyEl.value || 1) : 1;
            if (isNaN(qty) || qty <= 0) {
                window.showToast?.('Jumlah buku harus minimal 1.', 'warning');
                return;
            }

            var totalQty = selectedBooks.reduce(function (sum, book) { return sum + book.qty; }, 0);
            if (totalQty + qty > remainingQuota) {
                window.showToast?.('Sisa kuota hanya ' + remainingQuota + ' buku (terpilih: ' + totalQty + ').', 'warning');
                return;
            }

            var optionText = '';
            try {
                optionText = window.jQuery?.(bukuPicker).find('option:selected').text() || '';
            } catch (e) {
                optionText = '';
            }
            if (!optionText) {
                var selected = bukuPicker.options?.[bukuPicker.selectedIndex];
                optionText = selected ? selected.text : ('Buku #' + id);
            }

            var cleanLabel = optionText;
            var parts = optionText.split(' — ');
            if (parts.length > 1) {
                cleanLabel = parts.slice(0, parts.length - 1).join(' — ');
            }

            var existingIndex = selectedBooks.findIndex(function (book) { return book.id === id; });
            if (existingIndex !== -1) {
                selectedBooks[existingIndex].qty += qty;
            } else {
                selectedBooks.push({ id: id, label: cleanLabel, qty: qty });
            }

            renderBookList();

            if (qtyEl) qtyEl.value = 1;

            if (typeof window.resetAjaxSelects === 'function') {
                window.resetAjaxSelects(bukuPicker.closest('.ajax-select-field') || bukuPicker.parentElement);
            } else if (window.jQuery) {
                window.jQuery(bukuPicker).val(null).trigger('change');
            } else {
                bukuPicker.value = '';
            }
        }

        function removeSelectedBook(bookId) {
            selectedBooks = selectedBooks.filter(function (book) { return book.id !== Number(bookId); });
            renderBookList();
        }

        bukuAddBtn?.addEventListener('click', addSelectedBook);
        bukuList?.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-loan-book-remove]');
            if (!btn) return;
            removeSelectedBook(btn.getAttribute('data-loan-book-remove'));
        });

        borrowForm?.addEventListener('submit', function (event) {
            if (selectedBooks.length > 0) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            window.showAlert?.({
                title: 'Validasi',
                message: 'Tambahkan minimal satu buku ke daftar pinjaman.',
                variant: 'warning',
            });
        }, true);

        function emptySummary(message) {
            remainingQuota = Number(config.maxBooks || 3);
            if (!summaryPanel) return;
            summaryPanel.innerHTML =
                '<h3 class="mb-3 text-base font-semibold text-slate-900 dark:text-white">Ringkasan Peminjam</h3>'
                + '<div class="library-loan-summary-empty text-sm text-slate-500">'
                + (message || 'Pilih peminjam untuk melihat peminjaman aktif dan sisa kuota.')
                + '</div>';
        }

        function renderSummary(data) {
            if (!summaryPanel) return;
            remainingQuota = Number(data.remaining_quota ?? config.maxBooks ?? 3);
            var totalQty = selectedBooks.reduce(function (sum, book) { return sum + book.qty; }, 0);
            if (totalQty > remainingQuota) {
                var currentSum = 0;
                var filtered = [];
                for (var i = 0; i < selectedBooks.length; i++) {
                    var book = selectedBooks[i];
                    if (currentSum + book.qty <= remainingQuota) {
                        filtered.push(book);
                        currentSum += book.qty;
                    } else {
                        var partialQty = remainingQuota - currentSum;
                        if (partialQty > 0) {
                            book.qty = partialQty;
                            filtered.push(book);
                            currentSum += partialQty;
                        }
                        break;
                    }
                }
                selectedBooks = filtered;
                renderBookList();
                window.showToast?.('Daftar buku dipotong sesuai sisa kuota.', 'warning');
            }

            var loans = (data.active_loans || []).map(function (loan) {
                var badge = loan.is_overdue
                    ? '<span class="badge badge-red">+' + loan.days_late + ' hari</span>'
                    : '<span class="badge badge-green">' + loan.days_remaining + ' hari lagi</span>';
                return '<li><span>' + (loan.buku_judul || 'Buku') + '</span><span>' + formatDateId(loan.due_date) + '</span>' + badge + '</li>';
            }).join('');

            var metaBits = [data.identifier, data.meta].filter(Boolean).join(' · ');

            summaryPanel.innerHTML =
                '<div class="library-loan-summary">'
                + '<div class="library-loan-summary__head">'
                + '<strong>' + (data.name || '-') + '</strong>'
                + '<span>' + (data.borrower_type_label || '') + (metaBits ? ' · ' + metaBits : '') + '</span>'
                + '</div>'
                + '<div class="library-loan-summary__metrics">'
                + '<div><span>Aktif</span><strong>' + data.active_count + '/' + data.max_books + '</strong></div>'
                + '<div><span>Sisa Kuota</span><strong>' + data.remaining_quota + '</strong></div>'
                + '<div><span>Terlambat</span><strong>' + data.overdue_count + '</strong></div>'
                + '</div>'
                + (data.can_borrow
                    ? '<p class="library-loan-summary__ok">Peminjam masih bisa meminjam buku.</p>'
                    : '<p class="library-loan-summary__warn">Kuota peminjaman sudah penuh.</p>')
                + (loans ? '<ul class="library-loan-summary__list">' + loans + '</ul>' : '<p class="text-sm text-slate-500">Belum ada peminjaman aktif.</p>')
                + '</div>';
        }

        async function loadSiswaSummary(siswaId) {
            if (!summaryPanel || !config.siswaSummaryUrl || !siswaId) {
                emptySummary();
                return;
            }
            summaryPanel.innerHTML = '<p class="text-sm text-slate-500">Memuat ringkasan...</p>';
            try {
                var payload = await fetchJson(config.siswaSummaryUrl + '/' + siswaId + '/ringkasan');
                renderSummary(payload.data || {});
            } catch (err) {
                summaryPanel.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
            }
        }

        async function loadGuruSummary(guruId) {
            if (!summaryPanel || !config.guruSummaryUrl || !guruId) {
                emptySummary();
                return;
            }
            summaryPanel.innerHTML = '<p class="text-sm text-slate-500">Memuat ringkasan...</p>';
            try {
                var payload = await fetchJson(config.guruSummaryUrl + '/' + guruId + '/ringkasan');
                renderSummary(payload.data || {});
            } catch (err) {
                summaryPanel.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
            }
        }

        async function loadTamuSummary() {
            if (!summaryPanel || !config.tamuSummaryUrl) {
                emptySummary();
                return;
            }

            var nama = (tamuNama?.value || '').trim();
            if (!nama) {
                emptySummary('Isi nama tamu untuk melihat kuota peminjaman.');
                return;
            }

            summaryPanel.innerHTML = '<p class="text-sm text-slate-500">Memuat ringkasan...</p>';
            try {
                var params = new URLSearchParams({ tamu_nama: nama });
                if ((tamuAsal?.value || '').trim()) params.set('tamu_asal', tamuAsal.value.trim());
                if ((tamuTelepon?.value || '').trim()) params.set('tamu_telepon', tamuTelepon.value.trim());
                var payload = await fetchJson(config.tamuSummaryUrl + '?' + params.toString());
                renderSummary(payload.data || {});
            } catch (err) {
                summaryPanel.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
            }
        }

        function scheduleTamuSummary() {
            clearTimeout(tamuSummaryTimer);
            tamuSummaryTimer = setTimeout(loadTamuSummary, 350);
        }

        function setRfidStatus(message, tone) {
            if (!rfidStatus) return;
            var toneClass = {
                info: 'text-slate-500',
                warning: 'text-amber-600',
                success: 'text-emerald-600',
                error: 'text-red-600',
            }[tone] || 'text-slate-500';
            rfidStatus.textContent = message;
            rfidStatus.className = 'mt-1 text-xs ' + toneClass;
        }

        function setLoanMethod(method) {
            currentMethod = method;
            methodButtons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-loan-method') === method);
            });
            if (rfidPanel) {
                rfidPanel.classList.toggle('hidden', method !== 'rfid' || currentBorrower === 'tamu');
            }
            if (method === 'rfid' && currentBorrower !== 'tamu' && rfidInput) {
                setTimeout(function () { rfidInput.focus(); }, 50);
            }
        }

        function setBorrowerType(type) {
            currentBorrower = type || 'siswa';
            if (borrowerTypeInput) borrowerTypeInput.value = currentBorrower;

            borrowerButtons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-loan-borrower') === currentBorrower);
            });

            document.querySelectorAll('[data-loan-borrower-panel]').forEach(function (panel) {
                var match = panel.getAttribute('data-loan-borrower-panel') === currentBorrower;
                panel.classList.toggle('hidden', !match);
            });

            if (methodWrap) {
                methodWrap.classList.toggle('hidden', currentBorrower === 'tamu');
            }

            if (currentBorrower === 'tamu') {
                setLoanMethod('search');
                if (rfidPanel) rfidPanel.classList.add('hidden');
                scheduleTamuSummary();
            } else {
                setLoanMethod(currentMethod === 'rfid' ? 'rfid' : 'search');
                if (currentBorrower === 'siswa') {
                    if (siswaSelect?.value) loadSiswaSummary(siswaSelect.value);
                    else emptySummary();
                } else if (guruSelect?.value) {
                    loadGuruSummary(guruSelect.value);
                } else {
                    emptySummary();
                }
            }
        }

        borrowerButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setBorrowerType(btn.getAttribute('data-loan-borrower'));
            });
        });

        methodButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setLoanMethod(btn.getAttribute('data-loan-method'));
            });
        });

        siswaSelect?.addEventListener('change', function () {
            if (currentBorrower === 'siswa') loadSiswaSummary(siswaSelect.value);
        });

        guruSelect?.addEventListener('change', function () {
            if (currentBorrower === 'guru') loadGuruSummary(guruSelect.value);
        });

        ['input', 'change', 'blur'].forEach(function (evt) {
            tamuNama?.addEventListener(evt, scheduleTamuSummary);
            tamuAsal?.addEventListener(evt, scheduleTamuSummary);
            tamuTelepon?.addEventListener(evt, scheduleTamuSummary);
        });

        async function resolveRfid() {
            if (!rfidInput || !config.resolveRfidUrl) return;

            var uid = rfidInput.value.trim();
            if (!uid) {
                setRfidStatus('Masukkan / scan UID kartu terlebih dahulu.', 'warning');
                return;
            }

            setRfidStatus('Mencari kartu...', 'info');

            try {
                var body = new FormData();
                body.append('rfid_uid', uid);

                var response = await fetch(config.resolveRfidUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: body,
                });
                var data = await response.json();

                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'Kartu RFID tidak dikenali.');
                }

                var payload = data.data || {};
                if (payload.borrower_type === 'guru' && payload.guru?.id) {
                    setBorrowerType('guru');
                    if (guruSelect) {
                        window.setAjaxSelectValue?.(guruSelect, payload.guru.id, payload.guru.label);
                    }
                    loadGuruSummary(payload.guru.id);
                    setRfidStatus('Terpilih: ' + (payload.guru.label || payload.guru.name || '-'), 'success');
                } else if (payload.siswa?.id) {
                    setBorrowerType('siswa');
                    if (siswaSelect) {
                        window.setAjaxSelectValue?.(siswaSelect, payload.siswa.id, payload.siswa.label);
                    }
                    loadSiswaSummary(payload.siswa.id);
                    setRfidStatus('Terpilih: ' + (payload.siswa.label || payload.siswa.name || '-'), 'success');
                } else {
                    throw new Error('Kartu RFID tidak dikenali.');
                }

                rfidInput.value = '';
            } catch (err) {
                setRfidStatus(err.message, 'error');
                window.showToast?.(err.message, 'error');
            }
        }

        rfidResolveBtn?.addEventListener('click', resolveRfid);
        rfidInput?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                resolveRfid();
            }
        });

        setBorrowerType(borrowerTypeInput?.value || 'siswa');
    }

    function initReturnPage() {
        var form = document.getElementById('loan-return-form');
        var previewPanel = document.getElementById('return-fine-preview');
        var kondisiSelect = document.getElementById('kondisi_kembali');
        var returnDateInput = document.getElementById('return_date');

        async function loadReturnPreview() {
            var loanId = document.getElementById('return_peminjaman_id')?.value;
            if (!previewPanel || !config.previewUrl || !loanId) {
                if (previewPanel) {
                    previewPanel.innerHTML = '<div class="library-loan-summary-empty text-sm text-slate-500">Pilih peminjaman aktif untuk melihat rincian denda.</div>';
                }
                return;
            }

            previewPanel.innerHTML = '<p class="text-sm text-slate-500">Menghitung preview...</p>';

            try {
                var params = new URLSearchParams({
                    kondisi_kembali: kondisiSelect?.value || 'baik',
                });
                if (returnDateInput?.value) {
                    params.set('return_date', returnDateInput.value);
                }

                var payload = await fetchJson(config.previewUrl + '/' + loanId + '?' + params.toString());
                var loan = payload.data || {};

                previewPanel.innerHTML = renderFinePreviewHtml(loan);
            } catch (err) {
                previewPanel.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
            }
        }

        window.loadReturnPreview = loadReturnPreview;

        kondisiSelect?.addEventListener('change', loadReturnPreview);
        returnDateInput?.addEventListener('change', loadReturnPreview);

        if (form) {
            form.addEventListener('submit', async function (event) {
                if (form.dataset.confirming === '1') {
                    form.dataset.confirming = '0';
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                var loanId = document.getElementById('return_peminjaman_id')?.value;
                if (!loanId) {
                    await window.showAlert?.({
                        title: 'Validasi',
                        message: 'Pilih peminjaman aktif terlebih dahulu.',
                        variant: 'warning',
                    });
                    return;
                }

                var preview = null;
                try {
                    var params = new URLSearchParams({
                        kondisi_kembali: kondisiSelect?.value || 'baik',
                    });
                    if (returnDateInput?.value) {
                        params.set('return_date', returnDateInput.value);
                    }
                    var payload = await fetchJson(config.previewUrl + '/' + loanId + '?' + params.toString());
                    preview = payload.data;
                } catch (err) {
                    await window.showAlert?.({
                        title: 'Gagal',
                        message: err.message,
                        variant: 'danger',
                    });
                    return;
                }

                var fineTotal = preview?.fine_preview?.total_fine || 0;
                var kondisi = kondisiSelect?.selectedOptions?.[0]?.textContent || 'Baik';
                var confirmed = await window.showConfirm?.({
                    title: 'Konfirmasi Pengembalian',
                    message: 'Proses pengembalian buku "' + (preview?.buku?.judul || '-') + '"?',
                    detail: 'Kondisi: ' + kondisi + (fineTotal > 0 ? ' · Denda ' + formatRupiah(fineTotal) : ' · Tanpa denda'),
                    confirmText: 'Ya, Proses',
                    tone: fineTotal > 0 ? 'danger' : 'primary',
                });

                if (!confirmed) return;

                form.dataset.confirming = '1';
                form.requestSubmit();
            }, true);

            form.addEventListener('fetch-success', function () {
                document.getElementById('loan-return-modal')?.classList.add('hidden');
            });
        }
    }

    bindTableActions();
    bindExtendModal();

    if (config.page === 'borrow') {
        initBorrowPage();
    }

    if (config.page === 'return') {
        initReturnPage();
    }
})();
