document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('riwayat-pembayaran-page');
    if (!root) return;

    var receiptUrl = root.dataset.receiptUrl || '';
    var modal = document.getElementById('payment-detail-modal');
    var summaryEl = document.getElementById('payment-detail-summary');
    var itemsBody = document.getElementById('payment-detail-items-body');
    var totalEl = document.getElementById('payment-detail-total');
    var printBtn = document.getElementById('payment-detail-print-btn');

    function formatRp(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function setLoadingState() {
        if (summaryEl) {
            summaryEl.textContent = 'Memuat detail pembayaran...';
        }
        if (itemsBody) {
            itemsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">Memuat tagihan...</td></tr>';
        }
        if (totalEl) {
            totalEl.textContent = '-';
        }
        if (printBtn) {
            printBtn.classList.add('hidden');
            delete printBtn.dataset.paymentIds;
        }
    }

    function renderSummary(receipt) {
        if (!summaryEl) return;

        var siswa = receipt.siswa || {};
        summaryEl.innerHTML = '<div class="grid gap-3 sm:grid-cols-2">'
            + '<div><p class="text-slate-500">Siswa</p><p class="font-medium text-slate-900 dark:text-white">' + (siswa.name || '-') + '</p></div>'
            + '<div><p class="text-slate-500">NIS</p><p class="font-medium text-slate-900 dark:text-white">' + (siswa.nis || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Kelas</p><p class="font-medium text-slate-900 dark:text-white">' + (siswa.kelas || '-') + '</p></div>'
            + '<div><p class="text-slate-500">No. Kuitansi</p><p class="font-medium text-slate-900 dark:text-white">' + (receipt.reference || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Metode</p><p class="font-medium text-slate-900 dark:text-white">' + (receipt.method_label || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Tanggal Bayar</p><p class="font-medium text-slate-900 dark:text-white">' + (receipt.paid_dt || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Petugas</p><p class="font-medium text-slate-900 dark:text-white">' + (receipt.operator || '-') + '</p></div>'
            + '<div><p class="text-slate-500">Jumlah Tagihan</p><p class="font-medium text-slate-900 dark:text-white">' + Number(receipt.item_count || 0).toLocaleString('id-ID') + ' item</p></div>'
            + '<div class="sm:col-span-2"><p class="text-slate-500">Total Tagihan</p><p class="text-lg font-semibold text-primary-700 dark:text-primary-300">' + (receipt.total_label || formatRp(receipt.total_amount)) + '</p></div>'
            + '</div>';
    }

    function renderItems(items) {
        if (!itemsBody) return;

        if (!items || !items.length) {
            itemsBody.innerHTML = '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500">Tidak ada detail tagihan.</td></tr>';
            return;
        }

        itemsBody.innerHTML = items.map(function (item) {
            var billLabel = item.bill_amount_label || formatRp(item.bill_amount || item.amount);
            var paidTotalLabel = item.paid_total_label || formatRp(item.paid_total != null ? item.paid_total : item.amount);
            var paidLabel = item.amount_label || formatRp(item.amount);
            var cicilanNote = item.is_cicilan ? '<span class="mt-0.5 block text-xs font-normal text-amber-600 dark:text-amber-400">Cicilan</span>' : '';

            return '<tr class="border-t border-slate-100 dark:border-slate-800">'
                + '<td class="px-4 py-3 font-medium text-slate-900 dark:text-white">' + (item.jenis || '-') + cicilanNote + '</td>'
                + '<td class="px-4 py-3 text-slate-600 dark:text-slate-300">' + (item.periode || '-') + '</td>'
                + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + billLabel + '</td>'
                + '<td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">' + paidTotalLabel + '</td>'
                + '<td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">' + paidLabel + '</td>'
                + '</tr>';
        }).join('');
    }

    function openPaymentDetail(paymentId) {
        if (!modal || !paymentId || !receiptUrl) return;

        setLoadingState();
        modal.classList.remove('hidden');

        window.fetchPaymentReceipt(receiptUrl, [paymentId])
            .then(function (receipt) {
                renderSummary(receipt);
                renderItems(receipt.items || []);
                if (totalEl) {
                    totalEl.textContent = receipt.total_label || formatRp(receipt.total_amount);
                }
                if (printBtn) {
                    printBtn.dataset.paymentIds = String(receipt.payment_id || paymentId);
                    printBtn.classList.remove('hidden');
                }
            })
            .catch(function (err) {
                window.showAlert?.({
                    title: 'Gagal',
                    message: err.message || 'Gagal memuat detail pembayaran.',
                    variant: 'danger',
                });
                modal.classList.add('hidden');
            });
    }

    document.addEventListener('click', function (event) {
        var detailBtn = event.target.closest('[data-payment-detail]');
        if (!detailBtn) {
            return;
        }

        var paymentId = parseInt(detailBtn.dataset.paymentId || '', 10);
        if (paymentId) {
            openPaymentDetail(paymentId);
        }
    });

    window.initPaymentReceiptPrintButtons?.(receiptUrl);
});
