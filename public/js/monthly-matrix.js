(function () {
    'use strict';

    function initMonthlyMatrix() {
        const matrixContainers = document.querySelectorAll('.monthly-matrix-container');

        matrixContainers.forEach(container => {
            const ajaxUrl = container.dataset.ajaxUrl;
            if (!ajaxUrl) return;

            const form = container.querySelector('.matrix-filter-form');
            const monthInput = container.querySelector('input[name="month"]');
            const kelasSelect = container.querySelector('select[name="kelas_id"]');
            const loading = container.querySelector('.matrix-loading');
            const emptyState = container.querySelector('.matrix-empty');
            const table = container.querySelector('.matrix-table');
            const monthTitle = container.querySelector('.matrix-month-title');
            const headerDaysRow = container.querySelector('.matrix-header-row-days');
            const daysHeaderCell = container.querySelector('.matrix-days-header-cell');
            const body = container.querySelector('.matrix-body');

            function loadMatrix() {
                if (loading) loading.classList.remove('hidden');
                if (emptyState) emptyState.classList.add('hidden');

                const params = new URLSearchParams();
                if (monthInput && monthInput.value) params.append('month', monthInput.value);
                if (kelasSelect && kelasSelect.value) params.append('kelas_id', kelasSelect.value);

                const fetchUrl = ajaxUrl + (ajaxUrl.includes('?') ? '&' : '?') + params.toString();

                fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP error ' + res.status);
                    return res.json();
                })
                .then(data => {
                    renderMatrix(data);
                })
                .catch(err => {
                    console.error('Error fetching monthly matrix:', err);
                    if (emptyState) {
                        emptyState.textContent = 'Gagal memuat data absensi.';
                        emptyState.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    if (loading) loading.classList.add('hidden');
                });
            }

            function renderMatrix(data) {
                const daysHeader = data.days_header || [];
                const students = data.students || [];
                const daysCount = daysHeader.length;

                if (monthTitle) monthTitle.textContent = data.month_label || data.month;
                if (daysHeaderCell) daysHeaderCell.colSpan = daysCount;

                // 1. Render Days Header Row
                let daysHtml = '';
                daysHeader.forEach(d => {
                    const weekendClass = d.is_weekend ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 font-bold' : '';
                    daysHtml += `
                        <th class="px-1 py-1 text-center min-w-[32px] border-x border-slate-200 dark:border-slate-700 ${weekendClass}">
                            <div class="text-[10px] leading-none text-slate-400 dark:text-slate-500 mb-0.5">${d.day_name}</div>
                            <div class="font-bold text-xs">${d.day}</div>
                        </th>
                    `;
                });

                // Summary headers (H, A, I, S, T, C)
                daysHtml += `
                    <th class="px-1.5 py-1 text-center font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 min-w-[32px]">H</th>
                    <th class="px-1.5 py-1 text-center font-bold text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300 min-w-[32px]">A</th>
                    <th class="px-1.5 py-1 text-center font-bold text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300 min-w-[32px]">I</th>
                    <th class="px-1.5 py-1 text-center font-bold text-sky-700 bg-sky-50 dark:bg-sky-950/40 dark:text-sky-300 min-w-[32px]">S</th>
                    <th class="px-1.5 py-1 text-center font-bold text-orange-700 bg-orange-50 dark:bg-orange-950/40 dark:text-orange-300 min-w-[32px]">T</th>
                    <th class="px-1.5 py-1 text-center font-bold text-purple-700 bg-purple-50 dark:bg-purple-950/40 dark:text-purple-300 min-w-[32px]">C</th>
                `;

                if (headerDaysRow) headerDaysRow.innerHTML = daysHtml;

                // 2. Render Student Rows
                if (students.length === 0) {
                    if (body) body.innerHTML = '';
                    if (emptyState) emptyState.classList.remove('hidden');
                    return;
                }

                if (emptyState) emptyState.classList.add('hidden');

                let bodyHtml = '';
                students.forEach((st, idx) => {
                    bodyHtml += `
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="sticky left-0 z-10 bg-white px-2 py-1.5 text-center font-medium text-slate-500 dark:bg-slate-900 dark:text-slate-400">${idx + 1}</td>
                            <td class="sticky left-[40px] z-10 bg-white px-2 py-1.5 font-mono text-[11px] text-slate-600 dark:bg-slate-900 dark:text-slate-300">${st.nis || '-'}</td>
                            <td class="sticky left-[130px] z-10 bg-white px-3 py-1.5 font-semibold text-slate-900 shadow-r dark:bg-slate-900 dark:text-white truncate max-w-[180px]">${st.name}</td>
                            <td class="px-2 py-1.5 text-slate-600 dark:text-slate-400 whitespace-nowrap">${st.kelas_name}</td>
                    `;

                    // Day Status Badges
                    for (let day = 1; day <= daysCount; day++) {
                        const dayInfo = st.days[day] || {};
                        const status = dayInfo.status;
                        const timeIn = dayInfo.time_in;
                        const isWeekend = dayInfo.is_weekend;

                        let badgeHtml = '';
                        if (status === 'hadir') {
                            const tooltip = `Tgl ${day}: Hadir` + (timeIn ? ` (${timeIn})` : '');
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-emerald-500 text-white shadow-xs" title="${tooltip}">H</span>`;
                        } else if (status === 'alpha') {
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-rose-500 text-white shadow-xs" title="Tgl ${day}: Alpha">A</span>`;
                        } else if (status === 'izin') {
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-amber-500 text-white shadow-xs" title="Tgl ${day}: Izin">I</span>`;
                        } else if (status === 'sakit') {
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-sky-500 text-white shadow-xs" title="Tgl ${day}: Sakit">S</span>`;
                        } else if (status === 'terlambat') {
                            const tooltip = `Tgl ${day}: Terlambat` + (timeIn ? ` (${timeIn})` : '');
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-orange-500 text-white shadow-xs" title="${tooltip}">T</span>`;
                        } else if (status === 'cuti') {
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-bold text-[10px] bg-purple-500 text-white shadow-xs" title="Tgl ${day}: Cuti">C</span>`;
                        } else {
                            const bg = isWeekend ? 'bg-slate-100 dark:bg-slate-800' : '';
                            badgeHtml = `<span class="inline-flex h-6 w-6 items-center justify-center rounded-md font-medium text-[10px] text-slate-300 dark:text-slate-600 ${bg}">-</span>`;
                        }

                        const cellClass = isWeekend ? 'bg-slate-50/50 dark:bg-slate-800/30' : '';
                        bodyHtml += `<td class="px-1 py-1 text-center border-x border-slate-100 dark:border-slate-800 ${cellClass}">${badgeHtml}</td>`;
                    }

                    // Summary Columns
                    const sum = st.summary || {};
                    bodyHtml += `
                        <td class="px-1.5 py-1 text-center font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20">${sum.hadir || 0}</td>
                        <td class="px-1.5 py-1 text-center font-bold text-rose-600 dark:text-rose-400 bg-rose-50/40 dark:bg-rose-950/20">${sum.alpha || 0}</td>
                        <td class="px-1.5 py-1 text-center font-bold text-amber-600 dark:text-amber-400 bg-amber-50/40 dark:bg-amber-950/20">${sum.izin || 0}</td>
                        <td class="px-1.5 py-1 text-center font-bold text-sky-600 dark:text-sky-400 bg-sky-50/40 dark:bg-sky-950/20">${sum.sakit || 0}</td>
                        <td class="px-1.5 py-1 text-center font-bold text-orange-600 dark:text-orange-400 bg-orange-50/40 dark:bg-orange-950/20">${sum.terlambat || 0}</td>
                        <td class="px-1.5 py-1 text-center font-bold text-purple-600 dark:text-purple-400 bg-purple-50/40 dark:bg-purple-950/20">${sum.cuti || 0}</td>
                    </tr>`;
                });

                if (body) body.innerHTML = bodyHtml;
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    loadMatrix();
                });
            }

            // Initial load
            loadMatrix();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonthlyMatrix);
    } else {
        initMonthlyMatrix();
    }
})();
