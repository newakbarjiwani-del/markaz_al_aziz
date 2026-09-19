(function () {
    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function buildSummaryUrl(baseUrl, form) {
        if (!baseUrl || !form) {
            return '';
        }

        var url = new URL(baseUrl, window.location.origin);
        var params = new URLSearchParams(new FormData(form));
        params.forEach(function (value, key) {
            url.searchParams.set(key, value);
        });

        return url.toString();
    }

    function applySummary(root, data) {
        root.querySelectorAll('[data-summary-key]').forEach(function (el) {
            var key = el.dataset.summaryKey || '';
            el.textContent = formatRupiah(data?.[key] || 0);
        });
    }

    function initCashlessTransaksiSummary() {
        var root = document.getElementById('cashless-transaksi-summary');
        var filterForm = document.getElementById('filter-form');
        if (!root || !filterForm) {
            return;
        }

        var summaryUrl = root.dataset.summaryUrl || '';
        if (!summaryUrl) {
            return;
        }

        var loading = false;

        function loadSummary() {
            if (loading) {
                return;
            }

            loading = true;

            fetch(buildSummaryUrl(summaryUrl, filterForm), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return { ok: response.ok, payload: payload };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload?.success) {
                        throw new Error(result.payload?.message || 'Gagal memuat ringkasan transaksi.');
                    }

                    applySummary(root, result.payload.data || {});
                })
                .catch(function () {
                    window.showToast?.('Gagal memuat ringkasan transaksi.', 'error');
                })
                .finally(function () {
                    loading = false;
                });
        }

        filterForm.addEventListener('submit', function () {
            loadSummary();
        });

        filterForm.addEventListener('reset', function () {
            setTimeout(loadSummary, 20);
        });

        loadSummary();
    }

    document.addEventListener('DOMContentLoaded', initCashlessTransaksiSummary);
})();
