(function () {
    function formatRupiah(value) {
        var amount = Number(value || 0);
        return 'Rp ' + (window.formatNumberId ? window.formatNumberId(amount) : amount.toLocaleString('id-ID'));
    }

    function updateCards(payload) {
        var kreditEl = document.getElementById('kas-manual-total-kredit');
        var debetEl = document.getElementById('kas-manual-total-debet');
        var saldoEl = document.getElementById('kas-manual-saldo-bersih');

        if (!kreditEl || !debetEl || !saldoEl || !payload) {
            return;
        }

        kreditEl.textContent = formatRupiah(payload.total_kredit);
        debetEl.textContent = formatRupiah(payload.total_debet);
        saldoEl.textContent = formatRupiah(payload.saldo_bersih);
        saldoEl.classList.toggle('text-green-600', payload.saldo_bersih >= 0);
        saldoEl.classList.toggle('text-red-600', payload.saldo_bersih < 0);
    }

    function loadStats() {
        var config = document.getElementById('kas-manual-page-config');
        if (!config) {
            return;
        }

        var statsUrl = config.dataset.statsUrl;
        if (!statsUrl) {
            return;
        }

        var params = new URLSearchParams();
        var filterForm = document.getElementById('filter-form');
        if (filterForm) {
            params = new URLSearchParams(new FormData(filterForm));
        }

        fetch(statsUrl + '?' + params.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                updateCards(payload.data || payload);
            })
            .catch(function () {});
    }

    window.refreshKasManualStats = loadStats;

    function hookTableReload() {
        var originalReload = window.reloadMainTable;
        if (!originalReload || originalReload.__kasManualHooked) {
            return;
        }

        function wrappedReload() {
            var result = originalReload.apply(this, arguments);
            loadStats();
            return result;
        }

        wrappedReload.__kasManualHooked = true;
        window.reloadMainTable = wrappedReload;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('kas-manual-page-config')) {
            return;
        }

        hookTableReload();

        var filterForm = document.getElementById('filter-form');
        filterForm?.addEventListener('submit', function () {
            setTimeout(loadStats, 0);
        });
        filterForm?.addEventListener('reset', function () {
            setTimeout(loadStats, 20);
        });

        document.addEventListener('fetch-success', function (event) {
            if (event.target?.id === 'kas-manual-form') {
                loadStats();
            }
        });
    });
})();
