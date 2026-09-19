(function () {
    var modal = document.getElementById('portal-cashless-detail-modal');
    var body = document.getElementById('portal-cashless-detail-body');
    if (!modal || !body) {
        return;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function factRow(label, value) {
        return '<div class="portal-cashless-detail__row">'
            + '<dt>' + escapeHtml(label) + '</dt>'
            + '<dd>' + escapeHtml(value) + '</dd>'
            + '</div>';
    }

    function renderDetail(data) {
        var siswa = data.siswa || {};
        var amountTone = Number(data.debet || 0) > 0 ? 'debet' : 'kredit';
        var amountLabel = Number(data.debet || 0) > 0 ? 'Debet' : 'Kredit';
        var amountValue = Number(data.debet || 0) > 0
            ? (data.debet_display || '-')
            : (data.kredit_display || '-');

        body.innerHTML = ''
            + '<section class="form-section">'
            +   '<h4 class="form-section__title">Siswa</h4>'
            +   '<div class="form-section__body">'
            +     '<dl class="portal-cashless-detail__facts">'
            +       factRow('NIS', siswa.nis || '-')
            +       factRow('Nama', siswa.name || '-')
            +       factRow('Kelas', siswa.kelas || '-')
            +     '</dl>'
            +   '</div>'
            + '</section>'
            + '<section class="form-section">'
            +   '<h4 class="form-section__title">Transaksi</h4>'
            +   '<div class="form-section__body space-y-4">'
            +     '<div class="portal-cashless-detail__amount portal-cashless-detail__amount--' + amountTone + '">'
            +       '<span class="text-xs font-semibold uppercase tracking-wide opacity-80">' + amountLabel + '</span>'
            +       '<strong>' + escapeHtml(amountValue) + '</strong>'
            +     '</div>'
            +     '<dl class="portal-cashless-detail__facts">'
            +       factRow('Tanggal', data.tanggal || '-')
            +       factRow('Metode', data.metode || '-')
            +       factRow('Dompet', data.wallet || '-')
            +       factRow('Referensi', data.noreff || '-')
            +       factRow('Keterangan', data.description || '-')
            +       factRow('Bank / Channel', [data.fidbank, data.channel].filter(function (v) {
                        return v && v !== '-';
                    }).join(' · ') || '-')
            +       factRow('Operator', data.operator || '-')
            +     '</dl>'
            +   '</div>'
            + '</section>';
    }

    async function openDetail(url) {
        body.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400">Memuat detail transaksi...</p>';
        modal.classList.remove('hidden');

        try {
            var response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            var json = await response.json();
            if (!response.ok || !json.success) {
                throw new Error(json.message || 'Gagal memuat detail.');
            }
            renderDetail(json.data || {});
        } catch (error) {
            body.innerHTML = '<p class="text-sm text-red-600 dark:text-red-400">'
                + escapeHtml(error.message || 'Gagal memuat detail.')
                + '</p>';
            window.showToast?.(error.message || 'Gagal memuat detail.', 'error');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-portal-cashless-detail]');
        if (!button) {
            return;
        }

        var url = button.getAttribute('data-detail-url');
        if (!url) {
            return;
        }

        openDetail(url);
    });
})();
