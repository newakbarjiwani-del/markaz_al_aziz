(function () {
    function openModal(id) {
        document.getElementById(id)?.classList.remove('hidden');
    }

    function formatRp(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function renderHistory(rows) {
        var body = document.getElementById('cicilan-history-body');
        if (!body) {
            return;
        }

        if (!rows || !rows.length) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Belum ada pembayaran cicilan.</td></tr>';
            return;
        }

        body.innerHTML = rows.map(function (row, index) {
            return '<tr>'
                + '<td>' + (index + 1) + '</td>'
                + '<td>' + (row.TRXDATE || '-') + '</td>'
                + '<td>' + (row.NOREFF || '-') + '</td>'
                + '<td>' + (row.FIDBANK || '-') + '</td>'
                + '<td class="text-right">' + formatRp(row.KREDIT) + '</td>'
                + '</tr>';
        }).join('');
    }

    function setCancelButton(canCancel, cancelUrl) {
        var btn = document.getElementById('cicilan-cancel-btn');
        if (!btn) {
            return;
        }

        if (canCancel && cancelUrl) {
            btn.dataset.fetchDelete = cancelUrl;
            btn.classList.remove('hidden');
            return;
        }

        btn.classList.add('hidden');
        delete btn.dataset.fetchDelete;
    }

    document.addEventListener('click', function (event) {
        var viewBtn = event.target.closest('[data-tagihan-cicilan-view]');
        if (!viewBtn) {
            return;
        }

        setCancelButton(false, '');
        fetch(viewBtn.dataset.cicilanShowUrl || '', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        throw new Error(data.message || 'Gagal memuat cicilan.');
                    }
                    return data.data || {};
                });
            })
            .then(function (payload) {
                var summary = document.getElementById('cicilan-summary');
                if (summary) {
                    summary.textContent = (payload.BILLNM || '-')
                        + ' · Total ' + formatRp(payload.original)
                        + ' · Terbayar ' + formatRp(payload.paid)
                        + ' · Sisa ' + formatRp(payload.remaining);
                }

                renderHistory(payload.history || []);
                setCancelButton(
                    payload.can_cancel === true,
                    viewBtn.dataset.cicilanCancelUrl || payload.cancel_url || ''
                );
                openModal(viewBtn.dataset.modalTarget || 'cicilan-modal');
            })
            .catch(function (error) {
                window.showAlert?.({
                    title: 'Gagal',
                    message: error.message,
                    variant: 'danger',
                });
            });
    });

    document.getElementById('cicilan-cancel-btn')?.addEventListener('fetch-delete-success', function () {
        window.reloadMainTable?.();
    });
})();
