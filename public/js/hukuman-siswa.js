(function () {
    var SANCTION_RANK = {
        SP1: 1,
        SP2: 2,
        SP3: 3,
        'ganti rugi 10 x lipat': 4,
        DO: 5,
    };

    var SANCTION_LABELS = {
        SP1: 'Surat Peringatan 1',
        SP2: 'Surat Peringatan 2',
        SP3: 'Surat Peringatan 3',
        DO: 'Drop Out (dikeluarkan)',
        'ganti rugi 10 x lipat': 'Ganti rugi 10x lipat',
    };

    var cachedViolations = [];
    var availableTotalPoint = 0;

    function recommendBaseUrl() {
        return window.hukumanRecommendUrlBase || '/admin/prestasi-pelanggaran/hukuman-siswa/recommend';
    }

    function recommendationPanel() {
        return document.getElementById('hukuman-recommendation-panel');
    }

    function setSiswaDisplay(name, nis, totalPoint) {
        var nameEl = document.getElementById('hukuman-siswa-display-name');
        var subEl = document.getElementById('hukuman-siswa-display-sub');
        if (nameEl) nameEl.textContent = name || 'Pilih dari daftar eligible';
        if (subEl) {
            var parts = [];
            if (nis) parts.push('NIS ' + nis);
            if (totalPoint != null && totalPoint !== '') parts.push(totalPoint + ' poin aktif');
            subEl.textContent = parts.length ? parts.join(' · ') : '-';
        }
    }

    function highestSanction(codes) {
        var best = null;
        var bestRank = 0;
        (codes || []).forEach(function (code) {
            if (!code) return;
            var rank = SANCTION_RANK[code] || 0;
            if (rank > bestRank) {
                bestRank = rank;
                best = code;
            }
        });
        return best;
    }

    function selectedViolationInputs() {
        return Array.from(document.querySelectorAll('#hukuman-rec-violations input[name="pelanggaran_ids[]"]:checked'));
    }

    function syncRecommendationFromSelection(updateSanctionInput) {
        var selected = selectedViolationInputs();
        var total = 0;
        var sanctions = [];

        selected.forEach(function (input) {
            total += parseInt(input.dataset.point || '0', 10) || 0;
            if (input.dataset.sanction) {
                sanctions.push(input.dataset.sanction);
            }
        });

        var recommended = highestSanction(sanctions);
        var label = recommended ? (SANCTION_LABELS[recommended] || recommended) : '-';

        var totalEl = document.getElementById('hukuman-rec-total-point');
        var sanctionEl = document.getElementById('hukuman-rec-sanction');
        var availableEl = document.getElementById('hukuman-rec-available');

        if (totalEl) totalEl.textContent = String(total);
        if (sanctionEl) sanctionEl.textContent = label;
        if (availableEl) {
            availableEl.textContent = selected.length
                ? (selected.length + ' pelanggaran dipilih · sisa poin aktif siswa: ' + availableTotalPoint)
                : 'Belum ada pelanggaran dipilih';
        }

        if (updateSanctionInput) {
            var sanctionInput = document.getElementById('hukuman-siswa-sanction');
            if (sanctionInput && recommended) {
                sanctionInput.value = label;
            }
        }
    }

    function renderViolationChoices(violations) {
        var list = document.getElementById('hukuman-rec-violations');
        if (!list) return;

        list.innerHTML = '';
        cachedViolations = violations || [];

        if (!cachedViolations.length) {
            list.innerHTML = '<p class="text-xs text-slate-500">Tidak ada pelanggaran aktif.</p>';
            syncRecommendationFromSelection(false);
            return;
        }

        cachedViolations.forEach(function (v) {
            var id = 'hukuman-pelanggaran-' + v.id;
            var label = document.createElement('label');
            label.className = 'flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-950/40';
            label.setAttribute('for', id);

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'pelanggaran_ids[]';
            input.id = id;
            input.value = String(v.id);
            input.className = 'form-checkbox mt-0.5';
            input.checked = true;
            input.dataset.point = String(v.point || 0);
            input.dataset.sanction = v.sanction || '';
            input.addEventListener('change', function () {
                syncRecommendationFromSelection(true);
            });

            var body = document.createElement('span');
            body.className = 'min-w-0 flex-1 leading-snug text-slate-700 dark:text-slate-200';

            var title = document.createElement('span');
            title.className = 'font-medium';
            title.textContent = (v.tanggal || '-') + ' · ' + (v.judul || '-') + ' (' + (v.point || 0) + ' poin)';
            body.appendChild(title);

            var meta = [];
            if (v.jenis_nama) meta.push(v.jenis_nama);
            if (v.sanction_label && v.sanction_label !== '-') meta.push(v.sanction_label);
            if (meta.length) {
                var metaEl = document.createElement('span');
                metaEl.className = 'mt-0.5 block text-slate-500';
                metaEl.textContent = meta.join(' · ');
                body.appendChild(metaEl);
            }

            label.appendChild(input);
            label.appendChild(body);
            list.appendChild(label);
        });

        syncRecommendationFromSelection(true);
    }

    function setAllViolations(checked) {
        document.querySelectorAll('#hukuman-rec-violations input[name="pelanggaran_ids[]"]').forEach(function (input) {
            input.checked = !!checked;
        });
        syncRecommendationFromSelection(true);
    }

    function loadRecommendation(siswaId) {
        var panel = recommendationPanel();
        if (!panel || !siswaId) {
            panel?.classList.add('hidden');
            return;
        }

        fetch(recommendBaseUrl() + '/' + siswaId, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
            .then(function (result) {
                var payload = result.body || {};
                var data = payload.data || {};

                if (!result.ok || !payload.success) {
                    panel.classList.add('hidden');
                    if (typeof window.showToast === 'function') {
                        window.showToast(payload.message || 'Siswa belum eligible untuk hukuman.', 'error');
                    }
                    return;
                }

                panel.classList.remove('hidden');
                availableTotalPoint = data.available_total_point ?? data.total_point ?? 0;
                setSiswaDisplay(data.siswa_name, data.siswa_nis, availableTotalPoint);
                renderViolationChoices(data.violations || []);
            })
            .catch(function () {
                panel?.classList.add('hidden');
            });
    }

    function setHukumanFormMode(isEdit) {
        var createFields = document.getElementById('hukuman-siswa-create-fields');
        var editFields = document.getElementById('hukuman-siswa-edit-fields');
        if (createFields) createFields.classList.toggle('hidden', isEdit);
        if (editFields) editFields.classList.toggle('hidden', !isEdit);
    }

    function openModalForSiswa(siswaId, name, nis, totalPoint) {
        var modal = document.getElementById('hukuman-siswa-modal');
        var form = document.getElementById('hukuman-siswa-form');
        if (!modal || !form) return;

        setHukumanFormMode(false);
        form.action = form.dataset.defaultAction;
        form.dataset.method = 'POST';
        document.getElementById('hukuman-siswa-siswa').value = siswaId;
        setSiswaDisplay(name, nis, totalPoint);
        modal.classList.remove('hidden');
        loadRecommendation(siswaId);
    }

    window.openHukumanCreateForSiswa = function (siswaId) {
        openModalForSiswa(siswaId, null, null, null);
    };

    function parseColumnOptions(table) {
        try {
            return JSON.parse(table.getAttribute('data-column-options') || '[]');
        } catch (e) {
            return [];
        }
    }

    function initEligibleTable() {
        var table = document.getElementById('eligible_table');
        if (!table || !window.jQuery || !window.jQuery.fn.DataTable) return;
        if (window.jQuery.fn.DataTable.isDataTable(table)) return;

        var columnOptions = parseColumnOptions(table);
        var columns = columnOptions.map(function (opt) {
            return {
                orderable: opt.orderable !== false,
                searchable: !!opt.searchable,
                render: opt.html ? function (data) {
                    if (data && typeof data === 'object' && data.display !== undefined) return data.display;
                    return data;
                } : undefined,
            };
        });

        var defaultOrder = [[3, 'desc']];
        try {
            defaultOrder = JSON.parse(table.getAttribute('data-default-order') || '[[3,"desc"]]');
        } catch (e) {}

        var dt = window.jQuery(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: table.getAttribute('data-ajax-url'),
                data: function (d) {
                    var form = document.getElementById('eligible-filter-form');
                    if (!form) return;
                    new FormData(form).forEach(function (value, key) {
                        if (value !== '') d[key] = value;
                    });
                },
            },
            columns: columns,
            order: defaultOrder,
            pageLength: 10,
            autoWidth: false,
        });

        if (typeof window.ensureTableScrollArea === 'function') {
            window.ensureTableScrollArea(table);
        }

        var form = document.getElementById('eligible-filter-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                dt.ajax.reload();
            });
            form.addEventListener('reset', function () {
                setTimeout(function () { dt.ajax.reload(); }, 0);
            });
        }

        window.eligibleHukumanTable = dt;
    }

    document.addEventListener('DOMContentLoaded', function () {
        initEligibleTable();
        document.getElementById('hukuman-rec-select-all')?.addEventListener('click', function () {
            setAllViolations(true);
        });
        document.getElementById('hukuman-rec-clear-all')?.addEventListener('click', function () {
            setAllViolations(false);
        });
    });

    document.addEventListener('modalReset', function (e) {
        if (e.detail?.formId !== 'hukuman-siswa-form') return;
        setHukumanFormMode(false);
        recommendationPanel()?.classList.add('hidden');
        cachedViolations = [];
        availableTotalPoint = 0;
        var list = document.getElementById('hukuman-rec-violations');
        if (list) list.innerHTML = '';
        var form = document.getElementById('hukuman-siswa-form');
        if (form) {
            form.action = form.dataset.defaultAction;
            form.dataset.method = 'POST';
        }
        document.getElementById('hukuman-siswa-siswa').value = '';
        setSiswaDisplay(null, null, null);
    });

    document.addEventListener('edit-record-populated', function (e) {
        var form = e.detail?.form;
        if (!form || form.id !== 'hukuman-siswa-form') return;
        setHukumanFormMode(true);
        recommendationPanel()?.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        var eligibleBtn = e.target.closest('[data-hukuman-eligible-siswa]');
        if (eligibleBtn) {
            e.preventDefault();
            openModalForSiswa(
                eligibleBtn.dataset.hukumanEligibleSiswa,
                eligibleBtn.dataset.siswaName,
                eligibleBtn.dataset.siswaNis,
                eligibleBtn.dataset.totalPoint
            );
            return;
        }

        var btn = e.target.closest('[data-view-url]');
        if (!btn || btn.dataset.viewModal !== 'hukuman-siswa-detail-modal') return;
        e.preventDefault();

        fetch(btn.dataset.viewUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.json(); })
            .then(function (payload) {
                if (!payload.success) return;
                var d = payload.data;
                var modal = document.getElementById('hukuman-siswa-detail-modal');
                modal.querySelector('.detail-siswa-name').textContent = d.siswa_name || '-';
                modal.querySelector('.detail-siswa-sub').textContent = 'NIS ' + (d.siswa_nis || '-') + ' · ' + (d.siswa_kelas || '-');
                modal.querySelector('.detail-total-point').textContent = d.total_point ?? 0;
                modal.querySelector('.detail-recommended').textContent = d.recommended_label || '-';
                modal.querySelector('.detail-sanction').textContent = d.sanction_label || '-';
                modal.querySelector('.detail-status').textContent = d.status_label || '-';
                modal.querySelector('.detail-keterangan').textContent = d.keterangan || '-';
                var list = modal.querySelector('.detail-violations-list');
                list.innerHTML = '';
                (d.violations || []).forEach(function (v) {
                    var item = document.createElement('div');
                    item.className = 'rounded border border-slate-200 px-3 py-2 dark:border-slate-700';
                    item.textContent = (v.tanggal || '-') + ' · ' + (v.judul || '-') + ' (' + (v.point || 0) + ' poin)';
                    list.appendChild(item);
                });
                var buktiList = modal.querySelector('.detail-bukti-list');
                if (buktiList) {
                    buktiList.innerHTML = '';
                    if (typeof window.renderBuktiDetailList === 'function') {
                        window.renderBuktiDetailList(buktiList, d.bukti || [], { group: 'hukuman-siswa-' + (d.id || Date.now()) });
                    } else {
                        buktiList.innerHTML = '<p class="text-xs text-slate-400">Tidak ada bukti.</p>';
                    }
                }
                modal.classList.remove('hidden');
            });
    });
})();
