@extends('layouts.app')

@section('title', '')

@section('content')
<style>
    .kiosk-page-wrapper {
        color: var(--text-primary);
    }

    body.rfid-kiosk-mode #sidebar,
    body.rfid-kiosk-mode #sidebar-overlay,
    body.rfid-kiosk-mode #app-main > header,
    body.rfid-kiosk-mode #app-main > main > div.mb-6,
    body.rfid-kiosk-mode #mobile-bottom-nav,
    body.rfid-kiosk-mode #sidebar-tooltip,
    body.rfid-kiosk-mode .datatable-section {
        display: none !important;
    }
    body.rfid-kiosk-mode #app-main {
        padding-left: 0 !important;
    }
    body.rfid-kiosk-mode main.app-content {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        height: 100vh;
        overflow: hidden;
        background-color: var(--surface-page) !important;
    }
    body.rfid-kiosk-mode .kiosk-page-wrapper {
        height: 100vh;
        padding: 2rem 2.5rem;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    .rfid-kiosk-header {
        border-color: var(--surface-border-subtle);
    }

    .rfid-scanner-ring {
        border-color: var(--color-primary-300);
        background: var(--color-primary-50);
    }
    .dark .rfid-scanner-ring {
        border-color: var(--color-primary-700);
        background: var(--color-primary-950);
    }

    .rfid-focus-ready {
        background: color-mix(in srgb, var(--color-primary-500) 12%, transparent);
        color: var(--color-primary-700);
    }
    .dark .rfid-focus-ready {
        background: color-mix(in srgb, var(--color-primary-400) 14%, transparent);
        color: var(--color-primary-300);
    }

    .rfid-scroll {
        overflow-y: auto;
        padding-right: 0.25rem;
        scrollbar-width: thin;
        scrollbar-color: var(--scrollbar-thumb) transparent;
    }
    .rfid-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .rfid-scroll::-webkit-scrollbar-thumb {
        background: var(--scrollbar-thumb);
        border-radius: 3px;
    }
    .rfid-scroll::-webkit-scrollbar-thumb:hover {
        background: var(--scrollbar-thumb-hover);
    }

    .rfid-recent-feed {
        max-height: min(72vh, 720px);
    }
    body.rfid-kiosk-mode .rfid-recent-feed {
        max-height: min(58vh, 560px);
    }

    .rfid-feed-item {
        border-color: var(--surface-border-subtle);
        background: var(--surface-card);
    }
    .rfid-feed-item--hadir {
        border-left: 4px solid var(--color-primary-600);
    }
    .rfid-feed-item--late {
        border-left: 4px solid var(--color-accent-500);
    }

    .scanner-input-container {
        position: absolute;
        top: -9999px;
        left: -9999px;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
    }
    #rfid-input {
        border: none;
        outline: none;
        background: transparent;
        color: transparent;
    }
</style>

