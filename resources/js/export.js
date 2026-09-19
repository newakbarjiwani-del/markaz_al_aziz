import ExcelJS from 'exceljs';
import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

pdfMake.vfs = pdfFonts.pdfMake?.vfs || pdfFonts.vfs;

export function initExportButtons() {
    document.getElementById('export-excel')?.addEventListener('click', exportExcel);
    document.getElementById('export-pdf')?.addEventListener('click', exportPdf);
}

async function exportExcel() {
    const table = document.getElementById('main_table');
    if (!table || !window.mainTable) return;

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('Export');
    const headers = [];
    table.querySelectorAll('thead th').forEach((th) => headers.push(th.textContent.trim()));
    sheet.addRow(headers);

    window.mainTable.rows({ search: 'applied' }).every(function () {
        const row = [];
        $(this.node()).find('td').each(function () {
            row.push($(this).text().trim());
        });
        if (row.length) sheet.addRow(row);
    });

    headers.forEach((_, i) => {
        sheet.getColumn(i + 1).width = 20;
    });

    const buffer = await workbook.xlsx.writeBuffer();
    downloadBlob(new Blob([buffer]), `${document.title || 'export'}.xlsx`);
    window.showToast?.('Excel berhasil diekspor.', 'success');
}

function exportPdf() {
    const table = document.getElementById('main_table');
    if (!table || !window.mainTable) return;

    const headers = [];
    table.querySelectorAll('thead th').forEach((th) => headers.push(th.textContent.trim()));

    const body = [headers.map((h) => ({ text: h, style: 'tableHeader' }))];

    window.mainTable.rows({ search: 'applied' }).every(function () {
        const row = [];
        $(this.node()).find('td').each(function () {
            row.push($(this).text().trim());
        });
        if (row.length) body.push(row);
    });

    const doc = {
        pageOrientation: 'landscape',
        content: [
            { text: document.title || 'Laporan', style: 'header', margin: [0, 0, 0, 10] },
            {
                table: {
                    headerRows: 1,
                    widths: headers.map(() => '*'),
                    body,
                },
                layout: 'lightHorizontalLines',
            },
        ],
        styles: {
            header: { fontSize: 16, bold: true },
            tableHeader: { bold: true, fillColor: '#f1f5f9' },
        },
        defaultStyle: { fontSize: 9 },
    };

    pdfMake.createPdf(doc).download(`${document.title || 'export'}.pdf`);
    window.showToast?.('PDF berhasil diekspor.', 'success');
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
