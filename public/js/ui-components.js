(function () {
    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    window.initUiCharts = function () {
        if (typeof Chart === 'undefined') return;

        var primary = cssVar('--color-primary-600') || '#0a7f49';
        var primaryLight = cssVar('--color-primary-300') || '#5ab47e';
        var accent = cssVar('--color-accent-500') || '#ebe21a';
        var isDark = document.documentElement.classList.contains('dark');
        var grid = isDark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';
        var text = isDark ? '#94a3b8' : '#64748b';

        Chart.defaults.font.family = '"Plus Jakarta Sans", sans-serif';
        Chart.defaults.color = text;

        var barCtx = document.getElementById('chart-bar');
        if (barCtx) {
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: [{
                        label: 'Pembayaran SPP',
                        data: [42, 38, 55, 48, 62, 58],
                        backgroundColor: primary,
                        borderRadius: 6,
                    }, {
                        label: 'Tagihan Baru',
                        data: [28, 32, 25, 35, 30, 40],
                        backgroundColor: accent,
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: grid }, beginAtZero: true },
                    },
                },
            });
        }

        var lineCtx = document.getElementById('chart-line');
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                    datasets: [{
                        label: 'Kehadiran Siswa',
                        data: [92, 94, 91, 95, 88, 0, 0],
                        borderColor: primary,
                        backgroundColor: 'rgba(67,97,55,0.1)',
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: grid }, min: 80, max: 100 },
                    },
                },
            });
        }

        var doughnutCtx = document.getElementById('chart-doughnut');
        if (doughnutCtx) {
            new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Lunas', 'Belum Lunas', 'Pending'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: [primary, accent, primaryLight],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    cutout: '65%',
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        }
    };

    window.initUiKitNav = function () {
        var nav = document.querySelector('.ui-kit-nav');
        if (!nav) return;

        var toggle = nav.querySelector('.ui-kit-nav__toggle');
        var activeLabel = nav.querySelector('.ui-kit-nav__toggle-active');
        var links = nav.querySelectorAll('.ui-kit-nav__panel a');
        var sections = Array.from(links).map(function (link) {
            return document.querySelector(link.getAttribute('href'));
        });

        function isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }

        function setOpen(open) {
            if (isDesktop()) {
                nav.classList.remove('is-open');
                toggle?.setAttribute('aria-expanded', 'true');
                return;
            }

            nav.classList.toggle('is-open', open);
            toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle?.addEventListener('click', function () {
            setOpen(!nav.classList.contains('is-open'));
        });

        links.forEach(function (link) {
            link.addEventListener('click', function () {
                if (!isDesktop()) {
                    setOpen(false);
                }
            });
        });

        function updateActiveNav() {
            var scrollY = window.scrollY + 120;
            var activeIndex = 0;

            sections.forEach(function (section, index) {
                if (!section) return;
                if (section.offsetTop <= scrollY && section.offsetTop + section.offsetHeight > scrollY) {
                    activeIndex = index;
                }
            });

            links.forEach(function (link, index) {
                link.classList.toggle('is-active', index === activeIndex);
            });

            if (activeLabel && links[activeIndex]) {
                activeLabel.textContent = links[activeIndex].textContent.trim();
            }
        }

        window.addEventListener('scroll', updateActiveNav, { passive: true });
        window.addEventListener('resize', function () {
            if (isDesktop()) {
                setOpen(true);
            }
        });

        updateActiveNav();
        setOpen(isDesktop());
    };

    window.initUiToastDemos = function () {
        var demos = {
            'simple-success': function () {
                window.showToast?.('Berhasil disimpan.', 'success');
            },
            'simple-error': function () {
                window.showToast?.('Terjadi kesalahan.', 'error');
            },
            'simple-warning': function () {
                window.showToast?.('Periksa data Anda.', 'warning');
            },
            'simple-info': function () {
                window.showToast?.('Informasi penting.', 'info');
            },
            'detail-success': function () {
                window.showToast?.(
                    'Siswa berhasil <span class="toast-message__verb">diperbarui</span>: Ahmad Fauzi · NIS 2025001 · X IPA 1.',
                    'success',
                    7000
                );
            },
            'detail-error': function () {
                window.showToast?.(
                    'Tagihan gagal <span class="toast-message__verb">disimpan</span>: Nominal di bawah Rp 1.000 · Periode tidak valid.',
                    'error',
                    7000
                );
            },
            'detail-warning': function () {
                window.showToast?.(
                    'RFID cashless berhasil <span class="toast-message__verb">diblokir</span>: Siti Nurhaliza · NIS 2025002 · XI IPS 1.',
                    'warning',
                    7000
                );
            },
            'detail-rows': function () {
                window.showToast?.({
                    type: 'info',
                    duration: 8000,
                    message: 'Pembayaran berhasil <span class="toast-message__verb">dicatat</span>',
                    detail: [
                        { label: 'Siswa', value: 'Ahmad Fauzi · NIS 2025001' },
                        { label: 'Nominal', value: 'Rp 450.000' },
                        { label: 'Metode', value: 'Tunai' },
                        { label: 'Referensi', value: 'KW-2026-0714-001' },
                    ],
                });
            },
        };

        document.querySelectorAll('[data-toast-demo]').forEach(function (button) {
            button.addEventListener('click', function () {
                var key = button.getAttribute('data-toast-demo');
                demos[key]?.();
            });
        });
    };

    window.initUiDataTables = function () {
        if (typeof DataTable === 'undefined') return;

        var payloadEl = document.getElementById('ui-dt-demo-data');
        if (!payloadEl) return;

        var rows = [];
        try {
            rows = JSON.parse(payloadEl.textContent || '[]');
        } catch (e) {
            rows = [];
        }

        var language = {
            search: 'Cari',
            lengthMenu: 'Tampilkan _MENU_ baris',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            zeroRecords: 'Data tidak ditemukan',
            paginate: {
                first: '«',
                last: '»',
                next: 'Selanjutnya',
                previous: 'Sebelumnya',
            },
        };

        function statusBadge(status) {
            var map = {
                aktif: 'success',
                pending: 'warning',
                nonaktif: 'neutral',
            };
            var tone = map[status] || 'neutral';
            var label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
            return '<span class="badge badge-' + tone + '">' + label + '</span>';
        }

        var baseColumns = [
            { data: 'nis', title: 'NIS' },
            { data: 'nama', title: 'Nama' },
            { data: 'kelas', title: 'Kelas' },
            {
                data: 'status',
                title: 'Status',
                className: 'dt-center',
                render: function (data, type) {
                    if (type !== 'display') return data || '';
                    return statusBadge(data);
                },
            },
        ];

        var shared = {
            data: rows,
            pageLength: 5,
            lengthMenu: [5, 10, 25],
            autoWidth: false,
            order: [[0, 'asc']],
            language: language,
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
        };

        function createTable(selector, options) {
            var el = document.querySelector(selector);
            if (!el) return null;
            return new DataTable(el, Object.assign({}, shared, options));
        }

        function checkColumn(opts) {
            opts = opts || {};
            return {
                data: null,
                orderable: false,
                searchable: false,
                className: 'dt-center dt-col-check',
                title: opts.title || '',
                render: function (_data, type, row) {
                    if (type !== 'display') return '';
                    return '<span class="dt-col-check__cell">'
                        + '<input type="checkbox" class="form-checkbox ui-dt-row-check" value="'
                        + String(row.nis).replace(/"/g, '&quot;')
                        + '" aria-label="Pilih ' + String(row.nama).replace(/"/g, '&quot;') + '">'
                        + '</span>';
                },
            };
        }

        /** Ignore row-toggle when interacting with form controls inside the row. */
        function isRowSelectIgnoredTarget(target) {
            return Boolean(
                target && target.closest(
                    'input, textarea, select, label, a, button, option, '
                    + '.ui-dt-row-interactive, .dt-col-check__cell'
                )
            );
        }

        function escapeAttr(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        createTable('#ui-dt-regular', { columns: baseColumns });

        // —— Single row select ——
        var selectLabel = document.getElementById('ui-dt-select-label');
        var selectedId = null;

        function syncSingleUi(table) {
            var selectedData = null;
            table.rows().every(function () {
                var data = this.data();
                var node = this.node();
                if (!data || !node) return;
                var on = selectedId != null && String(data.nis) === selectedId;
                node.classList.toggle('ui-dt-row--selected', on);
                if (on) selectedData = data;
            });

            table.table().node().querySelectorAll('tbody .ui-dt-row-check').forEach(function (cb) {
                cb.checked = selectedId != null && String(cb.value) === selectedId;
            });

            if (selectLabel) {
                selectLabel.textContent = selectedData
                    ? selectedData.nama + ' · NIS ' + selectedData.nis + ' · ' + selectedData.kelas
                    : '—';
            }
        }

        function toggleSingleSelected(id) {
            id = String(id);
            selectedId = selectedId === id ? null : id;
            if (selectTable) syncSingleUi(selectTable);
        }

        var selectTable = createTable('#ui-dt-select', {
            columns: [
                checkColumn(),
                baseColumns[0],
                baseColumns[1],
                baseColumns[2],
                baseColumns[3],
            ],
            order: [[1, 'asc']],
        });

        if (selectTable) {
            selectTable.on('draw', function () {
                syncSingleUi(selectTable);
            });

            selectTable.on('change', 'tbody .ui-dt-row-check', function () {
                var id = String(this.value);
                if (this.checked) selectedId = id;
                else if (selectedId === id) selectedId = null;
                syncSingleUi(selectTable);
            });

            selectTable.on('click', 'tbody tr', function (event) {
                if (isRowSelectIgnoredTarget(event.target)) return;
                var tr = event.target.closest('tbody tr');
                if (!tr || !selectTable.table().node().contains(tr)) return;
                var data = selectTable.row(tr).data();
                if (!data) return;
                toggleSingleSelected(data.nis);
            });

            syncSingleUi(selectTable);
        }

        // —— Multi select ——
        var multiCount = document.getElementById('ui-dt-multi-count');
        var multiLabel = document.getElementById('ui-dt-multi-label');
        var multiClear = document.getElementById('ui-dt-multi-clear');
        var selectedIds = new Set();
        var multiAll = null;

        function syncMultiUi(table, opts) {
            opts = opts || {};
            var countEl = opts.countEl || multiCount;
            var labelEl = opts.labelEl || multiLabel;
            var clearEl = opts.clearEl || multiClear;
            var allEl = opts.allEl !== undefined ? opts.allEl : multiAll;
            var idSet = opts.idSet || selectedIds;
            var checkSelector = opts.checkSelector || 'tbody .ui-dt-row-check';

            var labels = [];
            var appliedTotal = 0;
            var appliedSelected = 0;

            table.rows({ search: 'applied' }).every(function () {
                var data = this.data();
                if (!data) return;
                appliedTotal += 1;
                if (idSet.has(String(data.nis))) {
                    appliedSelected += 1;
                    labels.push(data.nama);
                }
            });

            table.rows().every(function () {
                var data = this.data();
                var node = this.node();
                if (!data || !node) return;
                node.classList.toggle('ui-dt-row--selected', idSet.has(String(data.nis)));
            });

            table.table().node().querySelectorAll(checkSelector).forEach(function (cb) {
                cb.checked = idSet.has(String(cb.value));
            });

            if (countEl) countEl.textContent = String(idSet.size);
            if (labelEl) {
                labelEl.textContent = idSet.size
                    ? labels.slice(0, 3).join(', ') + (labels.length > 3 ? '…' : '')
                    : '—';
            }
            if (clearEl) clearEl.disabled = idSet.size === 0;

            if (allEl) {
                if (appliedTotal === 0 || appliedSelected === 0) {
                    allEl.checked = false;
                    allEl.indeterminate = false;
                } else if (appliedSelected === appliedTotal) {
                    allEl.checked = true;
                    allEl.indeterminate = false;
                } else {
                    allEl.checked = false;
                    allEl.indeterminate = true;
                }
                allEl.setAttribute(
                    'aria-checked',
                    allEl.indeterminate ? 'mixed' : (allEl.checked ? 'true' : 'false')
                );
            }
        }

        var multiTable = createTable('#ui-dt-multi', {
            columns: [
                checkColumn({
                    title: '<span class="dt-col-check__cell"><input type="checkbox" id="ui-dt-multi-all" class="form-checkbox" aria-label="Pilih semua" aria-checked="false"></span>',
                }),
                baseColumns[0],
                baseColumns[1],
                baseColumns[2],
                baseColumns[3],
            ],
            order: [[1, 'asc']],
        });

        multiAll = document.getElementById('ui-dt-multi-all');

        if (multiTable) {
            multiTable.on('draw', function () {
                multiAll = document.getElementById('ui-dt-multi-all') || multiAll;
                syncMultiUi(multiTable);
            });

            multiTable.on('change', 'tbody .ui-dt-row-check', function () {
                var id = String(this.value);
                if (this.checked) selectedIds.add(id);
                else selectedIds.delete(id);
                syncMultiUi(multiTable);
            });

            multiTable.on('click', 'tbody tr', function (event) {
                if (isRowSelectIgnoredTarget(event.target)) return;
                var tr = event.target.closest('tbody tr');
                if (!tr) return;
                var data = multiTable.row(tr).data();
                if (!data) return;
                var id = String(data.nis);
                if (selectedIds.has(id)) selectedIds.delete(id);
                else selectedIds.add(id);
                syncMultiUi(multiTable);
            });

            multiTable.table().node().addEventListener('change', function (event) {
                if (!event.target || event.target.id !== 'ui-dt-multi-all') return;
                multiAll = event.target;
                var on = multiAll.checked;
                multiTable.rows({ search: 'applied' }).every(function () {
                    var data = this.data();
                    if (!data) return;
                    if (on) selectedIds.add(String(data.nis));
                    else selectedIds.delete(String(data.nis));
                });
                syncMultiUi(multiTable);
            });

            multiClear?.addEventListener('click', function () {
                selectedIds.clear();
                syncMultiUi(multiTable);
            });

            syncMultiUi(multiTable);
        }

        // —— Multi select + per-row inputs ——
        var inputCount = document.getElementById('ui-dt-input-count');
        var inputLabel = document.getElementById('ui-dt-input-label');
        var inputClear = document.getElementById('ui-dt-input-clear');
        var inputSelectedIds = new Set();
        var inputAll = null;
        var rowFieldState = {};

        rows.forEach(function (row, index) {
            rowFieldState[String(row.nis)] = {
                poin: String((index % 5) + 1),
                catatan: '',
            };
        });

        function syncInputRowUi(table) {
            inputAll = document.getElementById('ui-dt-input-all') || inputAll;
            syncMultiUi(table, {
                countEl: inputCount,
                labelEl: inputLabel,
                clearEl: inputClear,
                allEl: inputAll,
                idSet: inputSelectedIds,
                checkSelector: 'tbody .ui-dt-row-check',
            });

            // Restore field values after draw (inputs are recreated)
            table.table().node().querySelectorAll('[data-ui-dt-field]').forEach(function (el) {
                var nis = String(el.getAttribute('data-nis') || '');
                var field = el.getAttribute('data-ui-dt-field');
                if (!nis || !field || !rowFieldState[nis]) return;
                if (document.activeElement === el) return;
                el.value = rowFieldState[nis][field] != null ? rowFieldState[nis][field] : '';
            });
        }

        var inputTable = createTable('#ui-dt-input', {
            columns: [
                checkColumn({
                    title: '<span class="dt-col-check__cell"><input type="checkbox" id="ui-dt-input-all" class="form-checkbox" aria-label="Pilih semua" aria-checked="false"></span>',
                }),
                baseColumns[0],
                baseColumns[1],
                baseColumns[2],
                {
                    data: null,
                    title: 'Poin',
                    orderable: false,
                    searchable: false,
                    className: 'ui-dt-col-input',
                    render: function (_data, type, row) {
                        if (type !== 'display') return '';
                        var nis = String(row.nis);
                        var value = rowFieldState[nis]?.poin || '1';
                        return '<div class="ui-dt-row-interactive">'
                            + '<input type="number" min="0" max="100" class="form-input ui-dt-row-input ui-dt-row-input--narrow" '
                            + 'data-ui-dt-field="poin" data-nis="' + escapeAttr(nis) + '" '
                            + 'value="' + escapeAttr(value) + '" aria-label="Poin ' + escapeAttr(row.nama) + '">'
                            + '</div>';
                    },
                },
                {
                    data: null,
                    title: 'Catatan',
                    orderable: false,
                    searchable: false,
                    className: 'ui-dt-col-input',
                    render: function (_data, type, row) {
                        if (type !== 'display') return '';
                        var nis = String(row.nis);
                        var value = rowFieldState[nis]?.catatan || '';
                        return '<div class="ui-dt-row-interactive">'
                            + '<input type="text" class="form-input ui-dt-row-input" '
                            + 'data-ui-dt-field="catatan" data-nis="' + escapeAttr(nis) + '" '
                            + 'value="' + escapeAttr(value) + '" placeholder="Catatan…" '
                            + 'aria-label="Catatan ' + escapeAttr(row.nama) + '">'
                            + '</div>';
                    },
                },
            ],
            order: [[1, 'asc']],
        });

        inputAll = document.getElementById('ui-dt-input-all');

        if (inputTable) {
            inputTable.on('draw', function () {
                syncInputRowUi(inputTable);
            });

            inputTable.on('change', 'tbody .ui-dt-row-check', function () {
                var id = String(this.value);
                if (this.checked) inputSelectedIds.add(id);
                else inputSelectedIds.delete(id);
                syncInputRowUi(inputTable);
            });

            inputTable.on('click', 'tbody tr', function (event) {
                if (isRowSelectIgnoredTarget(event.target)) return;
                var tr = event.target.closest('tbody tr');
                if (!tr) return;
                var data = inputTable.row(tr).data();
                if (!data) return;
                var id = String(data.nis);
                if (inputSelectedIds.has(id)) inputSelectedIds.delete(id);
                else inputSelectedIds.add(id);
                syncInputRowUi(inputTable);
            });

            inputTable.table().node().addEventListener('change', function (event) {
                var target = event.target;
                if (!target) return;

                if (target.id === 'ui-dt-input-all') {
                    inputAll = target;
                    var on = inputAll.checked;
                    inputTable.rows({ search: 'applied' }).every(function () {
                        var data = this.data();
                        if (!data) return;
                        if (on) inputSelectedIds.add(String(data.nis));
                        else inputSelectedIds.delete(String(data.nis));
                    });
                    syncInputRowUi(inputTable);
                    return;
                }

                if (target.matches('[data-ui-dt-field]')) {
                    var nis = String(target.getAttribute('data-nis') || '');
                    var field = target.getAttribute('data-ui-dt-field');
                    if (!nis || !field) return;
                    if (!rowFieldState[nis]) rowFieldState[nis] = { poin: '1', catatan: '' };
                    rowFieldState[nis][field] = target.value;
                }
            });

            inputTable.table().node().addEventListener('input', function (event) {
                var target = event.target;
                if (!target || !target.matches('[data-ui-dt-field]')) return;
                var nis = String(target.getAttribute('data-nis') || '');
                var field = target.getAttribute('data-ui-dt-field');
                if (!nis || !field) return;
                if (!rowFieldState[nis]) rowFieldState[nis] = { poin: '1', catatan: '' };
                rowFieldState[nis][field] = target.value;
            });

            inputClear?.addEventListener('click', function () {
                inputSelectedIds.clear();
                syncInputRowUi(inputTable);
            });

            syncInputRowUi(inputTable);
        }

        // —— Filtered ——
        var filterState = { q: '', kelas: '', status: '' };

        var filterFn = function (settings, _data, dataIndex, rowData) {
            if (!settings.nTable || settings.nTable.id !== 'ui-dt-filter') return true;

            var row = rowData || {};
            if (!rowData) {
                try {
                    row = new DataTable.Api(settings).row(dataIndex).data() || {};
                } catch (e) {
                    row = {};
                }
            }

            var q = filterState.q.trim().toLowerCase();
            if (q) {
                var hay = ((row.nis || '') + ' ' + (row.nama || '')).toLowerCase();
                if (hay.indexOf(q) === -1) return false;
            }
            if (filterState.kelas && row.kelas !== filterState.kelas) return false;
            if (filterState.status && row.status !== filterState.status) return false;
            return true;
        };
        DataTable.ext.search.push(filterFn);

        var filterTable = createTable('#ui-dt-filter', {
            columns: baseColumns,
            // Built-in search still useful; filter-bar is the primary demo
            layout: {
                topStart: 'pageLength',
                topEnd: null,
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
        });

        var filterForm = document.getElementById('ui-dt-filter-form');
        if (filterForm && filterTable) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(filterForm);
                filterState.q = String(fd.get('q') || '');
                filterState.kelas = String(fd.get('kelas') || '');
                filterState.status = String(fd.get('status') || '');
                filterTable.draw();
            });

            filterForm.addEventListener('reset', function () {
                setTimeout(function () {
                    filterState.q = '';
                    filterState.kelas = '';
                    filterState.status = '';
                    filterTable.draw();
                }, 0);
            });
        }
    };
})();