<div class="kiosk-page-wrapper">
    <!-- Header Area -->
    <div class="kiosk-header rfid-kiosk-header flex justify-between items-center border-b pb-4 mb-6">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-[var(--text-primary)] kiosk-title">Kiosk Presensi RFID</h1>
            <p class="text-xs text-[var(--text-muted)]">Tempelkan kartu RFID siswa pada pembaca yang tersedia</p>
        </div>
        <div>
            <!-- Fullscreen Kiosk Mode Button (Primary Theme Color) -->
            <button type="button" id="kiosk-toggle-btn" class="btn-primary flex items-center gap-1.5 px-3 py-1.5 text-sm">
                <x-icon name="maximize" size="sm" />
                <span>Mode Kiosk</span>
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-[440px_1fr] gap-6 mb-6">
        <!-- Left Side: Scanner & Schedules -->
        <div class="flex flex-col gap-5">
            <!-- Time Widget Card -->
            <div class="card p-5 text-center">
                <div id="kiosk-date" class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">-</div>
                <div id="kiosk-time" class="mt-1 text-5xl font-extrabold tracking-tight text-primary-600 dark:text-primary-400 font-mono">-</div>
            </div>

            <!-- Scanner Station Card (Using absolute status bar inside) -->
            <div class="card p-6 text-center relative overflow-hidden flex flex-col gap-6" id="status-panel">
                <!-- Status Indicator Bar at the very top of the card (Primary Brand Green Idle) -->
                <div class="h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300" id="status-bar"></div>

                <!-- Visually hidden focusable form container for scanner wedges -->
                <form id="rfid-form" class="scanner-input-container">
                    <input type="text" id="rfid-input" autocomplete="off" autofocus placeholder="Scan kartu RFID...">
                </form>

                <!-- Idle View -->
                <div id="panel-idle" class="flex flex-col gap-5 py-4">
                    <!-- Simple styled dashed circle scanner zone -->
                    <div class="w-36 h-36 mx-auto rounded-full border-2 border-dashed rfid-scanner-ring flex items-center justify-center transition-all duration-200" id="pulse-zone">
                        <x-icon name="credit-card" size="lg" class="text-primary-600 dark:text-primary-400 text-4xl" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[var(--text-primary)]" id="status-headline">Silakan Tempelkan Kartu</h3>
                        <p class="mt-1 text-sm text-[var(--text-muted)]" id="status-subline">Posisikan kartu dekat dengan sensor reader</p>
                    </div>
                </div>

                <!-- Result View (Success / Error / Duplicate) -->
                <div id="panel-result" class="hidden flex flex-col items-center gap-5 py-2">
                    <!-- Photo Frame -->
                    <div class="relative">
                        <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center shadow-inner" id="result-photo-container">
                            <x-icon name="user" size="lg" class="text-slate-400 text-5xl" id="result-photo-placeholder" />
                            <img src="" alt="Foto Wajah" id="result-photo" class="hidden w-full h-full object-cover">
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm shadow bg-primary-600" id="result-badge-container">
                            <x-icon name="check" size="xs" id="result-badge-icon" />
                        </div>
                    </div>

                    <div class="w-full">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 truncate px-2" id="result-name">-</h4>
                        <div class="flex items-center justify-center gap-2 mt-1">
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400" id="result-class-nis">-</span>
                            <span id="result-school-badge-container"></span>
                        </div>
                        
                        <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" id="result-status-badge">
                            <span id="result-status-text">-</span>
                        </div>
                    </div>

                    <div class="w-full border-t border-slate-200 dark:border-slate-800 pt-4 flex justify-around text-center text-xs">
                        <div>
                            <span class="block text-slate-400 dark:text-slate-500 font-semibold mb-0.5">Jam Tap</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200" id="result-time">-</span>
                        </div>
                        <div id="result-slot-container">
                            <span class="block text-slate-400 dark:text-slate-500 font-semibold mb-0.5" id="result-slot-label">Jadwal</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200 truncate max-w-[120px] block" id="result-slot-value">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Schedules Card -->
            <div class="card p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                    <h4 class="font-extrabold text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <x-icon name="calendar-time" size="xs" class="text-primary-500" />
                        <span>Jadwal Presensi Aktif</span>
                    </h4>
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse" id="schedule-pulse"></span>
                </div>
                <div class="scroll-container-clean rfid-scroll flex flex-col gap-2 max-h-[180px]" id="active-slots-container">
                    <div class="text-center py-4 text-xs text-slate-500">Memuat jadwal...</div>
                </div>
            </div>

            <!-- Focus Status Indicator -->
            <div class="text-center text-xs py-1.5 px-3 rounded-lg rfid-focus-ready flex items-center justify-center gap-1.5" id="focus-indicator">
                <span class="w-2.5 h-2.5 rounded-full bg-primary-500 animate-pulse" id="focus-indicator-dot"></span>
                <span id="focus-indicator-text" class="font-bold uppercase tracking-wider text-[10px]">Menghubungkan ke scanner...</span>
            </div>
        </div>

        <!-- Right Side: Recent Activity Feed -->
        <div class="card p-5 flex flex-col gap-4 lg:min-h-[640px]">
            <div class="flex flex-wrap justify-between items-center gap-2 border-b border-[var(--surface-border-subtle)] pb-3">
                <div>
                    <h3 class="font-bold flex items-center gap-2 text-[var(--text-primary)] text-base">
                        <x-icon name="history" size="sm" class="text-primary-600 dark:text-primary-400" />
                        <span>Aktivitas Presensi Terkini</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">Semua tap RFID hari ini (urut terbaru)</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-md bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 font-bold border border-primary-200/80 dark:border-primary-800" id="today-scan-count">Tap: 0</span>
                    <span class="text-xs px-2 py-0.5 rounded-md bg-[var(--surface-muted)] text-[var(--text-secondary)] font-semibold border border-[var(--surface-border-subtle)]" id="today-student-count">Siswa: 0</span>
                </div>
            </div>

            <div class="rfid-scroll rfid-recent-feed flex flex-col gap-3" id="recent-feed-container">
                <!-- Feed logs dynamically loaded -->
                <div class="text-center py-12 text-[var(--text-muted)] text-sm" id="feed-empty-state">
                    <x-icon name="credit-card-off" size="lg" class="block mx-auto mb-2 text-2xl" />
                    Belum ada presensi yang tercatat hari ini
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Admin logs (Only visible in normal/admin layout) -->
    <div class="datatable-section">
        @include('admin.partials.datatable-page', [
            'tableTitle' => 'Database Presensi RFID Hari Ini',
            'ajaxUrl' => route('admin.absensi.absensi-rfid.data'),
            'columns' => ['NIS', 'Nama', 'Kelas', 'Jadwal', 'Pelajaran', 'Status', 'Jam'],
            'showExport' => false,
        ])
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rfidInput = document.getElementById('rfid-input');
        const rfidForm = document.getElementById('rfid-form');
        const statusPanel = document.getElementById('status-panel');
        const statusBar = document.getElementById('status-bar');
        const pulseZone = document.getElementById('pulse-zone');
        
        const panelIdle = document.getElementById('panel-idle');
        const panelResult = document.getElementById('panel-result');
        const statusHeadline = document.getElementById('status-headline');
        const statusSubline = document.getElementById('status-subline');
        
        // Result fields
        const resultPhoto = document.getElementById('result-photo');
        const resultPhotoPlaceholder = document.getElementById('result-photo-placeholder');
        const resultBadgeContainer = document.getElementById('result-badge-container');
        const resultBadgeIcon = document.getElementById('result-badge-icon');
        const resultName = document.getElementById('result-name');
        const resultClassNis = document.getElementById('result-class-nis');
        const resultStatusBadge = document.getElementById('result-status-badge');
        const resultStatusText = document.getElementById('result-status-text');
        const resultTime = document.getElementById('result-time');
        const resultSlotValue = document.getElementById('result-slot-value');
        const resultSlotLabel = document.getElementById('result-slot-label');
        const resultSlotContainer = document.getElementById('result-slot-container');
        const resultSchoolBadgeContainer = document.getElementById('result-school-badge-container');

        // Focus and schedule elements
        const focusIndicatorDot = document.getElementById('focus-indicator-dot');
        const focusIndicatorText = document.getElementById('focus-indicator-text');
        const recentFeedContainer = document.getElementById('recent-feed-container');
        const feedEmptyState = document.getElementById('feed-empty-state');
        const todayScanCount = document.getElementById('today-scan-count');
        const todayStudentCount = document.getElementById('today-student-count');
        const activeSlotsContainer = document.getElementById('active-slots-container');
        
        let resetTimer = null;
        let isProcessing = false;

        // Sound synthesizer (Web Audio API)
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        
        function playChime(success) {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            
            if (success) {
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                
                // First beep
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
                osc.start();
                
                // Second beep
                osc.frequency.setValueAtTime(1174.66, audioCtx.currentTime + 0.08); // D6
                
                // End
                osc.stop(audioCtx.currentTime + 0.25);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.25);
            } else {
                osc.type = 'sawtooth';
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                osc.frequency.setValueAtTime(220, audioCtx.currentTime); // A3
                osc.start();
                osc.stop(audioCtx.currentTime + 0.35);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.35);
            }
        }

        // Live Clock Widget
        function updateClock() {
            const days = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
            const months = ['JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
            
            const now = new Date();
            const dayName = days[now.getDay()];
            const day = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            document.getElementById('kiosk-date').textContent = `${dayName}, ${day} ${monthName} ${year}`;
            document.getElementById('kiosk-time').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Get tenant/school color badge (MA is mapped back to original brand green/emerald context)
        function getSchoolBadgeHtml(code, name) {
            if (!code) return '';
            let badgeClass = '';
            let display = name || code.toUpperCase();
            
            switch(code.toLowerCase()) {
                case 'paud':
                    badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20';
                    break;
                case 'mts':
                    badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20';
                    break;
                case 'ma':
                    badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20';
                    break;
                case 'takhasus':
                    badgeClass = 'bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20';
                    break;
                default:
                    badgeClass = 'bg-slate-100 text-slate-800 dark:bg-slate-500/10 dark:text-slate-400 border border-slate-200 dark:border-slate-500/20';
            }
            return `<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shrink-0 border ${badgeClass}">${display}</span>`;
        }

        // Kiosk Autofocus management
        function keepFocus() {
            if (isProcessing) return;
            
            if (document.activeElement !== rfidInput) {
                rfidInput.focus();
            }
            
            focusIndicatorDot.className = 'w-2.5 h-2.5 rounded-full bg-primary-500 animate-pulse';
            focusIndicatorText.textContent = 'Scanner siap';
            focusIndicatorText.className = 'font-bold uppercase tracking-wider text-[10px] text-primary-700 dark:text-primary-300';
        }

        rfidInput.addEventListener('blur', function() {
            setTimeout(keepFocus, 10);
        });

        document.addEventListener('click', function() {
            keepFocus();
        });

        keepFocus();
        setInterval(keepFocus, 2000);

        // Fetch active schedules slots
        function loadActiveSlots() {
            fetch('{{ route("admin.absensi.absensi-rfid.active-slots") }}')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const slots = res.data;
                    if (slots.length === 0) {
                        activeSlotsContainer.innerHTML = '<div class="text-center py-4 text-xs text-slate-500 italic"><i class="ti ti-calendar-off mr-1"></i>Tidak ada jadwal hari ini</div>';
                        return;
                    }

                    let html = '';
                    slots.forEach(slot => {
                        let statusText = '';
                        let statusClass = '';
                        
                        if (slot.status === 'active') {
                            statusText = '<span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-ping"></span><span class="text-primary-600 dark:text-primary-400">Terbuka (Tepat Waktu)</span>';
                            statusClass = 'bg-primary-50/50 dark:bg-primary-500/5 border border-primary-200 dark:border-primary-500/20';
                        } else if (slot.status === 'late') {
                            statusText = '<span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span><span class="text-orange-650 dark:text-orange-400">Terbuka (Terlambat)</span>';
                            statusClass = 'bg-orange-50/50 dark:bg-orange-500/5 border border-orange-200 dark:border-orange-500/20';
                        } else if (slot.status === 'upcoming') {
                            statusText = '<span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span><span class="text-yellow-600 dark:text-yellow-400">Akan Datang</span>';
                            statusClass = 'bg-yellow-50/50 dark:bg-yellow-500/5 border border-yellow-200 dark:border-yellow-500/10';
                        } else {
                            statusText = '<span class="w-1.5 h-1.5 rounded-full bg-[var(--text-muted)]"></span><span class="text-[var(--text-muted)]">Selesai</span>';
                            statusClass = 'bg-[var(--surface-muted)] border border-[var(--surface-border-subtle)]';
                        }

                        const schoolBadge = getSchoolBadgeHtml(slot.sekolah_code, slot.sekolah_code);

                        html += `
                            <div class="flex items-center justify-between p-2.5 rounded-lg ${statusClass} text-xs">
                                <div class="flex flex-col gap-0.5 truncate mr-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate">${slot.pelajaran}</span>
                                        ${schoolBadge}
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">${slot.name} (${slot.time_start} - ${slot.time_end})</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 px-2 py-0.5 rounded text-[10px] font-bold">
                                    ${statusText}
                                </div>
                            </div>
                        `;
                    });
                    activeSlotsContainer.innerHTML = html;
                }
            })
            .catch(err => {
                console.error('Gagal mengambil jadwal aktif:', err);
                activeSlotsContainer.innerHTML = '<div class="text-center py-4 text-xs text-red-400">Gagal memuat jadwal</div>';
            });
        }

        // Fetch recent activities from endpoint and render feed using project variables
        function loadRecentActivity() {
            const recentUrl = new URL('{{ route("admin.absensi.absensi-rfid.recent") }}', window.location.origin);
            recentUrl.searchParams.set('_ts', String(Date.now()));

            fetch(recentUrl.toString(), {
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'Cache-Control': 'no-cache',
                    Pragma: 'no-cache',
                },
            })
            .then(async res => {
                const payload = await res.json().catch(() => null);
                if (!res.ok) {
                    throw new Error(payload?.message || ('HTTP error ' + res.status));
                }
                return payload || {};
            })
            .then(res => {
                if (res.success && Array.isArray(res.data)) {
                    const records = res.data;
                    const total = res.meta?.total ?? records.length;
                    const uniqueStudents = res.meta?.unique_students ?? new Set(records.map(r => r.nis)).size;

                    todayScanCount.textContent = `Tap: ${total}`;
                    if (todayStudentCount) {
                        todayStudentCount.textContent = `Siswa: ${uniqueStudents}`;
                    }

                    if (records.length === 0) {
                        feedEmptyState.textContent = '';
                        feedEmptyState.innerHTML = '<i class="ti ti-credit-card-off text-2xl block mx-auto mb-2"></i>Belum ada presensi yang tercatat hari ini';
                        feedEmptyState.classList.remove('hidden');
                        const items = recentFeedContainer.querySelectorAll('.feed-item-wrapper');
                        items.forEach(el => el.remove());
                        return;
                    }

                    feedEmptyState.classList.add('hidden');
                    
                    let html = '';
                    records.forEach(row => {
                        const isLate = String(row.status || '').toLowerCase() === 'terlambat';
                        
                        let statusBadgeClass = '';
                        if (isLate) {
                            statusBadgeClass = 'bg-accent-50 text-accent-800 border-accent-200 dark:bg-accent-900/25 dark:text-accent-300 dark:border-accent-800/40';
                        } else {
                            statusBadgeClass = 'bg-primary-50 text-primary-800 border-primary-200 dark:bg-primary-900/30 dark:text-primary-300 dark:border-primary-800/50';
                        }

                        const feedToneClass = isLate ? 'rfid-feed-item--late' : 'rfid-feed-item--hadir';

                        const photoImg = row.foto_url
                            ? `<img src="${row.foto_url}" class="w-11 h-11 rounded-full object-cover border border-[var(--surface-border-subtle)]">`
                            : `<div class="w-11 h-11 rounded-full bg-[var(--surface-muted)] flex items-center justify-center border border-[var(--surface-border-subtle)] text-[var(--text-muted)]"><i class="ti ti-user text-xl leading-none"></i></div>`;

                        const schoolBadge = getSchoolBadgeHtml(row.sekolah_code, row.sekolah_code);

                        // Upgraded feed item with Lesson / Pelajaran details (More Informative)
                        html += `
                            <div class="feed-item-wrapper rfid-feed-item ${feedToneClass} flex items-start gap-3.5 p-3.5 rounded-xl border shadow-sm hover:translate-x-0.5 transition-all duration-200">
                                ${photoImg}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h5 class="font-extrabold text-sm text-[var(--text-primary)] truncate">${row.name}</h5>
                                        <span class="text-[10px] text-[var(--text-muted)] font-mono shrink-0">${row.time_in}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs mt-1 pb-2 border-b border-[var(--surface-border-subtle)]">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="text-[var(--text-secondary)]">Kelas ${row.kelas}</span>
                                            ${schoolBadge}
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border ${statusBadgeClass}">${row.status_label}</span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-[var(--text-muted)]">
                                        <i class="ti ti-notebook text-primary-600 dark:text-primary-400 text-xs shrink-0"></i>
                                        <span class="font-semibold text-[var(--text-secondary)] truncate">${row.pelajaran}</span>
                                        <span class="text-[10px] shrink-0">(${row.slot})</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    
                    const items = recentFeedContainer.querySelectorAll('.feed-item-wrapper');
                    items.forEach(el => el.remove());
                    
                    while (temp.firstChild) {
                        recentFeedContainer.appendChild(temp.firstChild);
                    }
                }
            })
            .catch(err => {
                console.error('Gagal mengambil riwayat presensi:', err);
                todayScanCount.textContent = 'Tap: -';
                if (todayStudentCount) {
                    todayStudentCount.textContent = 'Siswa: -';
                }
                feedEmptyState.innerHTML = 'Gagal memuat aktivitas presensi. Muat ulang halaman.';
                feedEmptyState.classList.remove('hidden');
            });
        }

        // Init loads
        loadRecentActivity();
        loadActiveSlots();

        setInterval(loadRecentActivity, 20000);
        setInterval(loadActiveSlots, 30000);

        // Reset display state to idle
        function resetToIdle() {
            isProcessing = false;
            
            // Reset status bar and view panel to Primary
            statusBar.className = 'h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300';
            pulseZone.className = 'w-36 h-36 mx-auto rounded-full border-2 border-dashed rfid-scanner-ring flex items-center justify-center transition-all duration-200';
            
            panelResult.classList.add('hidden');
            panelIdle.classList.remove('hidden');
            
            rfidInput.value = '';
            keepFocus();
        }

        // Handle Scan submissions
        rfidForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const rfidVal = rfidInput.value.trim();
            if (!rfidVal) return;
            
            if (isProcessing) return;
            isProcessing = true;
            
            if (resetTimer) clearTimeout(resetTimer);

            statusBar.className = 'h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300';
            panelIdle.classList.remove('hidden');
            panelResult.classList.add('hidden');
            statusHeadline.textContent = 'Memproses Kartu...';
            statusSubline.textContent = 'Harap tunggu sebentar';

            fetch('{{ route("admin.absensi.absensi-rfid.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rfid_uid: rfidVal })
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Terjadi kesalahan sistem.');
                }
                
                return data;
            })
            .then(data => {
                const isAlready = data.already_recorded === true;
                playChime(true);
                
                panelIdle.classList.add('hidden');
                panelResult.classList.remove('hidden');

                const s = data.data.siswa;
                const att = data.data.attendance;

                resultName.textContent = s.name;
                resultClassNis.textContent = `${s.nis} · Kelas ${s.kelas}`;
                resultTime.textContent = att.time_in;
                
                resultSchoolBadgeContainer.innerHTML = getSchoolBadgeHtml(s.sekolah_code, s.sekolah_name);
                
                if (att.slot_name) {
                    resultSlotContainer.classList.remove('hidden');
                    resultSlotLabel.textContent = att.pelajaran || 'Jadwal';
                    resultSlotValue.textContent = att.slot_name;
                } else {
                    resultSlotContainer.classList.add('hidden');
                }

                if (s.foto_url) {
                    resultPhoto.src = s.foto_url;
                    resultPhoto.classList.remove('hidden');
                    resultPhotoPlaceholder.classList.add('hidden');
                } else {
                    resultPhoto.classList.add('hidden');
                    resultPhotoPlaceholder.classList.remove('hidden');
                }

                if (isAlready) {
                    statusBar.className = 'h-1.5 w-full bg-yellow-500 absolute top-0 left-0 transition-all duration-300';
                    resultStatusBadge.className = 'mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-yellow-100 text-yellow-850 dark:bg-yellow-500/15 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/20';
                    resultStatusText.textContent = 'Sudah Presensi';
                    
                    resultBadgeContainer.className = 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm bg-yellow-500 shadow-lg';
                    resultBadgeIcon.className = 'ti ti-alert-triangle text-sm leading-none';
                } else {
                    statusBar.className = 'h-1.5 w-full bg-primary-600 dark:bg-primary-500 absolute top-0 left-0 transition-all duration-300';
                    
                    if (att.status.toLowerCase() === 'terlambat') {
                        resultStatusBadge.className = 'mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-orange-100 text-orange-850 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20';
                        resultStatusText.textContent = 'Hadir (Terlambat)';
                        
                        resultBadgeContainer.className = 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm bg-orange-500 shadow-lg';
                        resultBadgeIcon.className = 'ti ti-clock-play text-sm leading-none';
                    } else {
                        resultStatusBadge.className = 'mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-primary-50 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300 border border-primary-200 dark:border-primary-800/50';
                        resultStatusText.textContent = 'Hadir (Tepat Waktu)';

                        resultBadgeContainer.className = 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm bg-primary-600 shadow-lg';
                        resultBadgeIcon.className = 'ti ti-check text-sm leading-none';
                    }
                }

                loadRecentActivity();
                loadActiveSlots(); // Refresh schedule slots after tap
                window.reloadMainTable?.();
            })
            .catch(error => {
                playChime(false);

                panelIdle.classList.add('hidden');
                panelResult.classList.remove('hidden');

                resultName.textContent = 'Gagal Mencatat';
                resultClassNis.textContent = error.message;
                resultSchoolBadgeContainer.innerHTML = '';
                
                resultPhoto.classList.add('hidden');
                resultPhotoPlaceholder.classList.remove('hidden');
                
                resultTime.textContent = '--:--';
                resultSlotContainer.classList.add('hidden');

                statusBar.className = 'h-1.5 w-full bg-red-600 absolute top-0 left-0 transition-all duration-300';
                resultStatusBadge.className = 'mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-400 border border-red-200 dark:border-red-500/20';
                resultStatusText.textContent = 'Presensi Gagal';
                
                resultBadgeContainer.className = 'absolute -bottom-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm bg-red-500 shadow-lg';
                resultBadgeIcon.className = 'ti ti-x text-sm leading-none';
            })
            .finally(() => {
                rfidInput.value = '';
                resetTimer = setTimeout(resetToIdle, 5000);
            });
        });

        // Keydown wedge scanner focus capture
        document.addEventListener('keydown', function(e) {
            if (isProcessing) return;

            if (document.activeElement !== rfidInput && 
                document.activeElement.tagName !== 'INPUT' && 
                document.activeElement.tagName !== 'TEXTAREA') {
                rfidInput.focus();
            }
        });

        // Fullscreen & Kiosk Mode handler
        const kioskToggleBtn = document.getElementById('kiosk-toggle-btn');
        const kioskToggleIcon = kioskToggleBtn.querySelector('i');
        const kioskToggleText = kioskToggleBtn.querySelector('span');

        function toggleKioskMode() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen()
                    .then(() => {
                        document.body.classList.add('rfid-kiosk-mode');
                        kioskToggleIcon.className = 'ti ti-minimize text-base leading-none';
                        kioskToggleText.textContent = 'Keluar Kiosk';
                    })
                    .catch(err => {
                        console.error('Gagal masuk mode layar penuh:', err);
                    });
            } else {
                document.exitFullscreen();
            }
        }

        kioskToggleBtn.addEventListener('click', toggleKioskMode);

        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement) {
                document.body.classList.remove('rfid-kiosk-mode');
                kioskToggleIcon.className = 'ti ti-maximize text-base leading-none';
                kioskToggleText.textContent = 'Mode Kiosk';
            } else {
                document.body.classList.add('rfid-kiosk-mode');
            }
        });
    });
</script>
@endsection
