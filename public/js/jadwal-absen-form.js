(function () {
    var form = document.querySelector('[data-jadwal-absen-form]');
    if (!form) return;

    var config = window.jadwalAbsenFormConfig || {};
    var sekolahSelect = document.getElementById('jadwal-sekolah-id');
    var kelasPanel = document.getElementById('jadwal-kelas-panel');
    var kelasEmpty = document.getElementById('jadwal-kelas-empty');
    var allSchoolsHint = document.getElementById('jadwal-all-schools-hint');
    var selectAllKelas = document.getElementById('jadwal-select-all-kelas');
    var studentCountEl = document.getElementById('jadwal-student-count');
    var slotTemplate = document.getElementById('jadwal-slot-template');
    var previewUrl = form.dataset.previewUrl;
    var redirectUrl = form.dataset.redirectUrl;

    function isAllSchoolsMode() {
        return getSekolahId() === 'all';
    }

    function getSekolahId() {
        if (!sekolahSelect) return '';
        var value = sekolahSelect.value || '';
        if (!value && sekolahSelect.selectedIndex >= 0) {
            value = sekolahSelect.options[sekolahSelect.selectedIndex]?.value || '';
        }
        return value;
    }

    function getAssignmentType() {
        var checked = form.querySelector('[name="assignment_type"]:checked');
        return checked ? checked.value : 'kelas';
    }

    function pelajaranOptions(sekolahId) {
        if (sekolahId === 'all') {
            return config.pelajaranList || [];
        }

        return (config.pelajaranList || []).filter(function (row) {
            return !sekolahId || row.sekolah_id == null || String(row.sekolah_id) === String(sekolahId);
        });
    }

    function guruOptions(sekolahId) {
        if (sekolahId === 'all') {
            return config.guruList || [];
        }

        return (config.guruList || []).filter(function (row) {
            return !sekolahId || row.sekolah_id == null || String(row.sekolah_id) === String(sekolahId);
        });
    }

    function fillSelect(select, options, selected) {
        if (!select) return;
        var label = select.classList.contains('jadwal-slot-pelajaran') ? 'Pilih pelajaran' : 'Pilih guru';
        select.innerHTML = '<option value="">' + label + '</option>';
        options.forEach(function (row) {
            var option = document.createElement('option');
            option.value = row.id;
            option.textContent = row.name;
            if (selected && String(selected) === String(row.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function applyNameAttributes(row, day, index) {
        row.querySelectorAll('[data-name-template]').forEach(function (el) {
            var template = el.getAttribute('data-name-template');
            el.name = template.replace('__DAY__', day).replace('__INDEX__', index);
        });
    }

    function updateDayEmptyState(day) {
        var list = form.querySelector('.jadwal-slots-list[data-day="' + day + '"]');
        var empty = list?.closest('.jadwal-day-body')?.querySelector('.jadwal-day-empty');
        if (!empty || !list) return;
        empty.classList.toggle('hidden', list.children.length > 0);
    }

    function addSlot(day, slotData) {
        if (!slotTemplate) return;

        var list = form.querySelector('.jadwal-slots-list[data-day="' + day + '"]');
        if (!list) return;

        var index = list.children.length;
        var node = slotTemplate.content.firstElementChild.cloneNode(true);
        applyNameAttributes(node, day, index);

        var sekolahId = getSekolahId();
        fillSelect(node.querySelector('.jadwal-slot-pelajaran'), pelajaranOptions(sekolahId), slotData?.pelajaran_id);
        fillSelect(node.querySelector('.jadwal-slot-guru'), guruOptions(sekolahId), slotData?.guru_id);

        if (slotData?.time_start) node.querySelector('.jadwal-slot-start').value = slotData.time_start;
        if (slotData?.time_end) node.querySelector('.jadwal-slot-end').value = slotData.time_end;
        if (slotData?.tolerance_minutes) node.querySelector('.jadwal-slot-tolerance').value = slotData.tolerance_minutes;

        list.appendChild(node);
        updateDayEmptyState(day);
    }

    function reindexSlots(day) {
        var list = form.querySelector('.jadwal-slots-list[data-day="' + day + '"]');
        if (!list) return;
        Array.from(list.children).forEach(function (row, index) {
            applyNameAttributes(row, day, index);
        });
    }

    function setDayActive(day, active) {
        var card = form.querySelector('.jadwal-day-card[data-day="' + day + '"]');
        if (!card) return;

        var toggle = card.querySelector('.jadwal-day-toggle');
        var body = card.querySelector('.jadwal-day-body');
        var addBtn = card.querySelector('.jadwal-add-slot');

        if (toggle) toggle.checked = !!active;
        body?.classList.toggle('hidden', !active);
        addBtn?.classList.toggle('hidden', !active);
        card.classList.toggle('ring-2', !!active);
        card.classList.toggle('ring-primary-500/40', !!active);

        card.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.classList.contains('jadwal-day-toggle')) return;
            el.disabled = !active;
        });
    }

    function visibleKelasInputs() {
        return Array.from(form.querySelectorAll('.jadwal-kelas-item')).filter(function (item) {
            return item.style.display !== 'none';
        }).map(function (item) {
            return item.querySelector('input[type="checkbox"]');
        }).filter(Boolean);
    }

    function syncSelectAllKelasState() {
        if (!selectAllKelas) return;
        var inputs = visibleKelasInputs();
        if (inputs.length === 0) {
            selectAllKelas.checked = false;
            selectAllKelas.indeterminate = false;
            selectAllKelas.disabled = true;
            return;
        }

        selectAllKelas.disabled = false;
        var checkedCount = inputs.filter(function (input) { return input.checked; }).length;
        selectAllKelas.checked = checkedCount > 0 && checkedCount === inputs.length;
        selectAllKelas.indeterminate = checkedCount > 0 && checkedCount < inputs.length;
    }

    function syncAssignmentFieldState() {
        var assignmentType = getAssignmentType();
        var isKelas = assignmentType === 'kelas';

        form.querySelectorAll('[name="kelas_ids[]"]').forEach(function (input) {
            var item = input.closest('.jadwal-kelas-item');
            var visible = isKelas && item && item.style.display !== 'none';
            input.disabled = !visible;
            if (!visible) input.checked = false;
        });

        if (selectAllKelas) {
            selectAllKelas.disabled = !isKelas || visibleKelasInputs().length === 0;
        }

        syncSelectAllKelasState();
    }

    function syncAssignmentPanels() {
        var assignmentType = getAssignmentType();

        if (kelasPanel) {
            kelasPanel.style.display = assignmentType === 'kelas' ? '' : 'none';
        }

        if (allSchoolsHint) {
            allSchoolsHint.classList.toggle('hidden', !(assignmentType === 'kelas' && isAllSchoolsMode()));
        }
    }

    function syncKelasPanel() {
        syncAssignmentPanels();

        var isKelas = getAssignmentType() === 'kelas';
        var sekolahId = String(getSekolahId() || '');
        var showAllSchools = sekolahId === 'all';
        var visibleCount = 0;

        form.querySelectorAll('.jadwal-kelas-item').forEach(function (item) {
            var itemSekolahId = String(item.dataset.sekolahId || '');
            var match = showAllSchools || (sekolahId !== '' && itemSekolahId === sekolahId);
            item.style.display = match ? '' : 'none';
            if (!match) {
                var input = item.querySelector('input[type="checkbox"]');
                if (input) {
                    input.checked = false;
                    input.disabled = true;
                }
            } else {
                visibleCount += 1;
            }
        });

        if (kelasEmpty) {
            kelasEmpty.classList.toggle('hidden', visibleCount > 0 || !isKelas);
        }

        syncAssignmentFieldState();
        refreshStudentPreview();
    }

    function refreshSlotSelects() {
        var sekolahId = getSekolahId();
        form.querySelectorAll('.jadwal-slot-row').forEach(function (row) {
            var pelajaranSelect = row.querySelector('.jadwal-slot-pelajaran');
            var guruSelect = row.querySelector('.jadwal-slot-guru');
            var pelajaranValue = pelajaranSelect?.value;
            var guruValue = guruSelect?.value;
            fillSelect(pelajaranSelect, pelajaranOptions(sekolahId), pelajaranValue);
            fillSelect(guruSelect, guruOptions(sekolahId), guruValue);
        });
    }

    function handleSekolahChanged() {
        syncKelasPanel();
        refreshSlotSelects();
    }

    async function refreshStudentPreview() {
        if (!studentCountEl || !previewUrl) return;

        var assignmentType = getAssignmentType();
        var sekolahId = getSekolahId();

        if (!sekolahId && assignmentType === 'kelas') {
            studentCountEl.textContent = '-';
            return;
        }

        var kelasIds = Array.from(form.querySelectorAll('[name="kelas_ids[]"]:checked')).map(function (el) {
            return el.value;
        });

        try {
            var response = await fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    sekolah_id: sekolahId === 'all' ? 'all' : sekolahId,
                    assignment_type: assignmentType,
                    kelas_ids: kelasIds,
                }),
            });
            var json = await response.json();
            studentCountEl.textContent = json.data?.count ?? '0';
        } catch (e) {
            studentCountEl.textContent = '-';
        }
    }

    function loadInitialDays() {
        var days = config.initialDays || {};
        Object.keys(days).forEach(function (day) {
            var dayData = days[day];
            setDayActive(day, !!dayData.active);
            (dayData.slots || []).forEach(function (slot) {
                addSlot(day, slot);
            });
        });
    }

    form.querySelectorAll('.jadwal-day-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            var day = toggle.dataset.day;
            setDayActive(day, toggle.checked);
            if (toggle.checked) {
                var list = form.querySelector('.jadwal-slots-list[data-day="' + day + '"]');
                if (list && list.children.length === 0) {
                    addSlot(day);
                }
            }
        });
    });

    form.querySelectorAll('.jadwal-add-slot').forEach(function (btn) {
        btn.addEventListener('click', function () {
            addSlot(btn.dataset.day);
        });
    });

    form.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('.jadwal-remove-slot');
        if (!removeBtn) return;

        var row = removeBtn.closest('.jadwal-slot-row');
        var list = row?.parentElement;
        var day = list?.dataset.day;
        row?.remove();
        if (day) {
            reindexSlots(day);
            updateDayEmptyState(day);
        }
    });

    form.querySelectorAll('[name="assignment_type"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            syncKelasPanel();
        });
    });

    selectAllKelas?.addEventListener('change', function () {
        visibleKelasInputs().forEach(function (input) {
            input.checked = selectAllKelas.checked;
        });
        syncSelectAllKelasState();
        refreshStudentPreview();
    });

    sekolahSelect?.addEventListener('change', handleSekolahChanged);
    sekolahSelect?.addEventListener('input', handleSekolahChanged);
    sekolahSelect?.addEventListener('blur', handleSekolahChanged);
    sekolahSelect?.addEventListener('select2:select', handleSekolahChanged);
    sekolahSelect?.addEventListener('select2:clear', handleSekolahChanged);
    sekolahSelect?.addEventListener('select2:close', handleSekolahChanged);
    if (window.jQuery && sekolahSelect) {
        window.jQuery(sekolahSelect).on('select2:select select2:clear select2:close change', handleSekolahChanged);
    }

    if (sekolahSelect && window.MutationObserver) {
        var sekolahObserver = new MutationObserver(function () {
            handleSekolahChanged();
        });
        sekolahObserver.observe(sekolahSelect, {
            attributes: true,
            childList: true,
            subtree: true,
        });
    }

    form.addEventListener('change', function (event) {
        if (event.target.matches('[name="kelas_ids[]"]')) {
            syncSelectAllKelasState();
            refreshStudentPreview();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!(await window.validateFormWithDialog?.(form))) {
            return;
        }

        syncAssignmentFieldState();
        form.querySelectorAll('.jadwal-day-card').forEach(function (card) {
            var active = card.querySelector('.jadwal-day-toggle')?.checked;
            card.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.classList.contains('jadwal-day-toggle')) return;
                el.disabled = !active;
            });
        });

        var submitBtn = form.querySelector('[type="submit"]');
        var originalText = submitBtn?.innerHTML;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Menyimpan...';
        }

        form.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.border-red-500').forEach(function (el) { el.classList.remove('border-red-500'); });

        var formData = new FormData(form);

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });
            var raw = await response.text();
            var data = null;
            try {
                data = raw ? JSON.parse(raw) : null;
            } catch (parseError) {
                throw new Error('Server mengembalikan respons tidak valid. Coba muat ulang halaman.');
            }

            if (!response.ok) {
                if (data.errors) {
                    await window.showAlert?.({
                        title: 'Validasi Gagal',
                        message: data.message || 'Periksa kembali data jadwal absen.',
                        items: Object.values(data.errors).flat(),
                        variant: 'danger',
                    });
                } else {
                    await window.showAlert?.({
                        title: 'Gagal',
                        message: data.message || 'Terjadi kesalahan.',
                        variant: 'danger',
                    });
                }
                return;
            }

            window.showToast?.(data.message || 'Jadwal absen berhasil disimpan.', 'success');
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        } catch (err) {
            await window.showAlert?.({
                title: 'Koneksi Gagal',
                message: err.message || 'Tidak dapat terhubung ke server. Coba lagi.',
                variant: 'danger',
            });
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    });

    loadInitialDays();
    handleSekolahChanged();
    setTimeout(syncKelasPanel, 0);
    setTimeout(syncKelasPanel, 250);
})();
