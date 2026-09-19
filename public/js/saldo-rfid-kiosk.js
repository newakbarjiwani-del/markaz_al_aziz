/**
 * RFID balance kiosk — cashless only.
 * Finance panel stays disabled; never call finance balance APIs from here.
 */
(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatClock() {
        const now = new Date();
        const dateEl = document.getElementById('kiosk-date');
        const timeEl = document.getElementById('kiosk-time');
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        }
        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        }
    }

    window.initSaldoRfidKiosk = function initSaldoRfidKiosk(options) {
        const lookupUrl = options.lookupUrl;
        const recentUrl = options.recentUrl;
        const csrfToken = options.csrfToken;

        const financeEnabled =
            document.querySelector('[data-finance-enabled]')?.getAttribute('data-finance-enabled') === '1';

        const rfidInput = document.getElementById('rfid-input');
        const rfidForm = document.getElementById('rfid-form');
        const statusBar = document.getElementById('status-bar');
        const panelIdle = document.getElementById('panel-idle');
        const panelResult = document.getElementById('panel-result');
        const statusHeadline = document.getElementById('status-headline');
        const statusSubline = document.getElementById('status-subline');
        const resultPhoto = document.getElementById('result-photo');
        const resultPhotoPlaceholder = document.getElementById('result-photo-placeholder');
        const resultBadgeContainer = document.getElementById('result-badge-container');
        const resultName = document.getElementById('result-name');
        const resultClassNis = document.getElementById('result-class-nis');
        const resultSchool = document.getElementById('result-school');
        const resultCashless = document.getElementById('result-cashless-balance');
        const financePanel = document.getElementById('finance-balance-panel');
        const focusText = document.getElementById('focus-indicator-text');
        const focusDot = document.getElementById('focus-indicator-dot');
        const recentFeed = document.getElementById('recent-feed-container');
        const feedEmpty = document.getElementById('feed-empty-state');
        const todayCount = document.getElementById('today-tx-count');
        const studentRecentSection = document.getElementById('student-recent-section');
        const studentRecentList = document.getElementById('student-recent-list');
        const kioskToggle = document.getElementById('kiosk-toggle-btn');

        let busy = false;
        let resetTimer = null;

        function keepFocus() {
            if (document.activeElement !== rfidInput) {
                rfidInput.focus({ preventScroll: true });
            }
        }

        function setFocusReady(ready) {
            if (!focusText || !focusDot) return;
            if (ready) {
                focusText.textContent = 'Siap scan RFID';
                focusDot.classList.add('animate-pulse', 'bg-primary-500');
                focusDot.classList.remove('bg-amber-500');
            } else {
                focusText.textContent = 'Fokus scanner hilang — klik halaman';
                focusDot.classList.remove('animate-pulse', 'bg-primary-500');
                focusDot.classList.add('bg-amber-500');
            }
        }

        function showIdle() {
            panelIdle.classList.remove('hidden');
            panelResult.classList.add('hidden');
            statusBar.className =
                'h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300';
            statusHeadline.textContent = 'Silakan Tempelkan Kartu';
            statusSubline.textContent = 'Saldo cashless akan ditampilkan setelah scan';
            studentRecentSection?.classList.add('hidden');
            if (financePanel) {
                financePanel.classList.add('hidden');
            }
        }

        function showResult(ok) {
            panelIdle.classList.add('hidden');
            panelResult.classList.remove('hidden');
            statusBar.className = ok
                ? 'h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300'
                : 'h-1.5 w-full bg-red-500 absolute top-0 left-0 transition-all duration-300';
            resultBadgeContainer.className = ok
                ? 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm shadow bg-primary-600'
                : 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm shadow bg-red-500';
        }

        function setPhoto(url) {
            if (url) {
                resultPhoto.src = url;
                resultPhoto.classList.remove('hidden');
                resultPhotoPlaceholder.classList.add('hidden');
            } else {
                resultPhoto.src = '';
                resultPhoto.classList.add('hidden');
                resultPhotoPlaceholder.classList.remove('hidden');
            }
        }

        function renderStudentRecent(items) {
            if (!studentRecentSection || !studentRecentList) return;
            if (!items || !items.length) {
                studentRecentSection.classList.add('hidden');
                studentRecentList.innerHTML = '';
                return;
            }
            studentRecentSection.classList.remove('hidden');
            studentRecentList.innerHTML = items
                .map((row) => {
                    const tone =
                        row.direction === 'in'
                            ? 'text-primary-700 dark:text-primary-300'
                            : 'text-red-600 dark:text-red-400';
                    const sign = row.direction === 'in' ? '+' : '−';
                    return `<div class="flex items-center justify-between gap-2 rounded-lg border border-[var(--surface-border-subtle)] bg-[var(--surface-card)] px-3 py-2 text-xs">
                        <div class="min-w-0">
                            <div class="font-bold text-[var(--text-primary)] truncate">${escapeHtml(row.metode)}</div>
                            <div class="text-[10px] text-[var(--text-muted)]">${escapeHtml(row.date)} · ${escapeHtml(row.time)}</div>
                        </div>
                        <div class="font-extrabold tabular-nums ${tone}">${sign}${escapeHtml(row.amount_formatted)}</div>
                    </div>`;
                })
                .join('');
        }

        function loadRecentFeed() {
            fetch(recentUrl + '?_ts=' + Date.now(), {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            })
                .then((res) => res.json())
                .then((res) => {
                    if (!res.success || !res.data) return;
                    const items = res.data.items || [];
                    if (todayCount) {
                        todayCount.textContent = 'Hari ini: ' + (res.data.count ?? items.length);
                    }
                    recentFeed.querySelectorAll('.feed-item-wrapper').forEach((el) => el.remove());
                    if (!items.length) {
                        feedEmpty?.classList.remove('hidden');
                        return;
                    }
                    feedEmpty?.classList.add('hidden');
                    const html = items
                        .map((row) => {
                            const tone =
                                row.direction === 'in'
                                    ? 'border-l-primary-600'
                                    : 'border-l-red-500';
                            const sign = row.direction === 'in' ? '+' : '−';
                            return `<div class="feed-item-wrapper rfid-feed-item border border-[var(--surface-border-subtle)] rounded-lg border-l-4 ${tone} px-3 py-2.5 bg-[var(--surface-card)]">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-bold text-sm text-[var(--text-primary)] truncate">${escapeHtml(row.name)}</div>
                                        <div class="text-[11px] text-[var(--text-muted)]">${escapeHtml(row.kelas)} · NIS ${escapeHtml(row.nis)}</div>
                                        <div class="mt-1 text-[11px] font-semibold text-[var(--text-secondary)]">${escapeHtml(row.metode)} · ${escapeHtml(row.time)}</div>
                                    </div>
                                    <div class="text-sm font-extrabold tabular-nums whitespace-nowrap">${sign}${escapeHtml(row.amount_formatted)}</div>
                                </div>
                            </div>`;
                        })
                        .join('');
                    recentFeed.insertAdjacentHTML('beforeend', html);
                })
                .catch(() => {
                    /* ignore feed errors */
                });
        }

        function scheduleReset() {
            if (resetTimer) clearTimeout(resetTimer);
            resetTimer = setTimeout(() => {
                showIdle();
                keepFocus();
            }, 8000);
        }

        function lookup(uid) {
            if (busy || !uid) return;
            busy = true;
            statusHeadline.textContent = 'Memproses...';
            statusSubline.textContent = 'Membaca data saldo cashless';

            fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ rfid_uid: uid }),
            })
                .then(async (res) => {
                    const payload = await res.json().catch(() => ({}));
                    if (!res.ok || !payload.success) {
                        throw new Error(payload.message || 'Kartu tidak dikenali');
                    }
                    return payload.data;
                })
                .then((data) => {
                    showResult(true);
                    const siswa = data.siswa || {};
                    resultName.textContent = siswa.name || '-';
                    resultClassNis.textContent = [siswa.kelas, siswa.nis ? 'NIS ' + siswa.nis : null]
                        .filter(Boolean)
                        .join(' · ');
                    resultSchool.textContent = siswa.sekolah || '';
                    resultCashless.textContent = data.cashless_balance_formatted || 'Rp 0';
                    setPhoto(siswa.photo_url || null);
                    renderStudentRecent(data.recent || []);

                    // Hard rule: do not fetch finance balance while disabled
                    if (financePanel) {
                        if (financeEnabled) {
                            financePanel.classList.remove('hidden');
                            financePanel.setAttribute('aria-hidden', 'false');
                        } else {
                            financePanel.classList.add('hidden');
                            financePanel.setAttribute('aria-hidden', 'true');
                            financePanel.setAttribute('data-enabled', '0');
                        }
                    }

                    loadRecentFeed();
                    scheduleReset();
                })
                .catch((err) => {
                    showResult(false);
                    resultName.textContent = 'Gagal';
                    resultClassNis.textContent = err.message || 'Kartu RFID tidak dikenali';
                    resultSchool.textContent = '';
                    resultCashless.textContent = '—';
                    setPhoto(null);
                    renderStudentRecent([]);
                    scheduleReset();
                })
                .finally(() => {
                    busy = false;
                    rfidInput.value = '';
                    keepFocus();
                });
        }

        rfidForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            const uid = (rfidInput.value || '').trim();
            rfidInput.value = '';
            if (uid) lookup(uid);
        });

        rfidInput?.addEventListener('focus', () => setFocusReady(true));
        rfidInput?.addEventListener('blur', () => setFocusReady(false));
        document.addEventListener('click', keepFocus);

        kioskToggle?.addEventListener('click', function () {
            document.body.classList.toggle('rfid-kiosk-mode');
            const on = document.body.classList.contains('rfid-kiosk-mode');
            kioskToggle.querySelector('span')?.replaceChildren(document.createTextNode(on ? 'Keluar Kiosk' : 'Mode Kiosk'));
            if (on && document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else if (!on && document.fullscreenElement) {
                document.exitFullscreen().catch(() => {});
            }
            keepFocus();
        });

        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement) {
                document.body.classList.remove('rfid-kiosk-mode');
                kioskToggle?.querySelector('span')?.replaceChildren(document.createTextNode('Mode Kiosk'));
            }
        });

        formatClock();
        setInterval(formatClock, 1000);
        keepFocus();
        setInterval(keepFocus, 2000);
        loadRecentFeed();
        setInterval(loadRecentFeed, 30000);
        showIdle();
    };
})();
