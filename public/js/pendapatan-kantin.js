(function () {
    var page = document.getElementById('pendapatan-kantin-page');
    if (!page) return;

    var showBaseUrl = page.getAttribute('data-show-url') || '';
    var withdrawBaseUrl = page.getAttribute('data-withdraw-url') || showBaseUrl;
    var modal = document.getElementById('pendapatan-kantin-detail-modal');
    var summaryEl = document.getElementById('pendapatan-kantin-detail-summary');
    var trxTableEl = document.getElementById('pendapatan_kantin_trx_table');
    var trxFilterForm = document.getElementById('pendapatan-kantin-trx-filter-form');
    var withdrawModal = document.getElementById('pendapatan-kantin-withdraw-modal');
    var withdrawForm = document.getElementById('pendapatan-kantin-withdraw-form');
    var trxTable = null;
    var activeUserId = null;

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
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

        var text = String(value).trim();
        var normalized = text.indexOf('T') === -1 && /^\d{4}-\d{2}-\d{2}/.test(text)
            ? text.replace(' ', 'T')
            : text;
        var parsed = new Date(normalized);

        return isNaN(parsed.getTime()) ? null : parsed;
    }

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

    function renderCell(data, type) {
        if (data && typeof data === 'object' && 'display' in data && 'raw' in data) {
            if (type === 'display' || type === 'filter') {
                if (data.type === 'datetime') {
                    return formatIndonesianDate(data.raw != null ? data.raw : data.display, true);
                }
                if (data.type === 'date') {
                    return formatIndonesianDate(data.raw != null ? data.raw : data.display, false);
                }
                if (data.type === 'number') {
                    return data.display;
                }

                return data.display;
            }

            return data.raw;
        }

        if ((type === 'display' || type === 'filter') && typeof data === 'string' && /^\d{4}-\d{2}-\d{2}/.test(data)) {
            return formatIndonesianDate(data, data.length > 10);
        }

        return data == null ? '' : data;
    }

    function parseAmount(input) {
        if (!input) return 0;
        if (typeof window.parseFormattedNumber === 'function') {
            return window.parseFormattedNumber(input.value);
        }
        return Number(String(input.value || '').replace(/\D/g, '') || 0);
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Permintaan gagal.');
                }

                return payload;
            });
        });
    }

    function renderSummary(operator) {
        summaryEl.innerHTML = '<div class="grid gap-3 sm:grid-cols-2">'
            + '<div><p class="text-slate-500">Nama</p><p class="font-medium text-slate-900 dark:text-white">' + (operator.name || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Username</p><p class="font-medium text-slate-900 dark:text-white">' + (operator.username || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Sekolah</p><p class="font-medium text-slate-900 dark:text-white">' + (operator.sekolah || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Status</p><p class="font-medium text-slate-900 dark:text-white">' + (operator.status || '-') + '</p></div>'
            + '<div class="sm:col-span-2"><p class="text-slate-500">Sisa belum ditarik</p><p class="font-medium text-slate-900 dark:text-white">'
            + formatRp(operator.outstanding || 0) + '</p></div>'
            + '</div>';
    }

    function destroyTrxTable() {
        if (!trxTableEl) return;

        try {
            if (trxTable && typeof trxTable.destroy === 'function') {
                trxTable.destroy();
            } else if (typeof DataTable !== 'undefined' && DataTable.isDataTable && DataTable.isDataTable(trxTableEl)) {
                DataTable.getInstance(trxTableEl).destroy();
            } else if (window.jQuery) {
                var $table = window.jQuery(trxTableEl);
                if ($table.DataTable && $table.DataTable.isDataTable($table)) {
                    $table.DataTable().destroy();
                }
            }
        } finally {
            trxTable = null;
            trxTableEl = document.getElementById('pendapatan_kantin_trx_table');
        }
    }

    function initTrxTable(userId) {
        trxTableEl = document.getElementById('pendapatan_kantin_trx_table');
        if (!trxTableEl) return;

        destroyTrxTable();
        trxTableEl = document.getElementById('pendapatan_kantin_trx_table');

        var ajaxUrl = showBaseUrl + '/' + userId + '/transactions';
        var titles = Array.from(trxTableEl.querySelectorAll('thead th')).map(function (th) {
            return th.textContent.trim();
        }).filter(Boolean);

        if (!titles.length) return;

        var thead = trxTableEl.querySelector('thead');
        if (thead) {
            thead.parentNode.removeChild(thead);
        }

        var options = {
            processing: true,
            serverSide: true,
            ajax: {
                url: ajaxUrl,
                data: function (d) {
                    if (trxFilterForm) {
                        new FormData(trxFilterForm).forEach(function (value, key) {
                            d[key] = value;
                        });
                    }
                },
            },
            columns: titles.map(function (title, index) {
                return {
                    data: index,
                    title: title,
                    orderable: true,
                    searchable: true,
                    defaultContent: '',
                    render: function (data, type) {
                        return renderCell(data, type);
                    },
                };
            }),
            order: [[0, 'desc']],
            pageLength: 10,
            autoWidth: false,
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
            language: {
                processing: 'Memuat...',
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
        };

        if (typeof DataTable !== 'undefined') {
            trxTable = new DataTable(trxTableEl, options);
        } else if (window.jQuery) {
            trxTable = window.jQuery(trxTableEl).DataTable(options);
        }
    }

    function reloadTrxTable() {
        if (!trxTable) return;
        if (typeof trxTable.ajax?.reload === 'function') {
            trxTable.ajax.reload(null, false);
            return;
        }
        if (typeof trxTable.draw === 'function') {
            trxTable.draw(false);
        }
    }

    function openDetail(userId) {
        if (!modal || !summaryEl || !userId) return;

        activeUserId = userId;
        summaryEl.textContent = 'Memuat data operator...';
        modal.classList.remove('hidden');

        fetchJson(showBaseUrl + '/' + userId)
            .then(function (payload) {
                renderSummary(payload.data || {});
                initTrxTable(userId);
            })
            .catch(function (err) {
                summaryEl.innerHTML = '<p class="text-sm text-red-600">' + (err.message || 'Gagal memuat detail.') + '</p>';
            });
    }

    function openWithdraw(button) {
        if (!withdrawModal || !withdrawForm) return;

        var userId = parseInt(button.dataset.userId || '', 10);
        var outstanding = parseInt(button.dataset.outstanding || '0', 10);
        var name = button.dataset.userName || '-';

        if (!userId || outstanding < 1) return;

        withdrawForm.action = withdrawBaseUrl + '/' + userId + '/withdraw';
        withdrawForm.dataset.method = 'POST';

        var idInput = document.getElementById('withdraw-kantin-user-id');
        if (idInput) idInput.value = String(userId);

        var nameEl = document.getElementById('withdraw-operator-name');
        if (nameEl) nameEl.textContent = name;

        var outstandingEl = document.getElementById('withdraw-outstanding-label');
        if (outstandingEl) outstandingEl.textContent = formatRp(outstanding);

        var amountInput = document.getElementById('withdraw-pendapatan-amount');
        if (amountInput) {
            amountInput.value = outstanding.toLocaleString('id-ID');
            amountInput.dataset.maxOutstanding = String(outstanding);
        }

        var desc = document.getElementById('withdraw-pendapatan-description');
        if (desc) desc.value = '';

        withdrawModal.classList.remove('hidden');
    }

    window.buildPendapatanKantinWithdrawConfirm = function (form) {
        var name = document.getElementById('withdraw-operator-name')?.textContent || '-';
        var outstandingLabel = document.getElementById('withdraw-outstanding-label')?.textContent || '-';
        var amountInput = form.querySelector('[name="amount"]');
        var amount = parseAmount(amountInput);

        return {
            title: 'Konfirmasi Tarik Tunai',
            message: 'Serahkan kas fisik ke operator kantin sesuai nominal berikut?',
            detail: [
                { label: 'Operator', value: name },
                { label: 'Sisa belum ditarik', value: outstandingLabel },
                { label: 'Nominal ditarik', value: formatRp(amount) },
            ],
            tone: 'primary',
            confirmLabel: 'Ya, tarik tunai',
        };
    };

    document.addEventListener('click', function (event) {
        var detailBtn = event.target.closest('[data-pendapatan-kantin-detail]');
        if (detailBtn) {
            openDetail(parseInt(detailBtn.dataset.userId || '', 10));
            return;
        }

        var withdrawBtn = event.target.closest('[data-pendapatan-kantin-withdraw]');
        if (withdrawBtn) {
            openWithdraw(withdrawBtn);
        }
    });

    if (trxFilterForm) {
        trxFilterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (activeUserId) {
                reloadTrxTable();
            }
        });
    }
})();
