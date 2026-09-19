(function () {
    var config = window.portalGuruAbsensiConfig || {};
    var activeSchedule = null;
    var activeMode = null;

    var schedulePicker = document.getElementById('schedule-picker');
    var workspace = document.getElementById('attendance-workspace');
    var activeTitle = document.getElementById('active-schedule-title');
    var activeMeta = document.getElementById('active-schedule-meta');
    var todayCountEl = document.getElementById('today-attendance-count');
    var guruAttendanceStatusText = document.getElementById('guru-attendance-status-text');
    var guruAttendanceBadge = document.getElementById('guru-attendance-badge');
    var guruAttendanceManualBtn = document.getElementById('guru-attendance-manual');
    var guruAttendanceRfidBtn = document.getElementById('guru-attendance-rfid');
    var guruRfidInput = document.getElementById('guru-rfid-input');
    var guruAttendanceState = null;
    var sessionExemptionToggle = document.getElementById('session-exemption-toggle');
    var sessionExemptionRevoke = document.getElementById('session-exemption-revoke');
    var sessionExemptionBanner = document.getElementById('session-exemption-banner');
    var sessionExemptionBannerText = document.getElementById('session-exemption-banner-text');
    var sessionExemptionState = { active: false, reason: null };

    var rfidInput = document.getElementById('rfid-input');
    var manualTableBody = document.getElementById('manual-table-body');
    var manualCheckAll = document.getElementById('manual-check-all');
    var manualBulkStatus = document.getElementById('manual-bulk-status');
    var manualSubmitSelected = document.getElementById('manual-submit-selected');
    var manualBulkControls = document.getElementById('manual-bulk-controls');
    var manualThCheck = document.getElementById('manual-th-check');
    var manualThInput = document.getElementById('manual-th-input');
    var manualThAction = document.getElementById('manual-th-action');
    var manualSummaryTotal = document.getElementById('manual-summary-total');
    var manualSummaryHadir = document.getElementById('manual-summary-hadir');
    var manualSummaryBelum = document.getElementById('manual-summary-belum');
    var manualStudentsCache = [];
    var manualStudentsFiltered = [];
    var studentFilterBar = document.getElementById('student-filter-bar');
    var studentFilterSearch = document.getElementById('student-filter-search');
    var studentFilterSekolahWrap = document.getElementById('student-filter-sekolah-wrap');
    var studentFilterSekolah = document.getElementById('student-filter-sekolah');
    var studentFilterKelas = document.getElementById('student-filter-kelas');
    var studentFilterStatus = document.getElementById('student-filter-status');
    var studentFilterMeta = document.getElementById('student-filter-meta');
    var studentFilterReset = document.getElementById('student-filter-reset');
    var studentFilterSelectUnmarked = document.getElementById('student-filter-select-unmarked');
    var studentPerizinanBanner = document.getElementById('student-perizinan-banner');
    var perizinanBannerTitle = document.getElementById('perizinan-banner-title');
    var perizinanBannerDetail = document.getElementById('perizinan-banner-detail');
    var perizinanBannerApproveBtn = document.getElementById('perizinan-banner-approve-btn');
    var hasShownPerizinanPopupForSlot = {};
    var showSekolahInRows = false;
    var searchDebounceTimer = null;
    var studentsLoading = false;
    var studentLoadStatus = document.getElementById('student-load-status');
    var studentLoadStatusText = document.getElementById('student-load-status-text');
    var studentLoadingPanel = document.getElementById('student-loading-panel');
    var studentLoadingTitle = document.getElementById('student-loading-title');
    var studentLoadingDetail = document.getElementById('student-loading-detail');
    var studentListCard = document.getElementById('student-list-card');
    var studentListBody = document.getElementById('student-list-body');
    var modeSwitcher = document.getElementById('mode-switcher');
    var faceState = {
        stream: null,
        detectActive: false,
        rafId: null,
        lastDetectAt: 0,
        matcher: null,
        references: [],
        cooldown: new Map(),
        modalCooldown: new Map(),
        modelsReady: false,
        matcherReady: false,
        intervalMs: 480,
        threshold: 0.5,
    };

    function setStudentsLoading(isLoading, detail) {
        studentsLoading = !!isLoading;

        // Tailwind v4: last conflicting display utility in the class list wins.
        // Never leave `hidden` + `flex`/`inline-flex` on at the same time.
        if (studentLoadStatus) {
            studentLoadStatus.classList.toggle('hidden', !studentsLoading);
            studentLoadStatus.classList.toggle('inline-flex', studentsLoading);
        }
        if (studentLoadStatusText) {
            studentLoadStatusText.textContent = detail?.badge || 'Memuat siswa...';
        }
        if (studentLoadingPanel) {
            studentLoadingPanel.classList.toggle('hidden', !studentsLoading);
            studentLoadingPanel.classList.toggle('flex', studentsLoading);
            studentLoadingPanel.setAttribute('aria-hidden', studentsLoading ? 'false' : 'true');
        }
        if (studentLoadingTitle) {
            studentLoadingTitle.textContent = detail?.title || 'Memuat daftar siswa';
        }
        if (studentLoadingDetail) {
            studentLoadingDetail.textContent = detail?.detail || 'Mohon tunggu, data sedang diambil dari server...';
        }
        if (studentListCard) {
            studentListCard.classList.toggle('opacity-90', studentsLoading);
            studentListCard.setAttribute('aria-busy', studentsLoading ? 'true' : 'false');
        }
        if (studentListBody) {
            studentListBody.classList.toggle('pointer-events-none', studentsLoading);
            studentListBody.classList.toggle('select-none', studentsLoading);
        }
        if (modeSwitcher) {
            modeSwitcher.classList.toggle('pointer-events-none', studentsLoading);
            modeSwitcher.classList.toggle('opacity-60', studentsLoading);
        }
        if (manualBulkControls) {
            manualBulkControls.classList.toggle('pointer-events-none', studentsLoading);
            manualBulkControls.classList.toggle('opacity-60', studentsLoading);
        }
        if (manualTableBody && studentsLoading) {
            manualTableBody.innerHTML = '<tr><td colspan="5" class="px-3 py-10 text-center text-slate-500">'
                + '<span class="inline-flex flex-col items-center gap-2">'
                + '<i class="ti ti-loader-2 animate-spin text-2xl text-primary-600 dark:text-primary-400" aria-hidden="true"></i>'
                + '<span class="text-sm font-medium text-slate-700 dark:text-slate-200">' + (detail?.title || 'Memuat daftar siswa') + '</span>'
                + '<span class="text-xs text-slate-500">' + (detail?.detail || 'Mohon tunggu...') + '</span>'
                + '</span></td></tr>';
        }
        if (manualSummaryTotal && studentsLoading) {
            manualSummaryTotal.textContent = '…';
            if (manualSummaryHadir) manualSummaryHadir.textContent = '…';
            if (manualSummaryBelum) manualSummaryBelum.textContent = '…';
        }
    }

    function updateSessionExemptionUi(exemption) {
        exemption = exemption || {};
        sessionExemptionState.active = !!exemption.active;
        sessionExemptionState.reason = exemption.reason || null;

        if (sessionExemptionBanner) {
            sessionExemptionBanner.classList.toggle('hidden', !sessionExemptionState.active);
        }
        if (sessionExemptionBannerText && sessionExemptionState.active) {
            sessionExemptionBannerText.textContent = sessionExemptionState.reason
                ? ('Alasan: ' + sessionExemptionState.reason + '. Santri tidak dihitung alpha di rekap presensi.')
                : 'Sesi ini ditandai tidak diabsen. Santri tidak dihitung alpha di rekap.';
        }
        if (sessionExemptionToggle) {
            sessionExemptionToggle.classList.toggle('hidden', sessionExemptionState.active);
        }
        if (sessionExemptionRevoke) {
            sessionExemptionRevoke.classList.toggle('hidden', !sessionExemptionState.active);
        }
    }

    async function markSessionExempt() {
        if (!activeSchedule || !config.sessionExemptionStoreUrl) return;

        var reason = window.prompt('Alasan sesi tidak diabsen (min. 3 karakter):', 'Kegiatan diganti, tidak diabsen');
        if (reason === null) return;
        reason = String(reason).trim();
        if (reason.length < 3) {
            window.showToast?.('Alasan minimal 3 karakter.', 'warning');
            return;
        }

        var confirmed = true;
        if (typeof window.showConfirm === 'function') {
            confirmed = await window.showConfirm({
                title: 'Tandai sesi tidak diabsen?',
                message: 'Santri tidak akan dihitung alpha untuk sesi ini hari ini.',
                detail: [
                    { label: 'Jadwal', value: activeSchedule.pelajaran || activeSchedule.jadwal_name || '-' },
                    { label: 'Alasan', value: reason },
                ],
                tone: 'warning',
                confirmText: 'Ya, tandai',
            });
        }
        if (!confirmed) return;

        try {
            var res = await fetch(config.sessionExemptionStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    jadwal_absen_slot_id: activeSchedule.id,
                    reason: reason,
                }),
            });
            var json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal menandai pengecualian sesi');
            }
            window.showToast?.(json.message || 'Sesi ditandai tidak diabsen.', 'success');
            updateSessionExemptionUi(json.data?.session_exemption || { active: true, reason: reason });
        } catch (err) {
            window.showToast?.(err.message || 'Gagal menandai pengecualian sesi.', 'error');
        }
    }

    async function revokeSessionExempt() {
        if (!activeSchedule || !config.sessionExemptionDestroyUrl) return;

        var confirmed = true;
        if (typeof window.showConfirm === 'function') {
            confirmed = await window.showConfirm({
                title: 'Batalkan pengecualian sesi?',
                message: 'Sesi absensi kembali dihitung normal. Alpha dapat muncul jika tidak diabsen.',
                tone: 'warning',
                confirmText: 'Ya, batalkan',
            });
        }
        if (!confirmed) return;

        try {
            var res = await fetch(config.sessionExemptionDestroyUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    jadwal_absen_slot_id: activeSchedule.id,
                }),
            });
            var json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal membatalkan pengecualian sesi');
            }
            window.showToast?.(json.message || 'Pengecualian sesi dibatalkan.', 'success');
            updateSessionExemptionUi({ active: false, reason: null });
        } catch (err) {
            window.showToast?.(err.message || 'Gagal membatalkan pengecualian sesi.', 'error');
        }
    }

    function selectSchedule(schedule) {
        activeSchedule = schedule;
        if (activeTitle) activeTitle.textContent = schedule.jadwal_name;
        if (activeMeta) {
            activeMeta.textContent = [
                schedule.pelajaran,
                schedule.day_label,
                schedule.time_start + '–' + schedule.time_end,
                schedule.assignment_label || '',
            ].filter(Boolean).join(' · ');
        }
        schedulePicker?.classList.add('hidden');
        workspace?.classList.remove('hidden');
        manualStudentsCache = [];
        manualStudentsFiltered = [];
        showSekolahInRows = false;
        if (studentFilterSearch) studentFilterSearch.value = '';
        if (studentFilterSekolah) studentFilterSekolah.value = '';
        if (studentFilterKelas) studentFilterKelas.value = '';
        if (studentFilterStatus) studentFilterStatus.value = '';
        setMode('rfid');
        sessionExemptionToggle?.classList.remove('hidden');
        updateSessionExemptionUi({ active: false, reason: null });
        loadManualStudents();
        loadGuruAttendanceStatus();
        resetFaceSession();
    }

    function backToSchedules() {
        stopCamera();
        setStudentsLoading(false);
        activeSchedule = null;
        activeMode = null;
        updateSessionExemptionUi({ active: false, reason: null });
        sessionExemptionToggle?.classList.add('hidden');
        sessionExemptionRevoke?.classList.add('hidden');
        workspace?.classList.add('hidden');
        schedulePicker?.classList.remove('hidden');
    }

    function setMode(mode) {
        activeMode = mode;
        document.querySelectorAll('.attendance-mode-btn').forEach(function (btn) {
            var isActive = btn.dataset.mode === mode;
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-secondary', !isActive);
        });
        document.querySelectorAll('.attendance-mode-panel').forEach(function (panel) {
            panel.classList.add('hidden');
        });
        document.getElementById('mode-' + mode)?.classList.remove('hidden');

        if (mode !== 'face') {
            stopCamera();
        }
        if (mode === 'rfid') {
            rfidInput?.focus();
        }

        syncManualControlsByMode();
        if (manualStudentsCache.length) {
            applyStudentFilters();
        }
    }

    function syncManualControlsByMode() {
        var isManualMode = activeMode === 'manual';
        var hiddenDisplay = 'none';
        if (manualBulkControls) {
            manualBulkControls.style.display = isManualMode ? '' : hiddenDisplay;
        }
        if (manualThCheck) {
            manualThCheck.style.display = isManualMode ? '' : hiddenDisplay;
        }
        if (manualThInput) {
            manualThInput.style.display = isManualMode ? '' : hiddenDisplay;
        }
        if (manualThAction) {
            manualThAction.style.display = isManualMode ? '' : hiddenDisplay;
        }
        if (manualCheckAll) {
            manualCheckAll.checked = false;
            manualCheckAll.disabled = !isManualMode;
        }
    }

    function statusBadgeClass(hasAttendance) {
        return hasAttendance
            ? 'border-green-200 bg-green-50 text-green-700 shadow-sm dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300'
            : 'border-amber-200 bg-amber-50 text-amber-700 shadow-sm dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300';
    }

    function updateManualSummary(summary) {
        if (!summary) return;
        if (manualSummaryTotal) manualSummaryTotal.textContent = String(summary.total ?? 0);
        if (manualSummaryHadir) manualSummaryHadir.textContent = String(summary.hadir ?? 0);
        if (manualSummaryBelum) manualSummaryBelum.textContent = String(summary.belum ?? 0);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function studentMetaLine(row) {
        var parts = ['NIS ' + row.nis, row.kelas || '-'];
        if (showSekolahInRows && row.sekolah && row.sekolah !== '-') {
            parts.push(row.sekolah);
        }
        return parts.join(' · ');
    }

    function getActiveStudentFilters() {
        return {
            q: (studentFilterSearch?.value || '').trim().toLowerCase(),
            sekolah_id: studentFilterSekolah?.value || '',
            kelas_id: studentFilterKelas?.value || '',
            status: studentFilterStatus?.value || '',
        };
    }

    function hasActiveStudentFilters(filters) {
        return !!(filters.q || filters.sekolah_id || filters.kelas_id || filters.status);
    }

    function buildFilterOptions(students) {
        var sekolahMap = new Map();
        var kelasMap = new Map();

        students.forEach(function (row) {
            if (row.sekolah_id) {
                sekolahMap.set(String(row.sekolah_id), row.sekolah || 'Sekolah #' + row.sekolah_id);
            }
            if (row.kelas_id) {
                var kelasLabel = row.kelas || 'Kelas #' + row.kelas_id;
                if (showSekolahInRows && row.sekolah && row.sekolah !== '-') {
                    kelasLabel += ' · ' + row.sekolah;
                }
                kelasMap.set(String(row.kelas_id), kelasLabel);
            }
        });

        if (studentFilterSekolah) {
            var selectedSekolah = studentFilterSekolah.value;
            studentFilterSekolah.innerHTML = '<option value="">Semua sekolah</option>'
                + Array.from(sekolahMap.entries())
                    .sort(function (a, b) { return a[1].localeCompare(b[1], 'id'); })
                    .map(function (entry) {
                        return '<option value="' + entry[0] + '">' + escapeHtml(entry[1]) + '</option>';
                    }).join('');
            studentFilterSekolah.value = selectedSekolah;
            if (selectedSekolah && !sekolahMap.has(selectedSekolah)) {
                studentFilterSekolah.value = '';
            }
        }

        if (studentFilterKelas) {
            var selectedKelas = studentFilterKelas.value;
            studentFilterKelas.innerHTML = '<option value="">Semua kelas</option>'
                + Array.from(kelasMap.entries())
                    .sort(function (a, b) { return a[1].localeCompare(b[1], 'id'); })
                    .map(function (entry) {
                        return '<option value="' + entry[0] + '">' + escapeHtml(entry[1]) + '</option>';
                    }).join('');
            studentFilterKelas.value = selectedKelas;
            if (selectedKelas && !kelasMap.has(selectedKelas)) {
                studentFilterKelas.value = '';
            }
        }

        if (studentFilterSekolahWrap) {
            studentFilterSekolahWrap.classList.toggle('hidden', sekolahMap.size <= 1);
        }
    }

    function filterStudents(students) {
        var filters = getActiveStudentFilters();

        return students.filter(function (row) {
            if (filters.q) {
                var haystack = (row.name + ' ' + row.nis + ' ' + (row.kelas || '') + ' ' + (row.sekolah || '')).toLowerCase();
                if (haystack.indexOf(filters.q) === -1) {
                    return false;
                }
            }

            if (filters.sekolah_id && String(row.sekolah_id) !== String(filters.sekolah_id)) {
                return false;
            }

            if (filters.kelas_id && String(row.kelas_id) !== String(filters.kelas_id)) {
                return false;
            }

            if (filters.status === 'hadir' && !row.has_attendance) {
                return false;
            }

            if (filters.status === 'belum' && row.has_attendance) {
                return false;
            }

            return true;
        });
    }

    function updateFilterMeta() {
        if (!studentFilterMeta) return;

        var total = manualStudentsCache.length;
        var visible = manualStudentsFiltered.length;
        var filters = getActiveStudentFilters();
        var belumVisible = manualStudentsFiltered.filter(function (row) { return !row.has_attendance; }).length;

        if (!total) {
            studentFilterMeta.textContent = 'Tidak ada siswa pada jadwal ini.';
            return;
        }

        var parts = ['Menampilkan ' + visible + ' dari ' + total + ' siswa'];
        if (belumVisible > 0) {
            parts.push(belumVisible + ' belum absen');
        }
        if (hasActiveStudentFilters(filters)) {
            parts.push('filter aktif');
        }
        studentFilterMeta.textContent = parts.join(' · ');
    }

    function syncFilterBarVisibility() {
        if (!studentFilterBar) return;
        studentFilterBar.classList.toggle('hidden', manualStudentsCache.length === 0);
        if (studentFilterSelectUnmarked) {
            var hasUnmarked = manualStudentsFiltered.some(function (row) { return !row.has_attendance; });
            studentFilterSelectUnmarked.classList.toggle('hidden', !hasUnmarked || activeMode !== 'manual');
        }
    }

    function resetStudentFilters() {
        if (studentFilterSearch) studentFilterSearch.value = '';
        if (studentFilterSekolah) studentFilterSekolah.value = '';
        if (studentFilterKelas) studentFilterKelas.value = '';
        if (studentFilterStatus) studentFilterStatus.value = '';
        manualStudentsFiltered = manualStudentsCache.slice();
        if (manualStudentsCache.length) {
            applyStudentFilters();
        }
    }

    function applyStudentFilters() {
        manualStudentsFiltered = filterStudents(manualStudentsCache);
        renderManualTable(manualStudentsFiltered);
        updateFilterMeta();
        syncFilterBarVisibility();
        if (manualCheckAll) manualCheckAll.checked = false;
    }

    function selectVisibleUnmarked() {
        if (activeMode !== 'manual' || !manualTableBody) return;
        manualTableBody.querySelectorAll('tr[data-student-row]').forEach(function (tr) {
            var rowId = parseInt(tr.getAttribute('data-student-row') || '', 10);
            var student = manualStudentsFiltered.find(function (item) { return item.id === rowId; });
            var checkbox = tr.querySelector('.manual-row-check');
            if (!checkbox || checkbox.disabled) return;
            checkbox.checked = !!(student && !student.has_attendance);
        });
        if (manualCheckAll) {
            var checks = Array.from(manualTableBody.querySelectorAll('.manual-row-check:not(:disabled)'));
            manualCheckAll.checked = checks.length > 0 && checks.every(function (el) { return el.checked; });
        }
    }

    var manualApproveAllPerizinan = document.getElementById('manual-approve-all-perizinan');
    var manualApproveAllPerizinanLabel = document.getElementById('manual-approve-all-perizinan-label');

    function renderManualTable(students) {
        if (!manualTableBody) return;
        if (!students.length) {
            var emptyMessage = manualStudentsCache.length
                ? 'Tidak ada siswa yang cocok dengan filter.'
                : 'Tidak ada siswa pada jadwal ini.';
            manualTableBody.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">' + emptyMessage + '</td></tr>';
            if (manualApproveAllPerizinan) manualApproveAllPerizinan.classList.add('hidden');
            return;
        }

        var isManualMode = activeMode === 'manual';
        var hiddenCell = ' style="display:none"';

        var unapprovedPerizinanCount = students.filter(function (r) {
            return !r.has_attendance && r.perizinan && r.perizinan.is_active;
        }).length;

        if (manualApproveAllPerizinan) {
            manualApproveAllPerizinan.classList.toggle('hidden', unapprovedPerizinanCount === 0 || !isManualMode);
            if (manualApproveAllPerizinanLabel) {
                manualApproveAllPerizinanLabel.textContent = 'Approve (' + unapprovedPerizinanCount + ') Siswa Izin';
            }
        }

        manualTableBody.innerHTML = students.map(function (row) {
            var selectedStatus = (row.attendance?.status || '').toLowerCase();
            if (!selectedStatus && row.suggested_status) {
                selectedStatus = row.suggested_status.toLowerCase();
            }

            var attendanceText = row.has_attendance && row.attendance
                ? (row.attendance.status + ' · ' + (row.attendance.time_in || '-') + ' · ' + (row.attendance.method || '-'))
                : (row.perizinan && row.perizinan.is_active ? (row.perizinan.suggested_status_label === 'Izin' ? 'Izin Aktif' : ('Izin (' + row.perizinan.suggested_status_label + ')')) : 'Belum absen');

            var perizinanBadgeHtml = '';
            if (row.perizinan) {
                if (row.perizinan.is_active) {
                    perizinanBadgeHtml = '<span class="mt-1 inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300" title="' + escapeHtml(row.perizinan.alasan) + '"><i class="ti ti-file-text"></i> ' + escapeHtml(row.perizinan.jenis_label) + ': ' + escapeHtml(row.perizinan.alasan) + ' (' + escapeHtml(row.perizinan.date_range_label) + ')</span>';
                } else if (row.perizinan.is_expired) {
                    perizinanBadgeHtml = '<span class="mt-1 inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300" title="Batas waktu izin berakhir jam ' + escapeHtml(row.perizinan.time_sampai_label) + '"><i class="ti ti-alert-triangle"></i> Izin berakhir ' + escapeHtml(row.perizinan.time_sampai_label) + ' (Belum Kembali)</span>';
                }
            }

            var submitBtnText = row.has_attendance ? 'Update' : 'Simpan';
            var submitBtnClass = 'btn-primary btn-sm manual-row-submit w-full';

            if (!row.has_attendance && row.perizinan && row.perizinan.is_active) {
                submitBtnText = '<i class="ti ti-check text-base leading-none mr-0.5"></i> Approve Izin';
                submitBtnClass = 'btn-warning btn-sm manual-row-submit w-full border border-amber-300 bg-amber-600 hover:bg-amber-700 text-white font-medium shadow-sm';
            }

            var canUpdate = isManualMode;
            var rowTone = row.has_attendance
                ? 'bg-white hover:bg-green-50/30 dark:bg-slate-900/20 dark:hover:bg-green-900/10'
                : (row.perizinan && row.perizinan.is_active ? 'bg-amber-50/20 hover:bg-amber-50/40 dark:bg-slate-900/20 dark:hover:bg-amber-900/10' : 'bg-white hover:bg-amber-50/40 dark:bg-slate-900/20 dark:hover:bg-amber-900/10');

            return '<tr data-student-row="' + row.id + '" class="border-t border-slate-100 transition-colors dark:border-slate-800 ' + rowTone + '">'
                + '<td class="px-3 py-2 align-middle"' + (isManualMode ? '' : hiddenCell) + '><input type="checkbox" class="form-checkbox manual-row-check" value="' + row.id + '"' + (canUpdate ? '' : ' disabled') + '></td>'
                + '<td class="px-3 py-2 align-middle">'
                + '<p class="font-semibold text-slate-900 dark:text-white">' + escapeHtml(row.name) + '</p>'
                + '<p class="text-xs text-slate-500 dark:text-slate-400">' + escapeHtml(studentMetaLine(row)) + '</p>'
                + (perizinanBadgeHtml ? ('<div class="mt-0.5">' + perizinanBadgeHtml + '</div>') : '')
                + '</td>'
                + '<td class="px-3 py-2 align-middle"><span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold ' + statusBadgeClass(row.has_attendance || (row.perizinan && row.perizinan.is_active)) + '">' + escapeHtml(attendanceText) + '</span></td>'
                + '<td class="px-3 py-2 align-middle"' + (isManualMode ? '' : hiddenCell) + '>'
                + '<select class="form-input h-9 manual-row-status"' + (canUpdate ? '' : ' disabled') + '>'
                + '<option value="hadir"' + (selectedStatus === 'hadir' ? ' selected' : '') + '>Hadir</option>'
                + '<option value="terlambat"' + (selectedStatus === 'terlambat' ? ' selected' : '') + '>Terlambat</option>'
                + '<option value="izin"' + (selectedStatus === 'izin' ? ' selected' : '') + '>Izin</option>'
                + '<option value="sakit"' + (selectedStatus === 'sakit' ? ' selected' : '') + '>Sakit</option>'
                + '<option value="cuti"' + (selectedStatus === 'cuti' ? ' selected' : '') + '>Cuti</option>'
                + '<option value="alpha"' + (selectedStatus === 'alpha' ? ' selected' : '') + '>Alpha</option>'
                + '</select>'
                + '</td>'
                + '<td class="px-3 py-2 align-middle"' + (isManualMode ? '' : hiddenCell) + '><button type="button" class="' + submitBtnClass + '" data-student-id="' + row.id + '"' + (canUpdate ? '' : ' disabled') + '>' + submitBtnText + '</button></td>'
                + '</tr>';
        }).join('');
    }

    async function loadManualStudents() {
        if (!activeSchedule) return;
        studentFilterBar?.classList.add('hidden');

        var expected = Number(activeSchedule.student_count || 0);
        var expectedLabel = expected > 0 ? expected.toLocaleString('id-ID') + ' siswa' : 'daftar siswa';
        setStudentsLoading(true, {
            badge: 'Memuat ' + expectedLabel + '…',
            title: 'Memuat daftar siswa',
            detail: expected > 0
                ? 'Mengambil sekitar ' + expectedLabel + ' untuk jadwal ini. Mohon tunggu…'
                : 'Mohon tunggu, data sedang diambil dari server…',
        });

        try {
            var studentsUrl = new URL(config.studentsUrl, window.location.origin);
            studentsUrl.searchParams.set('slot_id', String(activeSchedule.id));
            studentsUrl.searchParams.set('_ts', String(Date.now()));

            var res = await fetch(studentsUrl.toString(), {
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'Cache-Control': 'no-cache',
                    Pragma: 'no-cache',
                },
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            var json = await res.json();
            if (!json || json.success === false) {
                throw new Error(json?.message || 'Gagal memuat siswa');
            }
            manualStudentsCache = json.data?.students || [];

            // Pin active unapproved perizinan students to the VERY TOP of the table!
            manualStudentsCache.sort(function (a, b) {
                var aActive = (!a.has_attendance && a.perizinan && a.perizinan.is_active) ? 1 : 0;
                var bActive = (!b.has_attendance && b.perizinan && b.perizinan.is_active) ? 1 : 0;
                if (aActive !== bActive) {
                    return bActive - aActive; // 1 (active perizinan) comes first!
                }
                return (a.name || '').localeCompare(b.name || '', 'id');
            });

            var sekolahIds = new Set(manualStudentsCache.map(function (row) { return String(row.sekolah_id || ''); }).filter(Boolean));
            showSekolahInRows = sekolahIds.size > 1 || !!activeSchedule?.covers_all_schools;
            setStudentsLoading(false);
            buildFilterOptions(manualStudentsCache);
            applyStudentFilters();
            updateManualSummary(json.data?.summary);
            updateSessionExemptionUi(json.data?.session_exemption || {});
            if (manualCheckAll) manualCheckAll.checked = false;

            var unapprovedPerizinanList = manualStudentsCache.filter(function (r) {
                return !r.has_attendance && r.perizinan && r.perizinan.is_active;
            });

            if (studentPerizinanBanner) {
                var count = unapprovedPerizinanList.length;
                studentPerizinanBanner.classList.toggle('hidden', count === 0);
                if (count > 0) {
                    if (perizinanBannerTitle) perizinanBannerTitle.textContent = count + ' Siswa Dalam Perizinan Aktif';
                    if (perizinanBannerDetail) {
                        var names = unapprovedPerizinanList.map(function (s) { return s.name; }).join(', ');
                        perizinanBannerDetail.textContent = 'Siswa (' + names + ') otomatis ditempatkan di PALING ATAS tabel untuk kemudahan approve.';
                    }
                }
            }

            if (unapprovedPerizinanList.length > 0 && activeSchedule && !hasShownPerizinanPopupForSlot[activeSchedule.id]) {
                hasShownPerizinanPopupForSlot[activeSchedule.id] = true;
                showPerizinanAlertPopup(unapprovedPerizinanList);
            }

            if (!manualStudentsCache.length) {
                var assignment = json.data?.assignment;
                var reason = (assignment && assignment.type === 'kelas' && !(assignment.kelas_ids || []).length)
                    ? 'Jadwal ini belum punya kelas. Minta admin memilih kelas pada penugasan jadwal absen.'
                    : 'Tidak ada siswa aktif pada kelas/penugasan jadwal ini.';
                if (manualTableBody) {
                    manualTableBody.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">' + reason + '</td></tr>';
                }
            }
        } catch (e) {
            manualStudentsCache = [];
            manualStudentsFiltered = [];
            setStudentsLoading(false);
            if (manualTableBody) {
                manualTableBody.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Gagal memuat siswa. Coba muat ulang halaman.</td></tr>';
            }
            updateManualSummary({ total: 0, hadir: 0, belum: 0 });
            window.showToast?.('Gagal memuat daftar siswa. ' + (e && e.message ? e.message : ''), 'error');
        }
    }

    async function submitAttendance(payload) {
        var res = await fetch(config.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || '',
                Accept: 'application/json',
            },
            body: JSON.stringify(Object.assign({
                jadwal_absen_slot_id: activeSchedule?.id,
            }, payload)),
        });
        return res.json();
    }

    function updateGuruManualButton(attendance) {
        if (!guruAttendanceManualBtn) return;
        var label = 'Absen Masuk Manual';
        var disabled = false;

        if (attendance?.has_masuk && !attendance?.has_pulang && !attendance?.is_absent_only) {
            label = 'Absen Pulang Manual';
        } else if (attendance?.is_complete || attendance?.is_absent_only) {
            label = 'Sudah Absen Hari Ini';
            disabled = true;
        }

        guruAttendanceManualBtn.disabled = disabled;
        guruAttendanceManualBtn.innerHTML = '<i class="ti ti-check text-base leading-none mr-1"></i>' + label;
    }

    function updateGuruAttendanceBadge(attendance) {
        if (!guruAttendanceBadge) return;
        if (!attendance) {
            guruAttendanceBadge.className = 'mt-1 inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300';
            guruAttendanceBadge.textContent = 'Belum Absen';
            return;
        }

        if (attendance.is_complete || attendance.is_absent_only) {
            guruAttendanceBadge.className = 'mt-1 inline-flex rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300';
            guruAttendanceBadge.textContent = 'Sudah Absen Lengkap';
            return;
        }

        if (attendance.has_masuk && !attendance.has_pulang) {
            guruAttendanceBadge.className = 'mt-1 inline-flex rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300';
            guruAttendanceBadge.textContent = 'Sudah Masuk, Menunggu Pulang';
            return;
        }

        guruAttendanceBadge.className = 'mt-1 inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300';
        guruAttendanceBadge.textContent = 'Belum Absen';
    }

    function formatGuruAttendance(attendance) {
        if (!attendance) {
            return 'Belum ada absensi guru hari ini.';
        }

        var parts = [];
        parts.push('Masuk: ' + (attendance.status || '-'));
        parts.push('Jam masuk: ' + (attendance.jam_masuk || '-'));
        if (attendance.status_pulang || attendance.jam_keluar) {
            parts.push('Pulang: ' + (attendance.status_pulang || '-'));
            parts.push('Jam keluar: ' + (attendance.jam_keluar || '-'));
        }
        parts.push('Metode: ' + (attendance.method || '-'));
        if (attendance.method_keluar && attendance.method_keluar !== '-') {
            parts.push('Metode keluar: ' + attendance.method_keluar);
        }

        return parts.join(' · ');
    }

    function setGuruAttendanceLoading(isLoading) {
        var disabled = !!isLoading;
        if (guruAttendanceManualBtn) guruAttendanceManualBtn.disabled = disabled;
        if (guruAttendanceRfidBtn) guruAttendanceRfidBtn.disabled = disabled;
        if (guruRfidInput) guruRfidInput.disabled = disabled;
        if (!disabled) {
            updateGuruManualButton(guruAttendanceState);
        }
    }

    async function loadGuruAttendanceStatus() {
        if (!config.guruAttendanceUrl) return;
        if (guruAttendanceStatusText) {
            guruAttendanceStatusText.textContent = 'Memuat status absensi guru hari ini...';
        }
        try {
            var statusUrl = new URL(config.guruAttendanceUrl, window.location.origin);
            statusUrl.searchParams.set('_ts', String(Date.now()));
            var res = await fetch(statusUrl.toString(), {
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'Cache-Control': 'no-cache',
                    Pragma: 'no-cache',
                },
            });
            var json = await res.json();
            if (!json.success) {
                if (guruAttendanceStatusText) {
                    guruAttendanceStatusText.textContent = json.message || 'Gagal memuat status absensi guru.';
                }
                return;
            }
            var attendance = json.data?.attendance || null;
            guruAttendanceState = attendance;
            if (guruAttendanceStatusText) {
                guruAttendanceStatusText.textContent = formatGuruAttendance(attendance);
            }
            updateGuruAttendanceBadge(attendance);
            updateGuruManualButton(attendance);
        } catch (e) {
            if (guruAttendanceStatusText) {
                guruAttendanceStatusText.textContent = 'Gagal memuat status absensi guru.';
            }
            updateGuruAttendanceBadge(null);
            updateGuruManualButton(null);
        }
    }

    async function confirmGuruAttendance(method) {
        var actionLabel = method === 'manual'
            ? (guruAttendanceState?.has_masuk && !guruAttendanceState?.has_pulang && !guruAttendanceState?.is_absent_only ? 'PULANG' : 'MASUK')
            : 'RFID';

        var detail = [
            { label: 'Aksi', value: actionLabel },
            { label: 'Metode', value: method.toUpperCase() },
            { label: 'Status sekarang', value: guruAttendanceState ? (guruAttendanceState.status || '-') : 'Belum absen' },
        ];

        if (method === 'rfid') {
            detail.push({ label: 'UID', value: ((guruRfidInput?.value || '').trim() || '-') });
        }

        if (typeof window.showConfirm === 'function') {
            return window.showConfirm({
                title: 'Konfirmasi Absensi Guru',
                message: 'Lanjutkan proses absensi guru?',
                tone: 'primary',
                confirmText: 'Ya, simpan',
                confirmIcon: 'ti-user-check',
                headerIcon: 'ti-user-check',
                cancelText: 'Batal',
                detail: detail,
            });
        }

        return window.confirm('Lanjutkan absensi guru?');
    }

    async function submitGuruAttendance(method) {
        if (!config.guruAttendanceStoreUrl) return;

        var payload = { method: method };
        if (method === 'rfid') {
            var uid = (guruRfidInput?.value || '').trim();
            if (!uid) {
                window.showToast?.('Masukkan UID RFID guru terlebih dahulu.', 'warning');
                guruRfidInput?.focus();
                return;
            }
            payload.rfid_uid = uid;
        }

        if (method === 'manual' && guruAttendanceState?.is_complete) {
            window.showToast?.('Absensi guru hari ini sudah lengkap.', 'info');
            return;
        }

        var confirmed = await confirmGuruAttendance(method);
        if (!confirmed) return;

        setGuruAttendanceLoading(true);
        try {
            var res = await fetch(config.guruAttendanceStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });
            var json = await res.json();
            if (!json.success) {
                window.showToast?.(json.message || 'Gagal mencatat absensi guru.', 'error');
                return;
            }

            var latestAttendance = json.data?.attendance || null;
            if (latestAttendance) {
                guruAttendanceState = latestAttendance;
                if (guruAttendanceStatusText) {
                    guruAttendanceStatusText.textContent = formatGuruAttendance(latestAttendance);
                }
                updateGuruAttendanceBadge(latestAttendance);
                updateGuruManualButton(latestAttendance);
            }

            window.showToast?.(json.message || 'Absensi guru berhasil dicatat.', 'success');
            if (method === 'rfid' && guruRfidInput) {
                guruRfidInput.value = '';
                guruRfidInput.focus();
            }
            await loadGuruAttendanceStatus();
        } catch (e) {
            window.showToast?.('Gagal mencatat absensi guru.', 'error');
        } finally {
            setGuruAttendanceLoading(false);
        }
    }

    function showAttendanceModal(attendance, alreadyRecorded) {
        if (!attendance || !window.showAlert) return;
        window.showAlert({
            variant: alreadyRecorded ? 'info' : 'success',
            title: alreadyRecorded ? 'Siswa Sudah Absen' : 'Absensi Berhasil',
            message: alreadyRecorded
                ? attendance.name + ' sudah tercatat absen hari ini.'
                : attendance.name + ' berhasil dicatat.',
            detail: [
                { label: 'NIS', value: attendance.nis },
                { label: 'Kelas', value: attendance.kelas || '-' },
                { label: 'Status', value: attendance.status || '-' },
                { label: 'Metode', value: attendance.method || '-' },
                { label: 'Jam', value: attendance.time_in || '-' },
            ],
            confirmText: 'Tutup',
        });
    }

    function handleAttendanceResult(result) {
        var attendance = result.data?.attendance;
        if (!attendance) {
            window.showToast?.(result.message || 'Absensi diproses.', result.success ? 'success' : 'error');
            return;
        }
        showAttendanceModal(attendance, !!result.data?.already_recorded);
        if (!result.data?.already_recorded && !result.data?.updated_existing && todayCountEl) {
            todayCountEl.textContent = String(parseInt(todayCountEl.textContent || '0', 10) + 1);
        }
    }

    async function submitRfid() {
        if (!activeSchedule || !rfidInput) return;
        var uid = (rfidInput.value || '').trim();
        if (!uid) return;

        try {
            var result = await submitAttendance({
                method: 'rfid',
                rfid_uid: uid,
            });
            if (!result.success) {
                window.showToast?.(result.message || 'RFID tidak dikenali.', 'error');
                return;
            }
            handleAttendanceResult(result);
            await loadManualStudents();
            rfidInput.value = '';
            rfidInput.focus();
        } catch (e) {
            window.showToast?.('Gagal menyimpan absensi RFID.', 'error');
        }
    }

    async function submitManual() {
        if (activeMode !== 'manual') {
            window.showToast?.('Input status hanya tersedia di mode Manual.', 'info');
            return;
        }

        var selectedIds = [];
        if (manualTableBody) {
            selectedIds = Array.from(manualTableBody.querySelectorAll('.manual-row-check:checked'))
                .map(function (el) { return parseInt(el.value, 10); })
                .filter(Boolean);
        }

        if (!selectedIds.length) {
            window.showToast?.('Pilih minimal satu siswa.', 'warning');
            return;
        }

        var button = manualSubmitSelected;
        var originalText = button?.innerHTML;
        if (button) {
            button.disabled = true;
            button.innerHTML = 'Menyimpan...';
        }

        var status = manualBulkStatus?.value || 'hadir';
        var successCount = 0;

        try {
            for (var i = 0; i < selectedIds.length; i += 1) {
                var result = await submitAttendance({
                    method: 'manual',
                    siswa_id: selectedIds[i],
                    status: status,
                    force_update: true,
                });
                if (result.success) {
                    successCount += 1;
                } else {
                    window.showToast?.(result.message || 'Sebagian data gagal disimpan.', 'warning');
                }
            }

            if (successCount > 0) {
                window.showToast?.(successCount + ' siswa berhasil diproses.', 'success');
                await loadManualStudents();
            }
        } catch (e) {
            window.showToast?.('Gagal menyimpan absensi manual jamak.', 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText || 'Simpan Terpilih';
            }
        }
    }

    async function submitApproveAllPerizinan() {
        if (activeMode !== 'manual') return;

        var targetStudents = manualStudentsFiltered.filter(function (r) {
            return !r.has_attendance && r.perizinan && r.perizinan.is_active;
        });

        if (!targetStudents.length) {
            window.showToast?.('Tidak ada siswa dengan perizinan aktif yang belum diapprove.', 'info');
            return;
        }

        var confirmed = false;
        var detail = targetStudents.map(function (s) {
            return { label: s.name, value: (s.perizinan.jenis_label || 'Izin') + ' (' + (s.perizinan.suggested_status_label || 'Izin') + ')' };
        });

        if (typeof window.showConfirm === 'function') {
            confirmed = await window.showConfirm({
                title: 'Approve Absensi Perizinan',
                message: 'Approve & simpan absensi ' + targetStudents.length + ' siswa yang sedang izin?',
                tone: 'warning',
                confirmText: 'Ya, Approve Semua',
                confirmIcon: 'ti-check',
                headerIcon: 'ti-file-text',
                cancelText: 'Batal',
                detail: detail.slice(0, 5),
            });
        } else {
            confirmed = window.confirm('Approve & simpan absensi ' + targetStudents.length + ' siswa yang sedang izin?');
        }

        if (!confirmed) return;

        var button = manualApproveAllPerizinan;
        var originalText = button?.innerHTML;
        if (button) {
            button.disabled = true;
            button.innerHTML = 'Memproses...';
        }

        var successCount = 0;
        try {
            for (var i = 0; i < targetStudents.length; i += 1) {
                var item = targetStudents[i];
                var result = await submitAttendance({
                    method: 'manual',
                    siswa_id: item.id,
                    status: item.suggested_status || 'izin',
                    force_update: true,
                });
                if (result.success) {
                    successCount += 1;
                }
            }

            if (successCount > 0) {
                window.showToast?.(successCount + ' absensi siswa izin berhasil diapprove.', 'success');
                await loadManualStudents();
            }
        } catch (e) {
            window.showToast?.('Gagal meng-approve absensi siswa izin.', 'error');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText || 'Approve Semua Izin';
            }
        }
    }

    function showPerizinanAlertPopup(activePermitStudents) {
        if (!activePermitStudents || !activePermitStudents.length) return;

        var count = activePermitStudents.length;
        var detail = activePermitStudents.map(function (s) {
            return {
                label: s.name + ' (' + (s.kelas || '-') + ')',
                value: (s.perizinan.jenis_label || 'Izin') + ': ' + (s.perizinan.alasan || 'Izin') + ' (' + (s.perizinan.date_range_label || '') + ')'
            };
        });

        if (typeof window.showConfirm === 'function') {
            window.showConfirm({
                title: 'Siswa Dalam Perizinan Aktif (' + count + ' Siswa)',
                message: 'Terdapat ' + count + ' siswa pada jadwal ini yang sedang izin dan perlu di-approve ke tabel absensi. Apa yang ingin Anda lakukan?',
                tone: 'warning',
                confirmText: 'Approve Semua (' + count + ') Izin',
                confirmIcon: 'ti-check',
                headerIcon: 'ti-file-text',
                cancelText: 'Buka Tabel Absensi',
                detail: detail.slice(0, 6),
            }).then(function (confirmed) {
                if (confirmed) {
                    submitApproveAllPerizinan();
                } else {
                    setMode('manual');
                    var studentListCard = document.getElementById('student-list-card');
                    if (studentListCard) {
                        studentListCard.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        }
    }

    document.querySelectorAll('.jadwal-pick-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var raw = card.getAttribute('data-schedule') || '';
            var schedule = null;
            try {
                if (/^[A-Za-z0-9+/=]+$/.test(raw) && raw.length > 8) {
                    schedule = JSON.parse(atob(raw));
                } else {
                    schedule = JSON.parse(raw || '{}');
                }
            } catch (e) {
                window.showToast?.('Data jadwal tidak valid. Muat ulang halaman.', 'error');
                return;
            }
            if (!schedule?.id) {
                window.showToast?.('Jadwal tidak memiliki ID slot yang valid.', 'error');
                return;
            }
            selectSchedule(schedule);
        });
    });

    document.getElementById('back-to-schedules')?.addEventListener('click', backToSchedules);
    sessionExemptionToggle?.addEventListener('click', markSessionExempt);
    sessionExemptionRevoke?.addEventListener('click', revokeSessionExempt);
    document.querySelectorAll('.attendance-mode-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { setMode(btn.dataset.mode); });
    });

    rfidInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitRfid();
        }
    });

    manualSubmitSelected?.addEventListener('click', submitManual);
    manualApproveAllPerizinan?.addEventListener('click', submitApproveAllPerizinan);
    perizinanBannerApproveBtn?.addEventListener('click', submitApproveAllPerizinan);
    manualCheckAll?.addEventListener('change', function () {
        if (activeMode !== 'manual') return;
        var checked = !!manualCheckAll.checked;
        manualTableBody?.querySelectorAll('.manual-row-check:not(:disabled)').forEach(function (el) {
            el.checked = checked;
        });
    });

    studentFilterSearch?.addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(applyStudentFilters, 180);
    });
    studentFilterSekolah?.addEventListener('change', applyStudentFilters);
    studentFilterKelas?.addEventListener('change', applyStudentFilters);
    studentFilterStatus?.addEventListener('change', applyStudentFilters);
    studentFilterReset?.addEventListener('click', resetStudentFilters);
    studentFilterSelectUnmarked?.addEventListener('click', selectVisibleUnmarked);
    manualTableBody?.addEventListener('click', async function (event) {
        if (activeMode !== 'manual') return;
        var submitBtn = event.target.closest('.manual-row-submit');
        if (!submitBtn) return;

        var siswaId = parseInt(submitBtn.dataset.studentId || '', 10);
        if (!siswaId) return;

        var row = submitBtn.closest('tr');
        var status = row?.querySelector('.manual-row-status')?.value || 'hadir';
        var originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '...';

        try {
            var result = await submitAttendance({
                method: 'manual',
                siswa_id: siswaId,
                status: status,
                force_update: true,
            });
            if (!result.success) {
                window.showToast?.(result.message || 'Gagal menyimpan absensi.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            handleAttendanceResult(result);
            await loadManualStudents();
        } catch (e) {
            window.showToast?.('Gagal menyimpan absensi manual.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    guruAttendanceManualBtn?.addEventListener('click', function () {
        submitGuruAttendance('manual');
    });
    guruAttendanceRfidBtn?.addEventListener('click', function () {
        submitGuruAttendance('rfid');
    });
    guruRfidInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitGuruAttendance('rfid');
        }
    });

    // --- Face detection ---
    var toggleBtn = document.getElementById('face-toggle');
    var cameraModeSelect = document.getElementById('face-camera-mode');
    var statusSelect = document.getElementById('face-status');
    var video = document.getElementById('face-video');
    var overlay = document.getElementById('face-overlay');
    var matchCard = document.getElementById('face-match-card');
    var logList = document.getElementById('face-log-list');
    var engineStatus = document.getElementById('face-engine-status');

    function resetFaceSession() {
        stopCamera();
        faceState.matcherReady = false;
        faceState.references = [];
        faceState.matcher = null;
        if (matchCard) matchCard.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400">Belum ada deteksi.</p>';
        if (logList) logList.innerHTML = '';
    }

    function getFacingMode() {
        return cameraModeSelect?.value === 'environment' ? 'environment' : 'user';
    }

    function applyVideoMirror() {
        if (!video) return;
        video.style.transform = getFacingMode() === 'user' ? 'scaleX(-1)' : 'none';
    }

    function setEngineStatus(text) {
        if (engineStatus) engineStatus.textContent = text;
    }

    async function fetchReferences() {
        var res = await fetch(config.refsUrl + '?slot_id=' + activeSchedule.id, {
            headers: { Accept: 'application/json' },
        });
        var json = await res.json();
        faceState.references = json.data?.students || [];
        return faceState.references;
    }

    async function loadModels() {
        setEngineStatus('Memuat model deteksi wajah...');
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(config.modelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(config.modelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(config.modelUrl),
        ]);
    }

    async function buildMatcher() {
        var labeled = [];
        for (var i = 0; i < faceState.references.length; i += 1) {
            var row = faceState.references[i];
            try {
                var img = await faceapi.fetchImage(row.photo_url);
                var det = await faceapi.detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.4 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                if (det) labeled.push(new faceapi.LabeledFaceDescriptors('siswa-' + row.id, [det.descriptor]));
            } catch (e) { /* skip */ }
        }
        faceState.matcher = labeled.length ? new faceapi.FaceMatcher(labeled, faceState.threshold) : null;
        setEngineStatus(labeled.length ? 'Deteksi aktif' : 'Belum ada referensi wajah');
    }

    async function markFaceAttendance(studentId) {
        return submitAttendance({
            method: 'face',
            siswa_id: studentId,
            status: statusSelect?.value || 'hadir',
        });
    }

    async function confirmFaceAttendance(student) {
        if (!student) return false;
        var selectedStatus = statusSelect?.value || 'hadir';

        if (typeof window.showConfirm === 'function') {
            return window.showConfirm({
                title: 'Konfirmasi Absensi Wajah',
                message: 'Lanjutkan absensi untuk siswa ini?',
                tone: 'primary',
                confirmText: 'Lanjut',
                confirmIcon: 'ti-face-id',
                headerIcon: 'ti-face-id',
                cancelText: 'Batal',
                detail: [
                    { label: 'Nama', value: student.name || '-' },
                    { label: 'NIS', value: student.nis || '-' },
                    { label: 'Status', value: selectedStatus.toUpperCase() },
                    { label: 'Metode', value: 'WAJAH' },
                ],
            });
        }

        return window.confirm('Lanjut absensi wajah untuk ' + (student.name || 'siswa') + '?');
    }

    function appendLog(message, success) {
        if (!logList) return;
        var li = document.createElement('li');
        li.className = 'rounded-lg border px-3 py-2 text-sm ' + (success
            ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300'
            : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300');
        li.textContent = message;
        logList.prepend(li);
    }

    async function detectFrame() {
        if (!video || !faceState.matcher || !faceState.detectActive) return;
        faceState.rafId = requestAnimationFrame(detectFrame);
        if ((Date.now() - faceState.lastDetectAt) < faceState.intervalMs) return;
        faceState.lastDetectAt = Date.now();
        if (!video.videoWidth) return;

        var detections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.45 }))
            .withFaceLandmarks()
            .withFaceDescriptors();
        faceapi.matchDimensions(overlay, { width: video.videoWidth, height: video.videoHeight });
        var resized = faceapi.resizeResults(detections, { width: video.videoWidth, height: video.videoHeight });
        overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);

        resized.forEach(function (det) {
            var best = faceState.matcher.findBestMatch(det.descriptor);
            new faceapi.draw.DrawBox(det.detection.box, {
                label: best.toString(),
                boxColor: best.distance <= faceState.threshold ? '#16a34a' : '#ef4444',
            }).draw(overlay);
        });

        for (var i = 0; i < detections.length; i += 1) {
            var best = faceState.matcher.findBestMatch(detections[i].descriptor);
            if (best.label === 'unknown' || best.distance > faceState.threshold) continue;
            var studentId = best.label.replace('siswa-', '');
            var student = faceState.references.find(function (r) { return String(r.id) === studentId; });
            if (!student) continue;
            var key = String(student.id);
            var nowTs = Date.now();
            if (faceState.cooldown.has(key) && (nowTs - faceState.cooldown.get(key)) < 20000) continue;
            faceState.cooldown.set(key, nowTs);
            if (faceState.modalCooldown.has(key) && (nowTs - faceState.modalCooldown.get(key)) < 12000) continue;
            faceState.modalCooldown.set(key, nowTs);

            if (matchCard) {
                matchCard.innerHTML = '<p class="font-semibold text-slate-900 dark:text-white">' + student.name + '</p><p class="text-sm text-slate-500">NIS ' + student.nis + '</p>';
            }

            try {
                var confirmed = await confirmFaceAttendance(student);
                if (!confirmed) {
                    appendLog(student.name + ' - dibatalkan', false);
                    continue;
                }

                var result = await markFaceAttendance(student.id);
                if (result.success) {
                    appendLog(student.name + ' - ' + (result.data?.already_recorded ? 'sudah absen' : 'tersimpan'), true);
                    handleAttendanceResult(result);
                    await loadManualStudents();
                }
            } catch (e) {
                appendLog('Gagal simpan ' + student.name, false);
            }
        }
    }

    async function openCameraStream() {
        if (faceState.stream) {
            faceState.stream.getTracks().forEach(function (t) { t.stop(); });
        }
        faceState.stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: getFacingMode() }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
        video.srcObject = faceState.stream;
        applyVideoMirror();
        await video.play();
    }

    function stopCamera() {
        faceState.detectActive = false;
        if (faceState.rafId) cancelAnimationFrame(faceState.rafId);
        if (faceState.stream) {
            faceState.stream.getTracks().forEach(function (t) { t.stop(); });
            faceState.stream = null;
        }
        if (video) video.srcObject = null;
        if (overlay) overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);
        if (toggleBtn) {
            toggleBtn.classList.remove('btn-secondary');
            toggleBtn.classList.add('btn-primary');
            toggleBtn.innerHTML = '<i class="ti ti-camera text-base leading-none mr-1"></i>Mulai Kamera';
        }
    }

    toggleBtn?.addEventListener('click', async function () {
        if (faceState.detectActive) {
            stopCamera();
            return;
        }
        if (!window.faceapi) {
            window.showToast?.('face-api.js gagal dimuat.', 'error');
            return;
        }
        try {
            if (!faceState.modelsReady) {
                await loadModels();
                faceState.modelsReady = true;
            }
            if (!faceState.matcherReady) {
                await fetchReferences();
                await buildMatcher();
                faceState.matcherReady = true;
            }
            await openCameraStream();
            faceState.detectActive = true;
            toggleBtn.classList.replace('btn-primary', 'btn-secondary');
            toggleBtn.innerHTML = '<i class="ti ti-camera-off text-base leading-none mr-1"></i>Stop Kamera';
            detectFrame();
        } catch (e) {
            stopCamera();
            window.showToast?.('Gagal membuka kamera atau memuat model.', 'error');
        }
    });

    cameraModeSelect?.addEventListener('change', async function () {
        if (!faceState.stream) return;
        try {
            await openCameraStream();
        } catch (e) {
            window.showToast?.('Gagal mengganti kamera.', 'error');
        }
    });

    window.addEventListener('beforeunload', stopCamera);
})();
