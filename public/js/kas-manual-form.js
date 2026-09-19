(function () {
    function form() {
        return document.getElementById('kas-manual-form');
    }

    function syncArahLabel(root) {
        var selected = root.querySelector('input[name="arah"]:checked');
        var label = document.getElementById('kas-nominal-label');
        if (!label) return;

        label.textContent = selected?.value === 'pengeluaran'
            ? 'Nominal Pengeluaran'
            : 'Nominal Pemasukan';
    }

    function resetCreateDefaults() {
        var root = form();
        if (!root) return;

        var today = new Date().toISOString().slice(0, 10);
        var tanggal = root.querySelector('[name="tanggal"]');
        var pemasukan = root.querySelector('input[name="arah"][value="pemasukan"]');
        var nominal = root.querySelector('[name="nominal"]');

        if (tanggal) tanggal.value = today;
        if (pemasukan) pemasukan.checked = true;
        if (nominal) nominal.value = '';
        syncArahLabel(root);
    }

    window.initKasManualForm = function () {
        var root = form();
        if (!root || root.dataset.kasManualFormBound) {
            return;
        }

        root.dataset.kasManualFormBound = '1';

        root.querySelectorAll('input[name="arah"]').forEach(function (input) {
            input.addEventListener('change', function () {
                syncArahLabel(root);
            });
        });

        document.querySelector('[data-open-modal="kas-manual-modal"]')?.addEventListener('click', function () {
            setTimeout(resetCreateDefaults, 0);
        });

        document.addEventListener('edit-record-populated', function (event) {
            if (!event.detail || event.detail.form !== root) {
                return;
            }

            var record = event.detail.record || {};
            var arah = record.arah || 'pemasukan';
            var arahInput = root.querySelector('input[name="arah"][value="' + arah + '"]');
            if (arahInput) {
                arahInput.checked = true;
            }

            var nominal = root.querySelector('[name="nominal"]');
            if (nominal && record.nominal !== undefined && record.nominal !== null) {
                nominal.value = window.formatNumberId
                    ? window.formatNumberId(record.nominal)
                    : String(record.nominal);
            }

            syncArahLabel(root);
        });

        syncArahLabel(root);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initKasManualForm?.();
    });
})();
