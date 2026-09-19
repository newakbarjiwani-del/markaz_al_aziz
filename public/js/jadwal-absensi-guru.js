(function () {
    var assignModal = document.getElementById('jadwal-absensi-guru-assign-modal');
    var assignForm = document.getElementById('jadwal-absensi-guru-assign-form');
    var assignHiddenInputs = document.getElementById('jadwal-absensi-guru-assign-hidden-inputs');
    var assignMeta = document.getElementById('jadwal-absensi-guru-assign-meta');
    var assignList = document.getElementById('jadwal-absensi-guru-assign-list');
    var assignEmpty = document.getElementById('jadwal-absensi-guru-assign-empty');
    var assignFilterEmpty = document.getElementById('jadwal-absensi-guru-assign-filter-empty');
    var assignSearch = document.getElementById('jadwal-absensi-guru-assign-search');
    var assignSelectAll = document.getElementById('jadwal-absensi-guru-assign-select-all');
    var assignVisibleCount = document.getElementById('jadwal-absensi-guru-assign-visible-count');
    var assignSelectedWrap = document.getElementById('jadwal-absensi-guru-assign-selected');
    var assignSelectedCount = document.getElementById('jadwal-absensi-guru-assign-selected-count');
    var assignSelectedList = document.getElementById('jadwal-absensi-guru-assign-selected-list');
    var assignSelectedEmpty = document.getElementById('jadwal-absensi-guru-assign-selected-empty');

    if (!assignModal || !assignForm) {
        return;
    }

    var allGurus = [];
    var selectedIds = new Set();

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function guruSearchText(guru) {
        return [guru.name, guru.nip, guru.jabatan].join(' ').toLowerCase();
    }

    function filteredGurus(term) {
        var query = String(term || '').trim().toLowerCase();

        if (!query) {
            return allGurus.slice();
        }

        return allGurus.filter(function (guru) {
            return guruSearchText(guru).indexOf(query) !== -1;
        });
    }

    function renderGuruRow(guru) {
        var checked = selectedIds.has(guru.id) ? ' checked' : '';
        var note = guru.other_jadwal
            ? '<span class="ml-2 text-xs text-amber-600 dark:text-amber-400">(pindah dari jadwal lain)</span>'
            : '';

        return '<label class="jadwal-assign-guru-row flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700" data-guru-id="' + guru.id + '">'
            + '<input type="checkbox" value="' + guru.id + '" class="jadwal-assign-guru-checkbox mt-1"' + checked + '>'
            + '<span class="min-w-0">'
            + '<span class="block font-medium text-slate-900 dark:text-white">' + escapeHtml(guru.name) + note + '</span>'
            + '<span class="block text-xs text-slate-500">' + escapeHtml(guru.nip || '-') + ' &bull; ' + escapeHtml(guru.jabatan || '-') + '</span>'
            + '</span>'
            + '</label>';
    }

    function syncFormInputs() {
        if (!assignHiddenInputs) {
            return;
        }

        assignHiddenInputs.innerHTML = Array.from(selectedIds).map(function (id) {
            return '<input type="hidden" name="guru_ids[]" value="' + id + '">';
        }).join('');
    }

    function updateSelectedSummary() {
        var selected = allGurus.filter(function (guru) {
            return selectedIds.has(guru.id);
        });

        assignSelectedCount.textContent = String(selected.length);

        if (!selected.length) {
            assignSelectedWrap.classList.add('hidden');
            assignSelectedList.innerHTML = '';
            assignSelectedEmpty.classList.remove('hidden');
            syncFormInputs();
            return;
        }

        assignSelectedWrap.classList.remove('hidden');
        assignSelectedEmpty.classList.add('hidden');
        assignSelectedList.innerHTML = selected.map(function (guru) {
            return '<span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-primary-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-primary-800">'
                + '<span>' + escapeHtml(guru.name) + '</span>'
                + '<button type="button" class="text-slate-400 hover:text-red-600" data-remove-guru="' + guru.id + '" aria-label="Hapus ' + escapeHtml(guru.name) + '">&times;</button>'
                + '</span>';
        }).join('');
        syncFormInputs();
    }

    function updateSelectAllState(visible) {
        if (!assignSelectAll || !visible.length) {
            if (assignSelectAll) {
                assignSelectAll.checked = false;
                assignSelectAll.indeterminate = false;
            }
            return;
        }

        var selectedVisible = visible.filter(function (guru) {
            return selectedIds.has(guru.id);
        }).length;

        assignSelectAll.checked = selectedVisible === visible.length;
        assignSelectAll.indeterminate = selectedVisible > 0 && selectedVisible < visible.length;
    }

    function renderAssignList() {
        var term = assignSearch ? assignSearch.value : '';
        var visible = filteredGurus(term);

        if (assignVisibleCount) {
            assignVisibleCount.textContent = visible.length
                ? '(' + visible.length + ' ditampilkan)'
                : '';
        }

        if (!allGurus.length) {
            assignList.innerHTML = '';
            assignEmpty.classList.remove('hidden');
            assignFilterEmpty.classList.add('hidden');
            updateSelectAllState([]);
            updateSelectedSummary();
            return;
        }

        assignEmpty.classList.add('hidden');

        if (!visible.length) {
            assignList.innerHTML = '';
            assignFilterEmpty.classList.remove('hidden');
            updateSelectAllState([]);
            return;
        }

        assignFilterEmpty.classList.add('hidden');
        assignList.innerHTML = visible.map(renderGuruRow).join('');
        updateSelectAllState(visible);
        updateSelectedSummary();
    }

    function resetAssignModal() {
        allGurus = [];
        selectedIds = new Set();

        if (assignSearch) {
            assignSearch.value = '';
        }

        if (assignSelectAll) {
            assignSelectAll.checked = false;
            assignSelectAll.indeterminate = false;
        }

        assignSelectedWrap.classList.add('hidden');
        assignSelectedList.innerHTML = '';
        assignSelectedEmpty.classList.remove('hidden');
        assignSelectedCount.textContent = '0';
        syncFormInputs();
    }

    function openAssignModal(button) {
        var gurusUrl = button.getAttribute('data-gurus-url');
        var assignUrl = button.getAttribute('data-assign-url');

        assignForm.action = assignUrl;
        resetAssignModal();
        assignList.innerHTML = '<p class="text-sm text-slate-500">Memuat daftar guru...</p>';
        assignEmpty.classList.add('hidden');
        assignFilterEmpty.classList.add('hidden');

        if (typeof window.openModal === 'function') {
            window.openModal('jadwal-absensi-guru-assign-modal');
        } else {
            assignModal.classList.remove('hidden');
        }

        fetch(gurusUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload.success) {
                    throw new Error('Gagal memuat daftar guru.');
                }

                var jadwal = payload.data.jadwal;
                allGurus = payload.data.gurus || [];

                selectedIds = new Set(
                    allGurus.filter(function (guru) { return guru.assigned; }).map(function (guru) { return guru.id; })
                );

                assignMeta.innerHTML = '<strong>' + escapeHtml(jadwal.name) + '</strong>'
                    + ' &mdash; ' + escapeHtml(jadwal.sekolah);

                renderAssignList();

                if (assignSearch) {
                    assignSearch.focus();
                }
            })
            .catch(function () {
                assignList.innerHTML = '<p class="text-sm text-red-600">Gagal memuat daftar guru.</p>';
            });
    }

    if (assignSearch) {
        assignSearch.addEventListener('input', function () {
            renderAssignList();
        });
    }

    if (assignSelectAll) {
        assignSelectAll.addEventListener('change', function () {
            var visible = filteredGurus(assignSearch ? assignSearch.value : '');

            visible.forEach(function (guru) {
                if (assignSelectAll.checked) {
                    selectedIds.add(guru.id);
                } else {
                    selectedIds.delete(guru.id);
                }
            });

            renderAssignList();
        });
    }

    assignList.addEventListener('change', function (event) {
        var checkbox = event.target.closest('.jadwal-assign-guru-checkbox');
        if (!checkbox) {
            return;
        }

        var guruId = parseInt(checkbox.value, 10);
        if (checkbox.checked) {
            selectedIds.add(guruId);
        } else {
            selectedIds.delete(guruId);
        }

        renderAssignList();
    });

    assignSelectedList.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-guru]');
        if (!button) {
            return;
        }

        event.preventDefault();
        selectedIds.delete(parseInt(button.getAttribute('data-remove-guru'), 10));
        renderAssignList();
    });

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-assign-jadwal-guru]');
        if (!button) {
            return;
        }

        event.preventDefault();
        openAssignModal(button);
    });
})();
