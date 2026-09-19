document.addEventListener('DOMContentLoaded', function () {
    const containers = document.querySelectorAll('[data-ajax-url][id*="subject-period-summary"]');

    containers.forEach(container => {
        const ajaxUrl = container.dataset.ajaxUrl;
        const filterForm = container.querySelector('.subject-period-filter-form');
        const kelasSelect = container.querySelector('.subject-period-kelas-select');
        const pelajaranSelect = container.querySelector('.subject-period-pelajaran-select');
        const startDateInput = container.querySelector('.subject-period-start-date');
        const endDateInput = container.querySelector('.subject-period-end-date');
        const tableBody = container.querySelector('.subject-period-table-body');
        const loadingState = container.querySelector('.subject-period-loading');
        const emptyState = container.querySelector('.subject-period-empty');
        const searchInput = container.querySelector('.subject-period-search-input');
        const exportBtn = container.querySelector('.subject-period-export-excel');
        const exportLabel = container.querySelector('.subject-period-export-label');

        const statMapel = container.querySelector('.subject-period-stat-mapel');
        const statPeriode = container.querySelector('.subject-period-stat-periode');
        const statAvg = container.querySelector('.subject-period-stat-avg');
        const statDays = container.querySelector('.subject-period-stat-days');
        const statStudents = container.querySelector('.subject-period-stat-students');
        const statRisk = container.querySelector('.subject-period-stat-risk');

        let rawStudents = [];
        let lastMeta = null;
        let isExporting = false;

        function selectedText(selectEl) {
            if (!selectEl) return '-';
            const opt = selectEl.options[selectEl.selectedIndex];
            return opt ? String(opt.textContent || '').trim() : '-';
        }

        function filteredStudents() {
            const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
            return rawStudents.filter(s => {
                if (!q) return true;
                return (s.name && s.name.toLowerCase().includes(q))
                    || (s.nis && String(s.nis).toLowerCase().includes(q));
            });
        }

        function setExportEnabled(enabled) {
            if (!exportBtn) return;
            exportBtn.disabled = !enabled;
        }

        function fetchSummary() {
            if (!kelasSelect || !kelasSelect.value) {
                setExportEnabled(false);
                return;
            }

            if (loadingState) loadingState.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
            if (tableBody) tableBody.innerHTML = '';
            setExportEnabled(false);

            const params = new URLSearchParams({
                kelas_id: kelasSelect.value,
                pelajaran_id: pelajaranSelect ? pelajaranSelect.value : '',
                start_date: startDateInput ? startDateInput.value : '',
                end_date: endDateInput ? endDateInput.value : '',
            });

            fetch(`${ajaxUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async res => {
                const payload = await res.json().catch(() => null);
                if (!res.ok) {
                    throw new Error(payload?.message || ('HTTP error ' + res.status));
                }
                return payload || {};
            })
            .then(data => {
                rawStudents = data.students || [];
                lastMeta = {
                    kelas_name: selectedText(kelasSelect),
                    pelajaran_name: data.pelajaran_name || selectedText(pelajaranSelect) || 'Semua Mata Pelajaran',
                    period_label: data.period_label || '-',
                    start_date: data.start_date || (startDateInput ? startDateInput.value : ''),
                    end_date: data.end_date || (endDateInput ? endDateInput.value : ''),
                    class_avg_percentage: data.class_avg_percentage || 0,
                    total_sessions_count: data.total_sessions_count || 0,
                    active_school_days: data.active_school_days || 0,
                    total_students: data.total_students || 0,
                    students_at_risk_count: data.students_at_risk_count || 0,
                };

                if (statMapel) statMapel.textContent = lastMeta.pelajaran_name;
                if (statPeriode) statPeriode.textContent = lastMeta.period_label;
                if (statAvg) statAvg.textContent = `${lastMeta.class_avg_percentage}%`;
                if (statDays) {
                    statDays.textContent = `${lastMeta.total_sessions_count} Sesi (${lastMeta.active_school_days} Hari Aktif)`;
                }
                if (statStudents) statStudents.textContent = `${lastMeta.total_students} Siswa`;
                if (statRisk) statRisk.textContent = `${lastMeta.students_at_risk_count} Siswa`;

                renderTableRows();
                setExportEnabled(rawStudents.length > 0);
            })
            .catch(err => {
                console.error('Failed to load subject period summary:', err);
                rawStudents = [];
                lastMeta = null;
                setExportEnabled(false);
                if (tableBody) tableBody.innerHTML = '';
                if (emptyState) {
                    emptyState.textContent = err.message || 'Gagal memuat data rekapitulasi presensi.';
                    emptyState.classList.remove('hidden');
                }
            })
            .finally(() => {
                if (loadingState) loadingState.classList.add('hidden');
            });
        }

        function renderTableRows() {
            if (!tableBody) return;

            const filtered = filteredStudents();

            if (filtered.length === 0) {
                tableBody.innerHTML = '';
                if (emptyState) {
                    emptyState.textContent = rawStudents.length
                        ? 'Tidak ada siswa yang cocok dengan pencarian.'
                        : 'Tidak ada data siswa ditemukan.';
                    emptyState.classList.remove('hidden');
                }
                return;
            }

            if (emptyState) emptyState.classList.add('hidden');

            let html = '';
            filtered.forEach((s, idx) => {
                const pct = Number(s.percentage || 0);
                const hasConducted = !!s.has_conducted_sessions;
                const isRisk = hasConducted && pct < 75;

                let badgeHtml = '';
                if (!hasConducted) {
                    badgeHtml = `<span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-400 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-500">Belum Ada Sesi</span>`;
                } else {
                    let badgeBg = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
                    if (pct < 75) {
                        badgeBg = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
                    } else if (pct < 85) {
                        badgeBg = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
                    }
                    badgeHtml = `<span class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-bold shadow-2xs ${badgeBg}">${pct}%</span>`;
                }

                const rowTone = isRisk
                    ? 'bg-rose-50/40 dark:bg-rose-950/10'
                    : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40';

                html += `
                    <tr class="${rowTone} transition-all">
                        <td class="px-3 py-3 text-center text-slate-400 font-medium">${idx + 1}</td>
                        <td class="px-3 py-3 font-mono font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(s.nis || '-')}</td>
                        <td class="px-3 py-3 font-bold text-slate-900 dark:text-white">${escapeHtml(s.name || '-')}</td>
                        <td class="px-3 py-3 font-medium text-slate-600 dark:text-slate-400">${escapeHtml(s.kelas_name || '-')}</td>
                        <td class="px-3 py-3 text-slate-600 dark:text-slate-400 font-medium">${escapeHtml(s.pelajaran_name || 'Semua Mapel')}</td>
                        <td class="px-2 py-3 text-center font-bold text-slate-700 dark:text-slate-300">${s.total_sessions || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-emerald-700 bg-emerald-50/40 dark:bg-emerald-950/20 dark:text-emerald-300">${s.hadir || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-orange-700 bg-orange-50/40 dark:bg-orange-950/20 dark:text-orange-300">${s.terlambat || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-amber-700 bg-amber-50/40 dark:bg-amber-950/20 dark:text-amber-300">${s.izin || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-sky-700 bg-sky-50/40 dark:bg-sky-950/20 dark:text-sky-300">${s.sakit || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-purple-700 bg-purple-50/40 dark:bg-purple-950/20 dark:text-purple-300">${s.cuti || 0}</td>
                        <td class="px-2 py-3 text-center font-bold text-rose-700 bg-rose-50/40 dark:bg-rose-950/20 dark:text-rose-300">${s.alpha || 0}</td>
                        <td class="px-3 py-3 text-center">${badgeHtml}</td>
                    </tr>
                `;
            });

            tableBody.innerHTML = html;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function slugify(value) {
            return String(value || 'rekap')
                .trim()
                .replace(/[^\w\-]+/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '')
                .slice(0, 60) || 'rekap';
        }

        function applyBorder(cell, color) {
            cell.border = {
                top: { style: 'thin', color: { argb: color } },
                left: { style: 'thin', color: { argb: color } },
                bottom: { style: 'thin', color: { argb: color } },
                right: { style: 'thin', color: { argb: color } },
            };
        }

        function fillSolid(cell, argb) {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb } };
        }

        async function exportExcel() {
            if (isExporting) {
                window.showToast?.('Export sedang diproses, mohon tunggu.', 'info');
                return;
            }

            if (typeof ExcelJS === 'undefined') {
                window.showToast?.('Library Excel belum siap. Muat ulang halaman lalu coba lagi.', 'error');
                return;
            }

            const rows = filteredStudents();
            if (!rows.length || !lastMeta) {
                window.showToast?.('Tidak ada data untuk diekspor. Tampilkan rekap terlebih dahulu.', 'warning');
                return;
            }

            isExporting = true;
            if (exportBtn) exportBtn.disabled = true;
            if (exportLabel) exportLabel.textContent = 'Mengekspor...';

            try {
                const workbook = new ExcelJS.Workbook();
                workbook.creator = 'Nurul Muhtadin';
                workbook.created = new Date();

                const sheet = workbook.addWorksheet('Rekap Presensi', {
                    views: [{ state: 'frozen', ySplit: 13, xSplit: 0 }],
                    pageSetup: {
                        orientation: 'landscape',
                        fitToPage: true,
                        fitToWidth: 1,
                        fitToHeight: 0,
                        paperSize: 9,
                        printTitlesRow: '13:13',
                    },
                });

                const meta = lastMeta;
                const instansi = (container.dataset.instansi || '').trim() || 'Nurul Muhtadin';

                sheet.mergeCells('A1:M1');
                const instansiCell = sheet.getCell('A1');
                instansiCell.value = instansi.toUpperCase();
                instansiCell.font = { bold: true, size: 12, color: { argb: 'FF436137' } };
                instansiCell.alignment = { horizontal: 'center', vertical: 'middle' };

                sheet.mergeCells('A2:M2');
                const titleCell = sheet.getCell('A2');
                titleCell.value = 'REKAP AKUMULASI PRESENSI SISWA';
                titleCell.font = { bold: true, size: 16, color: { argb: 'FF1E293B' } };
                titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
                sheet.getRow(2).height = 24;

                sheet.mergeCells('A3:M3');
                sheet.getCell('A3').value = meta.period_label;
                sheet.getCell('A3').font = { size: 11, color: { argb: 'FF475569' } };
                sheet.getCell('A3').alignment = { horizontal: 'center' };

                const infoRows = [
                    ['Kelas', meta.kelas_name],
                    ['Mata Pelajaran', meta.pelajaran_name],
                    ['Periode', `${meta.start_date} s/d ${meta.end_date}`],
                    ['Rata-rata Kehadiran Kelas', `${meta.class_avg_percentage}%`],
                    ['Total Sesi / Hari Aktif', `${meta.total_sessions_count} sesi / ${meta.active_school_days} hari`],
                    ['Jumlah Siswa', `${meta.total_students} siswa`],
                    ['Perlu Perhatian (<75%)', `${meta.students_at_risk_count} siswa`],
                ];

                infoRows.forEach((pair, index) => {
                    const rowNumber = 5 + index;
                    sheet.getCell(`A${rowNumber}`).value = pair[0];
                    sheet.getCell(`A${rowNumber}`).font = { bold: true, color: { argb: 'FF334155' } };
                    sheet.mergeCells(`B${rowNumber}:D${rowNumber}`);
                    sheet.getCell(`B${rowNumber}`).value = pair[1];
                });

                sheet.mergeCells('A12:M12');
                sheet.getCell('A12').value = 'Keterangan: H=Hadir, T=Terlambat, I=Izin, S=Sakit, C=Cuti, A=Alpha. % Kehadiran = (Hadir + Terlambat) / Total Sesi. Baris merah muda = kehadiran < 75%.';
                sheet.getCell('A12').font = { italic: true, size: 9, color: { argb: 'FF64748B' } };

                const headers = [
                    'No', 'NIS', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Total Sesi',
                    'Hadir (H)', 'Terlambat (T)', 'Izin (I)', 'Sakit (S)', 'Cuti (C)', 'Alpha (A)', '% Kehadiran',
                ];
                const headerRow = sheet.getRow(13);
                headers.forEach((label, index) => {
                    const cell = headerRow.getCell(index + 1);
                    cell.value = label;
                    cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    applyBorder(cell, 'FF436137');
                });
                headerRow.height = 28;

                const headerFills = [
                    'FF436137', 'FF436137', 'FF436137', 'FF436137', 'FF436137', 'FF436137',
                    'FF059669', 'FFEA580C', 'FFD97706', 'FF0284C7', 'FF7C3AED', 'FFE11D48', 'FF436137',
                ];
                headerFills.forEach((color, index) => fillSolid(headerRow.getCell(index + 1), color));

                rows.forEach((s, idx) => {
                    const pct = Number(s.percentage || 0);
                    const hasConducted = !!s.has_conducted_sessions;
                    const isRisk = hasConducted && pct < 75;
                    const excelRow = sheet.addRow([
                        idx + 1,
                        s.nis || '-',
                        s.name || '-',
                        s.kelas_name || '-',
                        s.pelajaran_name || meta.pelajaran_name || 'Semua Mata Pelajaran',
                        Number(s.total_sessions || 0),
                        Number(s.hadir || 0),
                        Number(s.terlambat || 0),
                        Number(s.izin || 0),
                        Number(s.sakit || 0),
                        Number(s.cuti || 0),
                        Number(s.alpha || 0),
                        hasConducted ? pct / 100 : null,
                    ]);

                    excelRow.eachCell((cell, colNumber) => {
                        applyBorder(cell, 'FFCBD5E1');
                        cell.alignment = {
                            vertical: 'middle',
                            horizontal: [1, 6, 7, 8, 9, 10, 11, 12, 13].includes(colNumber) ? 'center' : 'left',
                        };

                        if (colNumber === 2) {
                            cell.numFmt = '@';
                        }
                        if (colNumber >= 6 && colNumber <= 12) {
                            cell.numFmt = '0';
                        }
                        if (colNumber === 13) {
                            cell.numFmt = '0.0%';
                            cell.font = { bold: true };
                        }
                    });

                    if (isRisk) {
                        for (let c = 1; c <= 13; c++) {
                            fillSolid(excelRow.getCell(c), 'FFFFE4E6');
                        }
                    } else if (idx % 2 === 1) {
                        for (let c = 1; c <= 13; c++) {
                            fillSolid(excelRow.getCell(c), 'FFF8FAFC');
                        }
                    }

                    // Soft tint status columns
                    fillSolid(excelRow.getCell(7), isRisk ? 'FFD1FAE5' : 'FFECFDF5');
                    fillSolid(excelRow.getCell(8), isRisk ? 'FFFFEDD5' : 'FFFFF7ED');
                    fillSolid(excelRow.getCell(9), isRisk ? 'FFFEF3C7' : 'FFFFFBEB');
                    fillSolid(excelRow.getCell(10), isRisk ? 'FFE0F2FE' : 'FFF0F9FF');
                    fillSolid(excelRow.getCell(11), isRisk ? 'FFEDE9FE' : 'FFF5F3FF');
                    fillSolid(excelRow.getCell(12), isRisk ? 'FFFFE4E6' : 'FFFFF1F2');
                });

                sheet.autoFilter = {
                    from: { row: 13, column: 1 },
                    to: { row: 13 + rows.length, column: 13 },
                };

                const widths = [5, 18, 32, 12, 22, 11, 11, 12, 10, 10, 10, 10, 12];
                widths.forEach((width, index) => {
                    sheet.getColumn(index + 1).width = width;
                });

                sheet.addRow([]);
                const noteRow = sheet.addRow([
                    `Diekspor ${new Date().toLocaleString('id-ID')} · Ditampilkan ${rows.length} dari ${rawStudents.length} siswa` +
                    (searchInput && searchInput.value.trim() ? ` · Filter cari: "${searchInput.value.trim()}"` : ''),
                ]);
                sheet.mergeCells(`A${noteRow.number}:M${noteRow.number}`);
                noteRow.getCell(1).font = { size: 9, italic: true, color: { argb: 'FF64748B' } };

                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                });
                const filename = [
                    'Rekap_Presensi',
                    slugify(meta.kelas_name),
                    slugify(meta.pelajaran_name),
                    slugify(meta.start_date),
                    slugify(meta.end_date),
                ].join('_') + '.xlsx';

                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(link.href);

                window.showToast?.('Excel rekap presensi berhasil diunduh.', 'success');
            } catch (error) {
                console.error('Export Excel failed:', error);
                window.showToast?.('Terjadi kesalahan saat export Excel.', 'error');
            } finally {
                isExporting = false;
                if (exportLabel) exportLabel.textContent = 'Export Excel';
                setExportEnabled(rawStudents.length > 0);
            }
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                fetchSummary();
            });
        }

        if (kelasSelect) {
            kelasSelect.addEventListener('change', fetchSummary);
        }

        if (pelajaranSelect) {
            pelajaranSelect.addEventListener('change', fetchSummary);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                renderTableRows();
            });
        }

        if (exportBtn) {
            exportBtn.addEventListener('click', exportExcel);
        }

        fetchSummary();
    });
});
