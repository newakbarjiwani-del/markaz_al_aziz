(function () {
    'use strict';

    function initDailySubjectMatrix() {
        const containers = document.querySelectorAll('.daily-subject-matrix-container');

        containers.forEach(container => {
            const ajaxUrl = container.dataset.ajaxUrl;
            if (!ajaxUrl) return;

            const form = container.querySelector('.daily-matrix-filter-form');
            const dateInput = container.querySelector('input[name="date"]');
            const kelasSelect = container.querySelector('select[name="kelas_id"]');
            const loading = container.querySelector('.daily-matrix-loading');
            const emptyState = container.querySelector('.daily-matrix-empty');
            const table = container.querySelector('.daily-matrix-table');
            const headerRow = container.querySelector('.daily-matrix-header-row');
            const body = container.querySelector('.daily-matrix-body');

            function loadDailyMatrix() {
                if (!kelasSelect || !kelasSelect.value) {
                    if (emptyState) {
                        emptyState.textContent = 'Silakan pilih kelas terlebih dahulu.';
                        emptyState.classList.remove('hidden');
                    }
                    if (body) body.innerHTML = '';
                    return;
                }

                if (loading) loading.classList.remove('hidden');
                if (emptyState) emptyState.classList.add('hidden');

                const params = new URLSearchParams();
                if (dateInput && dateInput.value) params.append('date', dateInput.value);
                if (kelasSelect && kelasSelect.value) params.append('kelas_id', kelasSelect.value);

                const fetchUrl = ajaxUrl + (ajaxUrl.includes('?') ? '&' : '?') + params.toString();

                fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const payload = await res.json().catch(() => null);
                    if (!res.ok) {
                        const message = payload?.message || ('HTTP error ' + res.status);
                        throw new Error(message);
                    }
                    if (!payload) {
                        throw new Error('Respons server tidak valid.');
                    }
                    return payload;
                })
                .then(data => {
                    renderDailyMatrix(data);
                })
                .catch(err => {
                    console.error('Error fetching daily subject matrix:', err);
                    if (body) body.innerHTML = '';
                    if (headerRow) {
                        headerRow.innerHTML = '';
                    }
                    const alertContainer = container.querySelector('.daily-matrix-holiday-alert');
                    if (alertContainer) {
                        alertContainer.className = 'daily-matrix-holiday-alert hidden';
                        alertContainer.innerHTML = '';
                    }
                    if (emptyState) {
                        emptyState.textContent = err.message || 'Gagal memuat data matriks mata pelajaran.';
                        emptyState.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    if (loading) loading.classList.add('hidden');
                });
            }

            function renderDailyMatrix(data) {
                const headers = data.subject_headers || [];
                const students = data.students || [];

                // 0. Render Holiday / Weekend Alert Banner
                let alertContainer = container.querySelector('.daily-matrix-holiday-alert');
                if (!alertContainer) {
                    alertContainer = document.createElement('div');
                    const tableWrapper = container.querySelector('.daily-matrix-wrapper');
                    if (tableWrapper && tableWrapper.parentNode) {
                        tableWrapper.parentNode.insertBefore(alertContainer, tableWrapper);
                    }
                }

                if (data.is_holiday) {
                    const holName = data.holiday_name || 'Hari Libur';
                    alertContainer.className = 'daily-matrix-holiday-alert m-4 mb-0 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs font-medium text-amber-900 shadow-xs dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200 flex items-center gap-3';
                    alertContainer.innerHTML = `
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white font-bold text-base shadow-xs">🌴</div>
                        <div>
                            <div class="font-bold text-sm text-amber-950 dark:text-amber-100">Hari Libur Sekolah (${holName})</div>
                            <div class="text-amber-800 dark:text-amber-300">Tanggal ${data.date_label || data.date} terdaftar sebagai hari libur sekolah. Tanggal ini tidak dihitung sebagai hari aktif sekolah (Alpha tidak berlaku).</div>
                        </div>
                    `;
                } else if (data.is_weekend) {
                    alertContainer.className = 'daily-matrix-holiday-alert m-4 mb-0 rounded-2xl border border-slate-300 bg-slate-50 p-4 text-xs font-medium text-slate-800 shadow-xs dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-200 flex items-center gap-3';
                    alertContainer.innerHTML = `
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-600 text-white font-bold text-base shadow-xs">📅</div>
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-white">Hari Libur Akhir Pekan (${data.day_name || ''})</div>
                            <div class="text-slate-600 dark:text-slate-400">Tanggal ${data.date_label || data.date} merupakan akhir pekan. Tanggal ini tidak dihitung sebagai hari aktif sekolah.</div>
                        </div>
                    `;
                } else {
                    alertContainer.className = 'daily-matrix-holiday-alert hidden';
                    alertContainer.innerHTML = '';
                }

                // 1. Render Header
                let headerHtml = `
                    <th class="sticky left-0 z-10 min-w-[40px] bg-slate-50 px-2 py-2.5 text-center font-bold dark:bg-slate-800">#</th>
                    <th class="sticky left-[40px] z-10 min-w-[90px] bg-slate-50 px-2 py-2.5 font-bold dark:bg-slate-800">NIS</th>
                    <th class="sticky left-[130px] z-10 min-w-[160px] bg-slate-50 px-3 py-2.5 font-bold shadow-r dark:bg-slate-800">Nama Siswa</th>
                    <th class="px-2 py-2.5 font-bold min-w-[90px]">Kelas</th>
                `;

                if (headers.length === 0) {
                    headerHtml += `<th class="px-3 py-2.5 text-center font-medium text-slate-500 min-w-[200px]">Belum Ada Jadwal Mapel Hari Ini</th>`;
                } else {
                    headers.forEach(h => {
                        const timeStr = (h.time_start && h.time_end) ? `(${h.time_start} - ${h.time_end})` : '';
                        const exemptBadge = h.is_exempt
                            ? '<div class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Tidak diabsen</div>'
                            : '';
                        headerHtml += `
                            <th class="px-3 py-2 text-center min-w-[130px] border-x border-slate-200 dark:border-slate-700 ${h.is_exempt ? 'bg-amber-50/80 dark:bg-amber-950/30' : 'bg-slate-100/70 dark:bg-slate-800'}">
                                <div class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">${h.jam} ${timeStr}</div>
                                <div class="font-bold text-xs text-slate-900 dark:text-white truncate max-w-[140px]">${h.pelajaran_name}</div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[140px]">${h.guru_name}</div>
                                ${exemptBadge}
                            </th>
                        `;
                    });
                }

                // Summary headers
                headerHtml += `
                    <th class="px-2 py-2 text-center font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 min-w-[32px]">H</th>
                    <th class="px-2 py-2 text-center font-bold text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300 min-w-[32px]">A</th>
                    <th class="px-2 py-2 text-center font-bold text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 min-w-[32px]">I</th>
                    <th class="px-2 py-2 text-center font-bold text-sky-700 bg-sky-50 dark:bg-sky-950/40 dark:text-sky-300 min-w-[32px]">S</th>
                    <th class="px-2 py-2 text-center font-bold text-orange-700 bg-orange-50 dark:bg-orange-950/40 dark:text-orange-300 min-w-[32px]">T</th>
                    <th class="px-2 py-2 text-center font-bold text-purple-700 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-300 min-w-[32px]">C</th>
                `;

                if (headerRow) headerRow.innerHTML = headerHtml;

                // 2. Render Student Rows
                if (students.length === 0) {
                    if (body) body.innerHTML = '';
                    if (emptyState) {
                        emptyState.textContent = 'Tidak ada data siswa untuk kelas dan tanggal yang dipilih.';
                        emptyState.classList.remove('hidden');
                    }
                    return;
                }

                if (emptyState) emptyState.classList.add('hidden');

                let bodyHtml = '';
                students.forEach((st, idx) => {
                    bodyHtml += `
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="sticky left-0 z-10 bg-white px-2 py-2 text-center font-medium text-slate-500 dark:bg-slate-900 dark:text-slate-400">${idx + 1}</td>
                            <td class="sticky left-[40px] z-10 bg-white px-2 py-2 font-mono text-[11px] text-slate-600 dark:bg-slate-900 dark:text-slate-300">${st.nis || '-'}</td>
                            <td class="sticky left-[130px] z-10 bg-white px-3 py-2 font-semibold text-slate-900 shadow-r dark:bg-slate-900 dark:text-white truncate max-w-[180px]">${st.name}</td>
                            <td class="px-2 py-2 text-slate-600 dark:text-slate-400 whitespace-nowrap">${st.kelas_name}</td>
                    `;

                    // Subject cells
                    if (headers.length === 0) {
                        bodyHtml += `<td class="px-3 py-2 text-center text-slate-400">-</td>`;
                    } else {
                        headers.forEach((h, hIdx) => {
                            const subInfo = st.subjects[hIdx] || {};
                            const status = subInfo.status;
                            const timeIn = subInfo.time_in;
                            const mapelName = h.pelajaran_name;
                            const isExempt = !!subInfo.is_exempt || !!h.is_exempt;

                            let badgeHtml = '';
                            if (isExempt && !status) {
                                const tooltip = (subInfo.exempt_reason || h.exempt_reason || 'Sesi tidak wajib absen');
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-semibold text-[11px] bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200" title="${mapelName}: ${tooltip}">—</span>`;
                            } else if (status === 'hadir') {
                                const tooltip = `${mapelName}: Hadir` + (timeIn ? ` (${timeIn})` : '');
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-emerald-500 text-white shadow-xs" title="${tooltip}">Hadir</span>`;
                            } else if (status === 'alpha') {
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-rose-500 text-white shadow-xs" title="${mapelName}: Alpha">Alpha</span>`;
                            } else if (status === 'izin') {
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-amber-500 text-white shadow-xs" title="${mapelName}: Izin">Izin</span>`;
                            } else if (status === 'sakit') {
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-sky-500 text-white shadow-xs" title="${mapelName}: Sakit">Sakit</span>`;
                            } else if (status === 'terlambat') {
                                const tooltip = `${mapelName}: Terlambat` + (timeIn ? ` (${timeIn})` : '');
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-orange-500 text-white shadow-xs" title="${tooltip}">Terlambat</span>`;
                            } else if (status === 'cuti') {
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-bold text-xs bg-purple-500 text-white shadow-xs" title="${mapelName}: Cuti">Cuti</span>`;
                            } else {
                                badgeHtml = `<span class="inline-flex h-7 px-2 items-center justify-center rounded-lg font-medium text-xs text-slate-400 bg-slate-100 dark:bg-slate-800 dark:text-slate-500">-</span>`;
                            }

                            bodyHtml += `<td class="px-2 py-2 text-center border-x border-slate-100 dark:border-slate-800">${badgeHtml}</td>`;
                        });
                    }

                    // Summary Columns
                    const sum = st.summary || {};
                    bodyHtml += `
                        <td class="px-2 py-2 text-center font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20">${sum.hadir || 0}</td>
                        <td class="px-2 py-2 text-center font-bold text-rose-600 dark:text-rose-400 bg-rose-50/40 dark:bg-rose-950/20">${sum.alpha || 0}</td>
                        <td class="px-2 py-2 text-center font-bold text-amber-600 dark:text-amber-400 bg-amber-50/40 dark:bg-amber-950/20">${sum.izin || 0}</td>
                        <td class="px-2 py-2 text-center font-bold text-sky-600 dark:text-sky-400 bg-sky-50/40 dark:bg-sky-950/20">${sum.sakit || 0}</td>
                        <td class="px-2 py-2 text-center font-bold text-orange-600 dark:text-orange-400 bg-orange-50/40 dark:bg-orange-950/20">${sum.terlambat || 0}</td>
                        <td class="px-2 py-2 text-center font-bold text-purple-600 dark:text-purple-400 bg-purple-50/40 dark:bg-purple-950/20">${sum.cuti || 0}</td>
                    </tr>`;
                });

                if (body) body.innerHTML = bodyHtml;
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    loadDailyMatrix();
                });
            }

            // Initial load
            loadDailyMatrix();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailySubjectMatrix);
    } else {
        initDailySubjectMatrix();
    }
})();
