(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderPotonganModal(payload) {
        var modal = document.getElementById('tagihan-potongan-modal');
        var body = document.getElementById('tagihan-potongan-body');
        if (!modal || !body || !payload) return;

        var tagihan = payload.tagihan || {};
        var rows = Array.isArray(payload.pemakaian) ? payload.pemakaian : [];
        var summary = ''
            + '<div class="grid gap-2 sm:grid-cols-3 mb-4 text-sm">'
            + '<div><span class="text-slate-500">Bruto</span><p class="font-semibold">Rp ' + escapeHtml((tagihan.amount_bruto || 0).toLocaleString('id-ID')) + '</p></div>'
            + '<div><span class="text-slate-500">Total Potongan</span><p class="font-semibold text-amber-700">Rp ' + escapeHtml((tagihan.potongan_amount || 0).toLocaleString('id-ID')) + '</p></div>'
            + '<div><span class="text-slate-500">Tagihan (Net)</span><p class="font-semibold">Rp ' + escapeHtml((tagihan.amount || 0).toLocaleString('id-ID')) + '</p></div>'
            + '</div>';

        var table = '<div class="overflow-x-auto"><table class="table w-full text-sm"><thead><tr>'
            + '<th class="text-left">Urutan</th><th class="text-left">Jenis</th><th class="text-right">Potongan</th><th class="text-right">Sisa</th>'
            + '</tr></thead><tbody>';

        if (!rows.length) {
            table += '<tr><td colspan="4" class="text-center text-slate-500 py-4">Belum ada pemakaian potongan.</td></tr>';
        } else {
            rows.forEach(function (row) {
                table += '<tr>'
                    + '<td>' + escapeHtml(row.urutan) + '</td>'
                    + '<td>' + escapeHtml(row.jenis_potongan) + '</td>'
                    + '<td class="text-right">' + escapeHtml(row.potongan_label) + '</td>'
                    + '<td class="text-right">' + escapeHtml(row.amount_net_label) + '</td>'
                    + '</tr>';
            });
        }

        table += '</tbody></table></div>';
        body.innerHTML = summary + table;
        modal.classList.remove('hidden');
    }

    document.addEventListener('click', function (event) {
        var detailBtn = event.target.closest('[data-tagihan-potongan-detail]');
        if (detailBtn) {
            var url = detailBtn.getAttribute('data-show-url');
            if (!url) return;
            fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json.success) {
                        window.showToast(json.message || 'Gagal memuat detail potongan.', 'error');
                        return;
                    }
                    renderPotonganModal(json.data);
                })
                .catch(function () {
                    window.showToast('Gagal memuat detail potongan.', 'error');
                });
            return;
        }

        var applyBtn = event.target.closest('[data-tagihan-potongan-apply]');
        if (!applyBtn) return;

        var applyUrl = applyBtn.getAttribute('data-apply-url');
        if (!applyUrl) return;

        var detailRaw = applyBtn.getAttribute('data-confirm-detail');
        var detail = [];
        try { detail = detailRaw ? JSON.parse(detailRaw) : []; } catch (e) { detail = []; }

        window.showConfirm({
            title: applyBtn.getAttribute('data-confirm-title') || 'Terapkan Potongan',
            message: applyBtn.getAttribute('data-confirm-message') || 'Terapkan potongan pada tagihan ini?',
            detail: detail,
            confirmText: applyBtn.getAttribute('data-confirm-text') || 'Terapkan',
            tone: applyBtn.getAttribute('data-confirm-tone') || 'info',
        }).then(function (confirmed) {
            if (!confirmed) return;

            fetch(applyUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({}),
            })
                .then(function (response) { return response.json().then(function (json) { return { ok: response.ok, json: json }; }); })
                .then(function (result) {
                    if (!result.ok || !result.json.success) {
                        window.showToast(result.json.message || 'Potongan gagal diterapkan.', 'error');
                        return;
                    }
                    window.showToast(result.json.message || 'Potongan berhasil diterapkan.', 'success');
                    if (typeof window.reloadMainTable === 'function') {
                        window.reloadMainTable();
                    }
                })
                .catch(function () {
                    window.showToast('Potongan gagal diterapkan.', 'error');
                });
        });
    });
})();
