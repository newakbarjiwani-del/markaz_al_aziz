(function () {
    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function parseNumber(v) {
        return parseInt(String(v || '').replace(/\D+/g, ''), 10) || 0;
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = qs('[data-cashless-pos]');
        if (!root) {
            return;
        }

        var lookupUrl = root.dataset.lookupUrl;
        var chargeUrl = root.dataset.chargeUrl;
        var activeCustId = null;
        var activeSaldo = 0;

        window.initFormattedNumbers?.(root);

        function setSiswa(payload) {
            var siswa = payload.siswa || {};
            activeCustId = siswa.CUSTID || null;
            activeSaldo = Number(payload.saldo || 0);
            qs('#pos-active-custid', root).value = activeCustId || '';
            qs('#pos-nis', root).textContent = siswa.NOCUST || siswa.NUM2ND || '—';
            qs('#pos-nama', root).textContent = siswa.NMCUST || '—';
            qs('#pos-kelas', root).textContent = ((siswa.DESC02 || '-') + ' / ' + (siswa.DESC03 || '-'));
            qs('#pos-pid-label', root).textContent = siswa.PID || '—';
            qs('#pos-saldo', root).textContent = formatRp(activeSaldo);
            qs('#pos-charge-btn', root).disabled = !activeCustId;
            qs('#pos-nominal', root).value = '0';
        }

        function clearSiswa() {
            setSiswa({ siswa: {}, saldo: 0 });
            activeCustId = null;
            qs('#pos-charge-btn', root).disabled = true;
        }

        async function lookup(body) {
            var res = await fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            var json = await res.json();
            if (!res.ok) {
                throw new Error(json.message || 'Lookup gagal.');
            }
            return json;
        }

        async function doLookup(body) {
            try {
                var json = await lookup(body);
                setSiswa(json.data || {});
                window.showToast?.(json.message || 'Siswa ditemukan.', 'success');
                qs('#pos-nominal', root)?.focus();
            } catch (error) {
                clearSiswa();
                window.showToast?.(error.message, 'error');
            }
        }

        qs('#pos-pid', root)?.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') {
                return;
            }
            e.preventDefault();
            var pid = this.value.trim();
            if (!pid) {
                return;
            }
            doLookup({ pid: pid }).then(function () {
                qs('#pos-pid', root).value = '';
            });
        });

        qs('#pos-lookup-btn', root)?.addEventListener('click', function () {
            var custId = parseInt(qs('#pos-custid', root).value, 10);
            if (!custId) {
                window.showToast?.('Isi CUSTID siswa.', 'warning');
                return;
            }
            doLookup({ CUSTID: custId });
        });

        root.querySelectorAll('.pos-quick').forEach(function (btn) {
            btn.addEventListener('click', function () {
                qs('#pos-nominal', root).value = Number(btn.dataset.amount || 0).toLocaleString('id-ID');
            });
        });

        qs('#pos-charge-btn', root)?.addEventListener('click', async function () {
            if (!activeCustId) {
                window.showToast?.('Cari siswa dulu.', 'warning');
                return;
            }
            var kantin = qs('#pos-kantin', root).value;
            if (!kantin) {
                window.showToast?.('Pilih kantin.', 'warning');
                return;
            }
            var nominal = parseNumber(qs('#pos-nominal', root).value);
            if (nominal < 1) {
                window.showToast?.('Nominal belanja tidak valid.', 'warning');
                return;
            }
            if (nominal > activeSaldo) {
                window.showToast?.('Saldo tidak cukup.', 'error');
                return;
            }

            var confirmed = await window.showConfirm?.({
                title: 'Konfirmasi Belanja',
                message: 'Catat belanja ' + formatRp(nominal) + '?',
                confirmText: 'Ya, Catat',
                tone: 'info',
            });
            if (!confirmed) {
                return;
            }

            try {
                var res = await fetch(chargeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        CUSTID: activeCustId,
                        NOMINAL: nominal,
                        KANTIN: parseInt(kantin, 10),
                    }),
                });
                var json = await res.json();
                if (!res.ok) {
                    throw new Error(json.message || 'Gagal mencatat belanja.');
                }
                var data = json.data || {};
                activeSaldo = Number(data.saldo || 0);
                qs('#pos-saldo', root).textContent = formatRp(activeSaldo);
                qs('#pos-nominal', root).value = '0';
                qs('#pos-last', root).textContent = 'Terakhir: ' + (data.tran?.TRANSNO || '-')
                    + ' · ' + formatRp(data.tran?.NOMINAL || 0)
                    + ' · sisa ' + formatRp(activeSaldo);
                window.showToast?.(json.message || 'Belanja dicatat.', 'success');
                qs('#pos-pid', root)?.focus();
            } catch (error) {
                window.showToast?.(error.message, 'error');
            }
        });
    });
})();
