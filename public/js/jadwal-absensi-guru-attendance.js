(function () {
    if (window.__jadwalGuruAttendanceInitialized) return;
    window.__jadwalGuruAttendanceInitialized = true;

    var config = window.jadwalGuruAttendanceConfig || {};
    var rfidInput = document.getElementById('rfid-input');
    var manualGuru = document.getElementById('manual-guru');
    var manualStatus = document.getElementById('manual-status');
    var manualStatusWrap = document.getElementById('manual-status-wrap');
    var manualNotes = document.getElementById('manual-notes');
    var manualSubmit = document.getElementById('manual-submit');
    var boardEl = document.getElementById('attendance-board');
    var rfidPreview = document.getElementById('rfid-preview');
    var refreshBtn = document.getElementById('refresh-board');
    var activeMode = 'rfid';
    var rfidTimer = null;
    var rfidPending = false;
    var submitPending = false;
    var boardCache = [];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatTime(value) {
        if (!value) return '-';
        return String(value).slice(0, 5);
    }

    function formatStatusPulang(value) {
        if (!value) return '-';
        return value.replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    function setMode(mode) {
        activeMode = mode;
        document.querySelectorAll('[data-attendance-mode]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-attendance-mode') === mode);
        });
        document.getElementById('panel-rfid')?.classList.toggle('hidden', mode !== 'rfid');
        document.getElementById('panel-manual')?.classList.toggle('hidden', mode !== 'manual');
        if (mode === 'rfid') {
            rfidInput?.focus();
        }
    }

    function updateSummary(summary) {
        if (!summary) return;
        var masuk = document.getElementById('summary-masuk');
        var pulang = document.getElementById('summary-pulang');
        var belum = document.getElementById('summary-belum');
        var total = document.getElementById('summary-total');
        if (masuk) masuk.textContent = String(summary.masuk ?? summary.hadir ?? 0);
        if (pulang) pulang.textContent = String(summary.pulang ?? 0);
        if (belum) belum.textContent = String(summary.belum ?? 0);
        if (total) total.textContent = String(summary.total ?? 0);
    }

    function statusLabel(value) {
        var labels = {
            hadir: 'Hadir',
            terlambat: 'Terlambat',
            izin: 'Izin',
            sakit: 'Sakit',
            cuti: 'Cuti',
            alpha: 'Alpha',
        };
        return labels[value] || (value ? value.charAt(0).toUpperCase() + value.slice(1) : '-');
    }

    function initialsFromName(name) {
        return String(name || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(function (part) { return part.charAt(0).toUpperCase(); })
            .join('') || '?';
    }

    function statusPillClass(status, hasRecord) {
        if (!hasRecord) return 'status-pending';
        if (status === 'terlambat') return 'status-terlambat';
        if (status === 'alpha') return 'status-alpha';
        if (status === 'izin' || status === 'sakit' || status === 'cuti') return 'status-absent';
        return 'status-hadir';
    }

    function rowClass(row) {
        if (row.is_complete) return 'is-done';
        if (row.has_masuk) return 'is-partial';
        return 'is-pending';
    }

    function renderBoardRow(row) {
        var stateClass = rowClass(row);
        var pillClass = statusPillClass(row.status, row.has_record);
        var statusText = row.has_record ? statusLabel(row.status) : 'Belum masuk';
        var timesHtml = '';

        if (row.has_record) {
            if (row.is_absent_only) {
                timesHtml = '<div class="attendance-terminal__board-times">'
                    + '<span class="attendance-terminal__time-chip attendance-terminal__time-chip--muted">Manual · tanpa jam kehadiran</span></div>';
            } else if (row.has_masuk) {
                timesHtml = '<div class="attendance-terminal__board-times">'
                    + '<span class="attendance-terminal__time-chip">Masuk ' + escapeHtml(formatTime(row.jam_masuk)) + ' · ' + escapeHtml((row.method || '-').toUpperCase()) + '</span>';
                if (row.has_pulang) {
                    timesHtml += '<span class="attendance-terminal__time-chip">Pulang ' + escapeHtml(formatTime(row.jam_keluar)) + ' · ' + escapeHtml(formatStatusPulang(row.status_pulang)) + '</span>';
                } else {
                    timesHtml += '<span class="attendance-terminal__time-chip attendance-terminal__time-chip--warn">Belum pulang</span>';
                }
                timesHtml += '</div>';
            }
        }

        return '<div class="attendance-terminal__board-row ' + stateClass + '" data-guru-row="' + row.id + '">'
            + '<div class="attendance-terminal__board-avatar" aria-hidden="true">' + escapeHtml(initialsFromName(row.name)) + '</div>'
            + '<div class="attendance-terminal__board-body">'
            + '<div class="attendance-terminal__board-head">'
            + '<div class="min-w-0"><p class="attendance-terminal__board-name">' + escapeHtml(row.name) + '</p>'
            + '<p class="attendance-terminal__board-meta">' + escapeHtml(row.nip || '-') + ' · ' + escapeHtml(row.jabatan || '-') + '</p></div>'
            + '<span class="attendance-terminal__status-pill ' + pillClass + '">' + escapeHtml(statusText) + '</span>'
            + '</div>' + timesHtml + '</div></div>';
    }

    function renderBoard(board) {
        boardCache = board || [];
        if (!boardEl) return;

        if (!board || !board.length) {
            boardEl.innerHTML = '<p class="attendance-terminal__empty">Belum ada guru pada jadwal ini. Atur guru terlebih dahulu.</p>';
            return;
        }

        boardEl.innerHTML = board.map(renderBoardRow).join('');

        syncManualGuruOptions();
    }

    function syncManualGuruOptions() {
        if (!manualGuru) return;
        var current = manualGuru.value;
        manualGuru.innerHTML = '<option value="">Pilih guru</option>' + boardCache
            .filter(function (row) { return !row.is_complete; })
            .map(function (row) {
                return '<option value="' + row.id + '">' + escapeHtml(row.name) + ' · ' + escapeHtml(row.nip || '-') + '</option>';
            }).join('');
        if (current && manualGuru.querySelector('option[value="' + current + '"]')) {
            manualGuru.value = current;
        }
        toggleManualStatus();
    }

    function toggleManualStatus() {
        if (!manualGuru || !manualStatusWrap) return;
        var row = boardCache.find(function (item) { return String(item.id) === String(manualGuru.value); });
        var isCheckout = row && row.has_masuk && !row.has_pulang && !row.is_absent_only;
        manualStatusWrap.classList.toggle('hidden', !!isCheckout);
    }

    async function refreshBoard() {
        if (!config.referencesUrl) return;
        var res = await fetch(config.referencesUrl, { headers: { Accept: 'application/json' } });
        var json = await res.json();
        if (!json.success) return;
        renderBoard(json.data.board);
        updateSummary(json.data.summary);
    }

    function clearRfidInput() {
        if (!rfidInput) return;
        rfidInput.value = '';
        rfidPreview?.classList.add('hidden');
        rfidInput.focus();
    }

    function findBoardRowByRfid(uid) {
        var normalized = String(uid || '').trim();
        if (!normalized) return null;

        return boardCache.find(function (row) {
            return String(row.rfid_uid || '').trim() === normalized;
        }) || null;
    }

    async function resolveBoardRowByRfid(uid) {
        var row = findBoardRowByRfid(uid);
        if (row) return row;

        await refreshBoard();

        return findBoardRowByRfid(uid);
    }

    function resolveRfidAction(row) {
        if (!row) return null;
        if (!row.has_masuk) return 'masuk';
        if (row.has_masuk && !row.has_pulang) return 'pulang';
        return 'selesai';
    }

    function buildRfidConfirmOptions(row, action) {
        var detail = [
            { label: 'Nama', value: row.name || '-' },
            { label: 'NIP', value: row.nip || '-' },
            { label: 'Jabatan', value: row.jabatan || '-' },
        ];

        if (action === 'masuk') {
            return {
                title: 'Konfirmasi Absensi Masuk',
                message: 'Catat absensi masuk untuk guru berikut?',
                tone: 'primary',
                confirmText: 'Ya, Catat Masuk',
                confirmIcon: 'ti-login-2',
                headerIcon: 'ti-login-2',
                detail: detail,
            };
        }

        detail.push({ label: 'Masuk hari ini', value: formatTime(row.jam_masuk) + ' (' + String(row.status || 'hadir') + ')' });

        return {
            title: 'Konfirmasi Absensi Pulang',
            message: 'Guru ini sudah tercatat masuk. Catat absensi pulang sekarang?',
            tone: 'primary',
            confirmText: 'Ya, Catat Pulang',
            confirmIcon: 'ti-logout-2',
            headerIcon: 'ti-logout-2',
            detail: detail,
        };
    }

    async function confirmRfidAttendance(row, action) {
        if (typeof window.showConfirm !== 'function') {
            return window.confirm(action === 'masuk'
                ? 'Catat absensi masuk untuk ' + row.name + '?'
                : 'Catat absensi pulang untuk ' + row.name + '?');
        }

        return window.showConfirm(buildRfidConfirmOptions(row, action));
    }

    async function submitAttendance(payload) {
        if (submitPending) return;
        submitPending = true;
        if (manualSubmit) {
            manualSubmit.disabled = true;
        }
        var res = await fetch(config.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || '',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
        try {
            var json = await res.json();
            if (!res.ok || !json.success) {
                if (typeof window.showAlert === 'function') {
                    window.showAlert(json.message || 'Gagal menyimpan absensi.', 'error');
                }
                return;
            }

            if (typeof window.showToast === 'function') {
                window.showToast(json.message || 'Absensi tersimpan.', 'success');
            }

            renderBoard(json.data.board);
            updateSummary(json.data.summary);

            if (payload.method === 'rfid' && rfidInput) {
                clearRfidInput();
            }
        } finally {
            submitPending = false;
            if (manualSubmit) {
                manualSubmit.disabled = false;
            }
        }
    }

    async function submitRfid() {
        if (rfidPending || config.rfidDisabled) return;

        var uid = (rfidInput?.value || '').trim();
        if (!uid) return;

        var row = await resolveBoardRowByRfid(uid);
        if (!row) {
            if (typeof window.showAlert === 'function') {
                await window.showAlert({
                    title: 'Kartu Tidak Dikenali',
                    message: 'UID kartu tidak terdaftar pada jadwal absensi ini.',
                    variant: 'warning',
                });
            }
            clearRfidInput();
            return;
        }

        var action = resolveRfidAction(row);
        if (action === 'selesai') {
            if (typeof window.showAlert === 'function') {
                await window.showAlert({
                    title: 'Absensi Sudah Lengkap',
                    message: row.name + ' sudah menyelesaikan absensi masuk dan pulang hari ini.',
                    variant: 'info',
                    detail: [
                        { label: 'Masuk', value: formatTime(row.jam_masuk) },
                        { label: 'Pulang', value: formatTime(row.jam_keluar) },
                    ],
                });
            }
            clearRfidInput();
            return;
        }

        var confirmed = await confirmRfidAttendance(row, action);
        if (!confirmed) {
            clearRfidInput();
            return;
        }

        rfidPending = true;
        try {
            await submitAttendance({ method: 'rfid', rfid_uid: uid });
        } finally {
            rfidPending = false;
        }
    }

    async function submitManual() {
        var guruId = manualGuru?.value;
        if (!guruId) {
            if (typeof window.showAlert === 'function') {
                window.showAlert('Pilih guru terlebih dahulu.', 'warning');
            }
            return;
        }

        var payload = {
            method: 'manual',
            guru_id: parseInt(guruId, 10),
        };

        var row = boardCache.find(function (item) { return String(item.id) === String(guruId); });
        var isCheckout = row && row.has_masuk && !row.has_pulang && !row.is_absent_only;

        if (!isCheckout && manualStatus?.value) {
            payload.status = manualStatus.value;
        }
        if (manualNotes?.value?.trim()) {
            payload.notes = manualNotes.value.trim();
        }

        await submitAttendance(payload);
    }

    document.querySelectorAll('[data-attendance-mode]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setMode(btn.getAttribute('data-attendance-mode'));
        });
    });

    rfidInput?.addEventListener('input', function () {
        clearTimeout(rfidTimer);
        rfidTimer = setTimeout(submitRfid, 280);
    });

    rfidInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(rfidTimer);
            submitRfid();
        }
    });

    manualGuru?.addEventListener('change', toggleManualStatus);
    manualSubmit?.addEventListener('click', submitManual);
    refreshBtn?.addEventListener('click', refreshBoard);

    if (config.initialBoard) {
        renderBoard(config.initialBoard);
        updateSummary(config.initialSummary);
    }

    setMode(config.rfidDisabled ? 'manual' : 'rfid');
})();
