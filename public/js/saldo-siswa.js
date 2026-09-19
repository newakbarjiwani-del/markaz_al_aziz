(function () {
    var page = document.getElementById('saldo-siswa-page');
    if (!page) return;

    var showBaseUrl = page.getAttribute('data-show-url') || '';
    var transactionsBaseUrl = page.getAttribute('data-transactions-url') || '';
    var modal = document.getElementById('saldo-detail-modal');
    var summaryEl = document.getElementById('saldo-detail-summary');
    var trxTableEl = document.getElementById('saldo_trx_table');
    var trxFilterForm = document.getElementById('saldo-trx-filter-form');
    var trxTable = null;
    var activeSiswaId = null;

    function formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
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

    /** Handle DataTableTrait structured cells ({ display, raw, type }). */
    function renderStructuredCell(data, type) {
        if (data == null || typeof data !== 'object' || !('display' in data) || !('raw' in data)) {
            return data;
        }

        var cellType = data.type || 'text';

        if (type === 'display' || type === 'filter') {
            if (cellType === 'datetime' || cellType === 'date') {
                return formatIndonesianDate(data.raw != null ? data.raw : data.display, cellType === 'datetime');
            }
            return data.display;
        }

        return data.raw;
    }

    async function fetchJson(url) {
        var response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        var data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Permintaan gagal.');
        }
        return data;
    }

    function renderSummary(payload) {
        var siswa = payload.siswa || {};
        var facts = [
            ['NIS', siswa.nis || '-'],
            ['Nama', siswa.name || '-'],
            ['Kelas', siswa.kelas || '-'],
            ['No. VA', siswa.virtual_account || '-'],
        ];

        var factsHtml = facts.map(function (row) {
            return '<div class="saldo-detail-modal__fact">'
                + '<dt>' + row[0] + '</dt>'
                + '<dd>' + row[1] + '</dd>'
                + '</div>';
        }).join('');

        summaryEl.innerHTML = '<div class="saldo-detail-modal__meta">'
            + '<dl class="saldo-detail-modal__facts">' + factsHtml + '</dl>'
            + '<div class="saldo-detail-modal__balance">'
            + '<p class="saldo-detail-modal__balance-label">Saldo</p>'
            + '<p class="saldo-detail-modal__balance-value">' + formatRupiah(payload.balance) + '</p>'
            + '</div>'
            + '</div>';
    }

    function refreshTrxTableElement() {
        trxTableEl = document.getElementById('saldo_trx_table');
    }

    function destroyTrxTable() {
        refreshTrxTableElement();
        if (!trxTableEl) return;

        try {
            if (trxTable && typeof trxTable.destroy === 'function') {
                trxTable.destroy();
            } else if (typeof DataTable !== 'undefined' && typeof DataTable.isDataTable === 'function' && DataTable.isDataTable(trxTableEl)) {
                if (typeof DataTable.api === 'function') {
                    DataTable.api(trxTableEl).destroy();
                } else if (typeof DataTable.getInstance === 'function') {
                    DataTable.getInstance(trxTableEl).destroy();
                }
            } else if (window.jQuery) {
                var $table = window.jQuery(trxTableEl);
                if ($table.DataTable && $table.DataTable.isDataTable($table)) {
                    $table.DataTable().destroy();
                }
            }
        } finally {
            trxTable = null;
            refreshTrxTableElement();
        }
    }

    function readTrxColumnTitles() {
        refreshTrxTableElement();
        if (!trxTableEl) return [];

        return Array.from(trxTableEl.querySelectorAll('thead th')).map(function (th) {
            return th.textContent.trim();
        }).filter(Boolean);
    }

    function initTrxTable(siswaId) {
        refreshTrxTableElement();
        if (!trxTableEl) return;

        destroyTrxTable();
        refreshTrxTableElement();

        var ajaxUrl = transactionsBaseUrl + '/' + siswaId + '/transactions';
        trxTableEl.setAttribute('data-ajax-url', ajaxUrl);

        var titles = readTrxColumnTitles();
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
                    orderable: index !== 5 && index !== 6,
                    searchable: true,
                    defaultContent: '',
                    render: renderStructuredCell,
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

    async function openDetail(siswaId, label) {
        if (!modal || !summaryEl) return;

        activeSiswaId = siswaId;
        var titleEl = document.getElementById('saldo-detail-modal-title');
        if (titleEl) {
            titleEl.textContent = label ? 'Detail Saldo — ' + label : 'Detail Saldo Siswa';
        }

        summaryEl.innerHTML = '<p class="text-sm text-slate-500">Memuat data siswa...</p>';
        modal.classList.remove('hidden');

        try {
            var payload = await fetchJson(showBaseUrl + '/' + siswaId);
            renderSummary(payload.data || {});
            initTrxTable(siswaId);
        } catch (err) {
            summaryEl.innerHTML = '<p class="text-sm text-red-600">' + err.message + '</p>';
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-saldo-detail]');
        if (!button) return;

        var siswaId = button.getAttribute('data-siswa-id');
        var label = button.getAttribute('data-siswa-label') || '';
        if (!siswaId) return;

        openDetail(siswaId, label);
    });

    if (trxFilterForm) {
        trxFilterForm.addEventListener('submit', function (event) {
            event.preventDefault();
            reloadTrxTable();
        });

        trxFilterForm.addEventListener('reset', function () {
            setTimeout(function () {
                if (activeSiswaId) {
                    reloadTrxTable();
                }
            }, 0);
        });
    }

    document.addEventListener('click', function (event) {
        var closeBtn = event.target.closest('[data-modal-close="saldo-detail-modal"]');
        if (!closeBtn) return;

        activeSiswaId = null;
        destroyTrxTable();
    });
})();
