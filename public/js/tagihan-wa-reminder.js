(function () {
    function formatRp(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function getConfig() {
        return document.getElementById('kirim-tagihan-wa-config')?.dataset || {};
    }

    function initTagihanWaReminder() {
        var config = getConfig();
        var selected = new Map();
        var lockedSiswaId = null;
        var previewPayload = null;

        var selectionBar = document.getElementById('tagihan-wa-selection-bar');
        var selectionSummary = document.getElementById('tagihan-wa-selection-summary');
        var selectionPhone = document.getElementById('tagihan-wa-selection-phone');
        var clearBtn = document.getElementById('tagihan-wa-clear-btn');
        var previewBtn = document.getElementById('tagihan-wa-preview-btn');
        var selectAll = document.getElementById('tagihan-wa-select-all');
        var table = document.getElementById('main_table');

        var previewModal = document.getElementById('tagihan-wa-preview-modal');
        var previewSiswa = document.getElementById('tagihan-wa-preview-siswa');
        var previewPhone = document.getElementById('tagihan-wa-preview-phone');
        var previewTotal = document.getElementById('tagihan-wa-preview-total');
        var previewTemplate = document.getElementById('tagihan-wa-preview-template');
        var previewMessage = document.getElementById('tagihan-wa-preview-message');
        var templateMode = document.getElementById('tagihan-wa-template-mode');
        var templatePickerWrap = document.getElementById('tagihan-wa-template-picker-wrap');
        var templateSelect = document.getElementById('tagihan-wa-template-id');
        var openWaBtn = document.getElementById('tagihan-wa-open-btn');

        if (!table || !config.buildUrl) {
            return;
        }

        table.classList.add('ui-dt--selectable', 'tagihan-wa-table');

        function syncSelectableRows() {
            table.querySelectorAll('tbody tr').forEach(function (row) {
                var box = row.querySelector('.tagihan-wa-checkbox');
                if (!box) {
                    row.classList.remove('tagihan-wa-row--selectable', 'ui-dt-row--selected');
                    row.removeAttribute('tabindex');
                    row.removeAttribute('role');
                    row.removeAttribute('aria-pressed');
                    return;
                }

                var id = String(box.dataset.tagihanId || box.value || '');
                var isSelected = selected.has(id);

                row.classList.add('tagihan-wa-row--selectable');
                row.classList.toggle('ui-dt-row--selected', isSelected);
                row.setAttribute('role', 'button');
                row.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                row.tabIndex = 0;
            });
        }

        function syncSelectAllState() {
            if (!selectAll) {
                return;
            }

            var checkboxes = table.querySelectorAll('.tagihan-wa-checkbox:not(:disabled)');
            var checkedCount = 0;
            checkboxes.forEach(function (box) {
                if (box.checked) {
                    checkedCount += 1;
                }
            });

            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
        }

        function updateSelectionBar() {
            var items = Array.from(selected.values());
            var total = items.reduce(function (sum, item) {
                return sum + Number(item.remaining || 0);
            }, 0);

            if (items.length === 0) {
                selectionBar?.classList.add('hidden');
                previewBtn.disabled = true;
                lockedSiswaId = null;
                syncSelectAllState();
                syncSelectableRows();
                return;
            }

            selectionBar?.classList.remove('hidden');
            var first = items[0];
            selectionSummary.textContent = items.length + ' tagihan · ' + first.siswaName + ' (NIS ' + first.siswaNis + ') · Total ' + formatRp(total);
            selectionPhone.textContent = 'Nomor WA wali: tersedia';
            previewBtn.disabled = false;
            syncSelectAllState();
        }

        function clearSelection() {
            selected.clear();
            lockedSiswaId = null;
            table.querySelectorAll('.tagihan-wa-checkbox').forEach(function (box) {
                box.checked = false;
            });
            updateSelectionBar();
            syncSelectableRows();
        }

        function toggleCheckbox(box, forceChecked) {
            var id = String(box.dataset.tagihanId || box.value || '');
            if (!id) {
                return;
            }

            var siswaId = String(box.dataset.siswaId || '');
            var shouldCheck = typeof forceChecked === 'boolean' ? forceChecked : box.checked;

            if (shouldCheck) {
                if (lockedSiswaId !== null && lockedSiswaId !== siswaId) {
                    box.checked = false;
                    if (window.showAlert) {
                        window.showAlert('Tagihan harus milik siswa yang sama.', 'warning');
                    }
                    return;
                }

                lockedSiswaId = siswaId;
                selected.set(id, {
                    id: id,
                    siswaId: siswaId,
                    siswaName: box.dataset.siswaName || '',
                    siswaNis: box.dataset.siswaNis || '',
                    remaining: Number(box.dataset.remaining || 0),
                });
                box.checked = true;
            } else {
                selected.delete(id);
                if (selected.size === 0) {
                    lockedSiswaId = null;
                }
                box.checked = false;
            }

            updateSelectionBar();
            syncSelectableRows();
        }

        function buildPayload(extra) {
            var payload = {
                tagihan_ids: Array.from(selected.keys()).map(function (id) {
                    return Number(id);
                }),
                random_template: templateMode?.value !== 'manual',
            };

            if (templateMode?.value === 'manual' && templateSelect?.value) {
                payload.template_id = Number(templateSelect.value);
                payload.random_template = false;
            }

            return Object.assign(payload, extra || {});
        }

        function loadTemplateOptions(kategori) {
            if (!templateSelect || !config.templateOptionsUrl) {
                return Promise.resolve();
            }

            var url = config.templateOptionsUrl + (kategori ? ('?kategori=' + encodeURIComponent(kategori)) : '');
            templateSelect.innerHTML = '<option value="">Memuat…</option>';

            return fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    var items = json?.data?.items || [];
                    templateSelect.innerHTML = '<option value="">Pilih template</option>';
                    items.forEach(function (item) {
                        var option = document.createElement('option');
                        option.value = String(item.id);
                        option.textContent = item.nama + ' (' + (item.kategori_label || item.kategori) + ')';
                        templateSelect.appendChild(option);
                    });
                })
                .catch(function () {
                    templateSelect.innerHTML = '<option value="">Gagal memuat template</option>';
                });
        }

        function requestPreview() {
            if (selected.size === 0) {
                return Promise.resolve(null);
            }

            return fetch(config.buildUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(buildPayload()),
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        return { ok: response.ok, json: json };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.json?.success) {
                        var message = result.json?.message || 'Gagal menyiapkan pesan WhatsApp.';
                        if (window.showAlert) {
                            window.showAlert(message, 'error');
                        }
                        throw new Error(message);
                    }

                    return result.json.data;
                });
        }

        function fillPreviewModal(data) {
            previewPayload = data;
            previewSiswa.textContent = data.siswa_name + ' · NIS ' + data.siswa_nis;
            previewPhone.textContent = data.phone_display || data.phone || '-';
            previewTotal.textContent = formatRp(data.total_remaining);
            previewTemplate.textContent = (data.template_nama || '-') + ' · ' + (data.kategori_label || data.kategori || '');
            previewMessage.value = data.message || '';
            loadTemplateOptions(data.kategori);
        }

        table.addEventListener('change', function (event) {
            var target = event.target;
            if (!target.classList.contains('tagihan-wa-checkbox')) {
                return;
            }
            toggleCheckbox(target);
        });

        table.addEventListener('click', function (event) {
            if (event.target.closest('.tagihan-wa-checkbox, a, button, label, select, textarea')) {
                return;
            }

            var row = event.target.closest('tbody tr');
            if (!row || !table.contains(row)) {
                return;
            }

            var box = row.querySelector('.tagihan-wa-checkbox');
            if (!box) {
                return;
            }

            toggleCheckbox(box, !box.checked);
        });

        table.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            var row = event.target.closest('tbody tr.tagihan-wa-row--selectable');
            if (!row || !table.contains(row)) {
                return;
            }

            var box = row.querySelector('.tagihan-wa-checkbox');
            if (!box) {
                return;
            }

            event.preventDefault();
            toggleCheckbox(box, !box.checked);
        });

        function onTableDraw() {
            table.querySelectorAll('.tagihan-wa-checkbox').forEach(function (box) {
                var id = String(box.dataset.tagihanId || box.value || '');
                box.checked = selected.has(id);
            });
            syncSelectAllState();
            syncSelectableRows();
        }

        table.addEventListener('draw.dt', onTableDraw);

        if (window.jQuery && window.jQuery.fn.DataTable) {
            window.jQuery(table).on('draw.dt', onTableDraw);
        }

        selectAll?.addEventListener('change', function () {
            var boxes = Array.from(table.querySelectorAll('.tagihan-wa-checkbox'));
            if (selectAll.checked) {
                var first = boxes.find(function (box) { return box.dataset.hasPhone === '1'; });
                if (!first) {
                    selectAll.checked = false;
                    return;
                }
                lockedSiswaId = String(first.dataset.siswaId || '');
                boxes.forEach(function (box) {
                    if (String(box.dataset.siswaId || '') === lockedSiswaId && box.dataset.hasPhone === '1') {
                        toggleCheckbox(box, true);
                    } else {
                        toggleCheckbox(box, false);
                    }
                });
            } else {
                clearSelection();
            }
        });

        clearBtn?.addEventListener('click', clearSelection);

        previewBtn?.addEventListener('click', function () {
            requestPreview()
                .then(function (data) {
                    if (!data) {
                        return;
                    }
                    fillPreviewModal(data);
                    if (window.openModal) {
                        window.openModal('tagihan-wa-preview-modal');
                    } else {
                        document.getElementById('tagihan-wa-preview-modal')?.classList.remove('hidden');
                    }
                })
                .catch(function () {});
        });

        templateMode?.addEventListener('change', function () {
            var manual = templateMode.value === 'manual';
            templatePickerWrap?.classList.toggle('hidden', !manual);
            if (!manual && previewPayload) {
                requestPreview().then(function (data) {
                    if (data) {
                        fillPreviewModal(data);
                    }
                }).catch(function () {});
            }
        });

        templateSelect?.addEventListener('change', function () {
            if (templateMode?.value !== 'manual' || !templateSelect.value) {
                return;
            }
            requestPreview().then(function (data) {
                if (data) {
                    fillPreviewModal(data);
                }
            }).catch(function () {});
        });

        openWaBtn?.addEventListener('click', function () {
            requestPreview()
                .then(function (data) {
                    if (!data?.whatsapp_url) {
                        return;
                    }
                    window.open(data.whatsapp_url, '_blank', 'noopener,noreferrer');
                })
                .catch(function () {});
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTagihanWaReminder);
    } else {
        initTagihanWaReminder();
    }
})();
