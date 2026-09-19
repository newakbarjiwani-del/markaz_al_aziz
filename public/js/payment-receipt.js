(function () {
    var PAYMENT_METHOD_LABELS = {
        '1140000': 'Tunai',
        '1140001': 'Manual BMI',
        '1140002': 'Saldo Keuangan',
        '1140003': 'Transfer Bank Lain',
        '1140004': 'INFAQ',
        '1140005': 'Transfer Bank BRI',
        '1200001': 'Loket Manual - Beasiswa',
        '1200002': 'Loket Manual - Potongan',
        '1': 'H2H VA BMI - ATM',
        '2': 'H2H VA BMI - Teller',
        '3': 'H2H VA BMI - IBANK',
        '4': 'H2H VA BMI - EDC',
        '5': 'H2H VA BMI - MOBILE',
        '6': 'ANDROID',
        tunai: 'Tunai',
        transfer: 'Transfer',
        qris: 'QRIS',
        va: 'Virtual Account',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function resolveOperatorName(receipt) {
        var receiptOperator = String(receipt?.operator || '').trim();
        if (receiptOperator && receiptOperator !== '-') {
            return receiptOperator;
        }

        var runtime = getRuntimeConfig();
        var pageOperator = String(runtime.operator || '').trim();
        if (pageOperator && pageOperator !== '-') {
            return pageOperator;
        }

        return '-';
    }

    function getRuntimeConfig() {
        var root = document.getElementById('pembayaran-page') || document.getElementById('riwayat-pembayaran-page');
        var instansi = {
            nama_instansi: root?.dataset.receiptNamaInstansi || '',
            nama_sub_1: root?.dataset.receiptNamaSub1 || '',
            nama_sub_2: root?.dataset.receiptNamaSub2 || '',
            akreditasi: root?.dataset.receiptAkreditasi || '',
            alamat: root?.dataset.receiptAlamat || '',
            kontak: {
                telepon: root?.dataset.receiptTelepon || '',
                email: root?.dataset.receiptEmail || '',
                website: root?.dataset.receiptWebsite || '',
            },
        };

        return {
            appName: root?.dataset.receiptAppName || 'Sekolah',
            operator: root?.dataset.receiptOperator || '-',
            location: root?.dataset.receiptLocation || '',
            logoUrl: root?.dataset.receiptLogo || '',
            instansi: instansi,
        };
    }

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function getContentWidth(pageSize, orientation, margins) {
        var sizes = {
            A4: [595.28, 841.89],
            A3: [841.89, 1190.55],
            LETTER: [612, 792],
            LEGAL: [612, 1008],
        };
        var key = String(pageSize || 'A4').toUpperCase();
        var size = sizes[key] || sizes.A4;
        var pageWidth = orientation === 'landscape' ? size[1] : size[0];
        var m = margins || [20, 20, 20, 20];
        return pageWidth - (m[0] || 0) - (m[2] || 0);
    }

    function normalizeMethodLabel(receipt) {
        if (receipt?.method_label) return receipt.method_label;
        return PAYMENT_METHOD_LABELS[String(receipt?.fidbank ?? receipt?.method ?? '')] || '-';
    }

    function normalizeReceipt(receipt) {
        var items = Array.isArray(receipt?.items) ? receipt.items : [];
        var totalAmount = Number(receipt?.total_amount || items.reduce(function (sum, item) {
            return sum + Number(item?.amount || 0);
        }, 0));

        return {
            reference: receipt?.reference || '-',
            paid_dt: receipt?.paid_dt || '-',
            method_label: normalizeMethodLabel(receipt),
            operator: resolveOperatorName(receipt),
            total_amount: totalAmount,
            total_label: receipt?.total_label || formatRupiah(totalAmount),
            school_name: receipt?.school?.name || 'Sekolah',
            siswa: {
                name: receipt?.siswa?.name || '-',
                nis: receipt?.siswa?.nis || '-',
                kelas: receipt?.siswa?.kelas || '-',
                virtual_account: receipt?.siswa?.virtual_account || '-',
            },
            items: items.map(function (item) {
                var amount = Number(item?.amount || 0);
                var billAmount = Number(item?.bill_amount != null ? item.bill_amount : amount);
                var paidTotal = Number(item?.paid_total != null ? item.paid_total : amount);
                return {
                    jenis: item?.jenis || '-',
                    periode: item?.periode || '-',
                    amount: amount,
                    amount_label: item?.amount_label || formatRupiah(amount),
                    bill_amount: billAmount,
                    bill_amount_label: item?.bill_amount_label || formatRupiah(billAmount),
                    paid_total: paidTotal,
                    paid_total_label: item?.paid_total_label || formatRupiah(paidTotal),
                    is_cicilan: !!item?.is_cicilan,
                };
            }),
        };
    }

    async function generateKuitansi(receiptPayload) {
        var receipt = normalizeReceipt(receiptPayload);

        var body = [
            {
                table: {
                    widths: ['18%', '32%', '18%', '32%'],
                    body: [
                        [{ text: 'No. Kuitansi', style: 'metaLabel' }, { text: ': ' + receipt.reference, style: 'metaValue' }, { text: 'Tanggal', style: 'metaLabel' }, { text: ': ' + receipt.paid_dt, style: 'metaValue' }],
                        [{ text: 'Nama Siswa', style: 'metaLabel' }, { text: ': ' + receipt.siswa.name, style: 'metaValue' }, { text: 'NIS', style: 'metaLabel' }, { text: ': ' + receipt.siswa.nis, style: 'metaValue' }],
                        [{ text: 'Kelas', style: 'metaLabel' }, { text: ': ' + receipt.siswa.kelas, style: 'metaValue' }, { text: 'No. VA', style: 'metaLabel' }, { text: ': ' + receipt.siswa.virtual_account, style: 'metaValue' }],
                        [{ text: 'Metode', style: 'metaLabel' }, { text: ': ' + receipt.method_label, style: 'metaValue' }, '', ''],
                    ],
                },
                layout: {
                    hLineWidth: function () { return 0; },
                    vLineWidth: function () { return 0; },
                    paddingTop: function () { return 1; },
                    paddingBottom: function () { return 1; },
                    paddingLeft: function () { return 0; },
                    paddingRight: function () { return 0; },
                },
                margin: [0, 0, 0, 8],
            },
        ];

        var itemRows = [
            [
                { text: 'No', style: 'tableHeader', alignment: 'center' },
                { text: 'Nama Tagihan', style: 'tableHeader' },
                { text: 'Periode', style: 'tableHeader' },
                { text: 'Total Tagihan', style: 'tableHeader', alignment: 'right', fontSize: 8.5 },
                { text: 'Total Terbayar', style: 'tableHeader', alignment: 'right', fontSize: 8.5 },
                { text: 'Dibayar', style: 'tableHeader', alignment: 'right', fontSize: 8.5 },
            ],
        ];

        receipt.items.forEach(function (item, index) {
            itemRows.push([
                { text: String(index + 1), alignment: 'center', fontSize: 8.5 },
                { text: item.jenis, fontSize: 8.5 },
                { text: item.periode, fontSize: 8.5 },
                { text: item.bill_amount_label, alignment: 'right', fontSize: 8.5 },
                { text: item.paid_total_label, alignment: 'right', fontSize: 8.5 },
                { text: item.amount_label, alignment: 'right', fontSize: 8.5 },
            ]);
        });

        itemRows.push([
            { text: 'Total Dibayar', colSpan: 5, style: 'tableHeader', alignment: 'right', fontSize: 9 }, {}, {}, {}, {},
            { text: receipt.total_label, style: 'tableHeader', alignment: 'right', fontSize: 9 },
        ]);

        body.push({
            table: {
                widths: ['5%', '26%', '15%', '18%', '18%', '18%'],
                body: itemRows,
            },
            layout: {
                fillColor: function (rowIndex) { return rowIndex === 0 ? '#ededed' : null; },
                hLineWidth: function () { return 0.6; },
                vLineWidth: function () { return 0.6; },
                hLineColor: function () { return '#7a7a7a'; },
                vLineColor: function () { return '#7a7a7a'; },
                paddingTop: function () { return 3; },
                paddingBottom: function () { return 3; },
                paddingLeft: function () { return 3; },
                paddingRight: function () { return 3; },
            },
            margin: [0, 0, 0, 4],
        });

        return {
            title: 'KUITANSI',
            schoolName: receipt.school_name,
            operator: receipt.operator,
            body: body,
        };
    }

    async function generatePdf(title, content, schoolName, operatorName) {
        if (!window.pdfMake) {
            throw new Error('PDF engine belum tersedia.');
        }

        var runtime = getRuntimeConfig();
        var operator = String(operatorName || '').trim() || runtime.operator || '-';
        var printDate = new Date().toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });

        var logoDataUrl = '';
        if (runtime.logoUrl) {
            try {
                logoDataUrl = await toDataUrl(runtime.logoUrl);
            } catch (_err) {
                logoDataUrl = '';
            }
        }

        var headerStack = [
            runtime.instansi?.nama_sub_1 ? { text: String(runtime.instansi.nama_sub_1).toUpperCase(), style: 'headerSub' } : null,
            runtime.instansi?.nama_sub_2 ? { text: String(runtime.instansi.nama_sub_2).toUpperCase(), style: 'headerSub' } : null,
            { text: String(runtime.instansi?.nama_instansi || runtime.appName || 'Sekolah').toUpperCase(), style: 'headerSchool' },
            runtime.instansi?.akreditasi ? { text: String(runtime.instansi.akreditasi), style: 'headerSub' } : null,
            runtime.instansi?.alamat ? { text: String(runtime.instansi.alamat), style: 'headerSubSmall', margin: [0, 1, 0, 0] } : null,
            ((runtime.instansi?.kontak?.telepon || runtime.instansi?.kontak?.email || runtime.instansi?.kontak?.website)
                ? {
                    text: [
                        runtime.instansi?.kontak?.telepon ? 'Telp: ' + runtime.instansi.kontak.telepon : null,
                        runtime.instansi?.kontak?.email ? 'Email: ' + runtime.instansi.kontak.email : null,
                        runtime.instansi?.kontak?.website ? 'Web: ' + runtime.instansi.kontak.website : null,
                    ].filter(Boolean).join(' | '),
                    style: 'headerSubSmall',
                }
                : null),
            { text: title.toUpperCase(), style: 'headerTitle', margin: [0, 5, 0, 0] },
        ].filter(Boolean);

        var pageMargins = [20, 20, 20, 20];
        var pageOrientation = 'portrait';
        var contentWidth = getContentWidth('A4', pageOrientation, pageMargins);

        var headerTable = {
            table: {
                widths: [62, '*'],
                body: [[
                    logoDataUrl ? { image: logoDataUrl, width: 52, alignment: 'center', margin: [0, 1, 0, 0] } : '',
                    {
                        stack: headerStack,
                        alignment: 'center',
                    },
                ]],
            },
            layout: 'noBorders',
            margin: [0, 0, 0, 6],
        };

        var signatureBlock = {
            columns: [
                { text: '', width: '*' },
                {
                    width: 210,
                    stack: [
                        { text: (runtime.location ? runtime.location + ', ' : '') + printDate, alignment: 'center' },
                        { text: 'Petugas Keuangan', alignment: 'center', margin: [0, 4, 0, 24] },
                        { text: operator, alignment: 'center', bold: true, decoration: 'underline' },
                    ],
                },
            ],
            margin: [0, 14, 0, 0],
        };

        var docContent = [
            headerTable,
            {
                canvas: [
                    { type: 'line', x1: 0, y1: 0, x2: contentWidth, y2: 0, lineWidth: 1.2 },
                    { type: 'line', x1: 0, y1: 3, x2: contentWidth, y2: 3, lineWidth: 0.5, lineColor: '#7a7a7a' },
                ],
                margin: [0, 0, 0, 6],
            },
        ].concat(Array.isArray(content) ? content : [content]).concat([signatureBlock]);

        var docDefinition = {
            pageSize: 'A4',
            pageOrientation: pageOrientation,
            pageMargins: pageMargins,
            content: docContent,
            styles: {
                headerSchool: { alignment: 'center', bold: true, fontSize: 13, letterSpacing: 0.15 },
                headerTitle: { alignment: 'center', bold: true, fontSize: 10, margin: [0, 2, 0, 0] },
                headerSub: { alignment: 'center', fontSize: 9 },
                headerSubSmall: { alignment: 'center', fontSize: 8.5 },
                tableHeader: { bold: true, fontSize: 9.5 },
                metaLabel: { bold: true, fontSize: 9.5 },
                metaValue: { fontSize: 9.5 },
            },
            defaultStyle: {
                fontSize: 9.5,
            },
        };

        var filename = (title || 'kuitansi').toLowerCase().replace(/\s+/g, '-') + '-' + Date.now() + '.pdf';
        window.pdfMake.createPdf(docDefinition).download(filename);
    }

    function toDataUrl(url) {
        return new Promise(function (resolve, reject) {
            var image = new Image();
            image.crossOrigin = 'anonymous';
            image.onload = function () {
                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = image.naturalWidth || image.width;
                    canvas.height = image.naturalHeight || image.height;
                    var ctx = canvas.getContext('2d');
                    if (!ctx) {
                        reject(new Error('Canvas context tidak tersedia.'));
                        return;
                    }
                    ctx.drawImage(image, 0, 0);
                    resolve(canvas.toDataURL('image/png'));
                } catch (error) {
                    reject(error);
                }
            };
            image.onerror = reject;
            image.src = url;
        });
    }

    window.generateKuitansi = generateKuitansi;
    window.generatePdfKuitansi = generatePdf;

    function buildReceiptHtml(receipt) {
        var itemsHtml = (receipt.items || []).map(function (item) {
            return '<tr class="receipt-item-row">'
                + '<td colspan="2">'
                + '<div class="receipt-item-title">' + escapeHtml(item.jenis || '-') + '</div>'
                + '<div class="receipt-item-periode">' + escapeHtml(item.periode || '-') + '</div>'
                + '<div class="receipt-item-amounts">'
                + '<div><span>Total Tagihan</span><strong>' + escapeHtml(item.bill_amount_label || item.amount_label || '-') + '</strong></div>'
                + '<div><span>Total Terbayar</span><strong>' + escapeHtml(item.paid_total_label || item.amount_label || '-') + '</strong></div>'
                + '<div><span>Dibayar</span><strong>' + escapeHtml(item.amount_label || '-') + '</strong></div>'
                + '</div>'
                + '</td>'
                + '</tr>';
        }).join('');

        return '<div class="payment-receipt-sheet">'
            + '<div class="receipt-title">' + escapeHtml(receipt.school?.name || receipt.school_name || 'Sekolah') + '</div>'
            + '<div class="receipt-subtitle">Kuitansi Pembayaran</div>'
            + '<div class="receipt-meta">'
            + '<p><strong>No. Kuitansi:</strong> ' + escapeHtml(receipt.reference || '-') + '</p>'
            + '<p><strong>Tanggal:</strong> ' + escapeHtml(receipt.paid_dt || '-') + '</p>'
            + '<p><strong>Siswa:</strong> ' + escapeHtml(receipt.siswa?.name || '-') + ' (' + escapeHtml(receipt.siswa?.nis || '-') + ')</p>'
            + '<p><strong>Kelas:</strong> ' + escapeHtml(receipt.siswa?.kelas || '-') + '</p>'
            + '<p><strong>Metode:</strong> ' + escapeHtml(receipt.method_label || '-') + '</p>'
            + '</div>'
            + '<div class="receipt-divider"></div>'
            + '<table><tbody>'
            + itemsHtml
            + '</tbody></table>'
            + '<div class="receipt-divider"></div>'
            + '<div class="receipt-total"><span>Total Dibayar</span><span>' + escapeHtml(receipt.total_label) + '</span></div>'
            + '<div class="receipt-signature">'
            + '<p>Petugas Keuangan</p>'
            + '<p class="receipt-signature-name">' + escapeHtml(receipt.operator || '-') + '</p>'
            + '</div>'
            + '<div class="receipt-footer">Terima kasih — simpan kuitansi ini sebagai bukti pembayaran.</div>'
            + '</div>';
    }

    window.printPaymentReceipt = function (receipt, button) {
        if (!receipt) return Promise.resolve();

        setPrintButtonLoading(button, true);

        return generateKuitansi(receipt)
            .then(function (doc) {
                return generatePdf(doc.title, doc.body, doc.schoolName, doc.operator);
            })
            .then(function () {
                window.showToast?.('Kuitansi berhasil dibuat.', 'success');
            })
            .catch(function () {
                // Fallback to browser print layout if PDF library fails/unavailable.
                var root = document.getElementById('payment-receipt-print-root');
                if (!root) {
                    throw new Error('Gagal membuat kuitansi.');
                }

                root.innerHTML = buildReceiptHtml(receipt);
                root.setAttribute('aria-hidden', 'false');

                function cleanup() {
                    root.innerHTML = '';
                    root.setAttribute('aria-hidden', 'true');
                    window.removeEventListener('afterprint', cleanup);
                }

                window.addEventListener('afterprint', cleanup);
                window.print();
            })
            .finally(function () {
                setPrintButtonLoading(button, false);
            });
    };

    window.fetchPaymentReceipt = function (receiptUrl, paymentIds) {
        if (!receiptUrl || !paymentIds?.length) {
            return Promise.reject(new Error('Data kuitansi tidak lengkap.'));
        }

        var params = new URLSearchParams();
        paymentIds.forEach(function (id) {
            params.append('ids[]', id);
        });

        return fetch(receiptUrl + '?' + params.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.json(); })
            .then(function (payload) {
                if (!payload.success) {
                    throw new Error(payload.message || 'Gagal memuat kuitansi');
                }

                return payload.data;
            });
    };

    function setPrintButtonLoading(button, loading) {
        if (!button) return;

        if (!button.dataset.defaultHtml) {
            button.dataset.defaultHtml = button.innerHTML;
        }

        button.disabled = loading;
        button.setAttribute('aria-busy', loading ? 'true' : 'false');
        button.classList.toggle('opacity-60', loading);
        button.classList.toggle('cursor-not-allowed', loading);

        if (loading) {
            button.innerHTML = '<i class="ti ti-loader-2 animate-spin mr-1" aria-hidden="true"></i>'
                + '<span class="btn-action-label">Membuat PDF...</span>';
            return;
        }

        button.innerHTML = button.dataset.defaultHtml;
    }

    var isPrintingReceipt = false;

    window.initPaymentReceiptPrintButtons = function (receiptUrl) {
        if (!receiptUrl || window.paymentReceiptPrintBound) return;
        window.paymentReceiptPrintBound = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-print-payment-receipt]');
            if (!button) return;

            event.preventDefault();

            if (isPrintingReceipt || button.disabled) {
                return;
            }

            var paymentIds = String(button.dataset.paymentIds || '')
                .split(',')
                .map(function (id) { return parseInt(id, 10); })
                .filter(Boolean);

            if (!paymentIds.length) return;

            isPrintingReceipt = true;
            setPrintButtonLoading(button, true);

            window.fetchPaymentReceipt(receiptUrl, paymentIds)
                .then(function (receipt) {
                    return window.printPaymentReceipt(receipt, button);
                })
                .catch(function (err) {
                    setPrintButtonLoading(button, false);
                    window.showAlert?.({
                        title: 'Gagal',
                        message: err.message || 'Gagal memuat kuitansi.',
                        variant: 'danger',
                    });
                })
                .finally(function () {
                    isPrintingReceipt = false;
                });
        });
    };
})();
