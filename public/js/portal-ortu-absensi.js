(function () {
    function formatNumber(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function formatPercent(value) {
        return Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
        }) + '%';
    }

    function buildSummaryUrl(baseUrl, form) {
        if (!baseUrl) return '';

        var url = new URL(baseUrl, window.location.origin);
        var params = new URLSearchParams(new FormData(form));
        params.forEach(function (value, key) {
            url.searchParams.set(key, value);
        });

        return url.toString();
    }

    function applySummary(summaryRoot, summary) {
        summaryRoot.querySelectorAll('[data-summary-key]').forEach(function (el) {
            var key = el.dataset.summaryKey || '';
            var value = summary?.[key] || 0;

            if (el.dataset.summaryFormat === 'percent') {
                el.textContent = formatPercent(value);
                return;
            }

            el.textContent = formatNumber(value);
        });

        var caption = summaryRoot.querySelector('[data-summary-caption]');
        if (caption) {
            caption.textContent = formatNumber(summary?.hadir || 0)
                + ' hadir dari '
                + formatNumber(summary?.total || 0)
                + ' hari tercatat';
        }

        var monthlyCaption = summaryRoot.querySelector('[data-summary-caption-monthly]');
        if (monthlyCaption) {
            monthlyCaption.textContent = formatNumber(summary?.hadir_bulan_ini || 0)
                + ' hadir dari '
                + formatNumber(summary?.total_bulan_ini || 0)
                + ' hari · '
                + (summary?.bulan_ini_label || '');
        }
    }

    function initOrtuAbsensiSummary() {
        var summaryRoot = document.getElementById('ortu-absensi-summary');
        var filterForm = document.getElementById('filter-form');
        if (!summaryRoot || !filterForm) return;

        var summaryUrl = summaryRoot.dataset.summaryUrl || '';
        if (!summaryUrl) return;

        var isLoading = false;

        function loadSummary() {
            if (isLoading) return;
            isLoading = true;

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
                        throw new Error(result.payload?.message || 'Gagal memuat ringkasan absensi.');
                    }

                    applySummary(summaryRoot, result.payload.data || {});
                })
                .catch(function () {
                    window.showToast?.('Gagal memuat ringkasan absensi.', 'error');
                })
                .finally(function () {
                    isLoading = false;
                });
        }

        filterForm.addEventListener('submit', function () {
            loadSummary();
        });

        filterForm.addEventListener('reset', function () {
            setTimeout(loadSummary, 20);
        });
    }

    document.addEventListener('DOMContentLoaded', initOrtuAbsensiSummary);
})();
