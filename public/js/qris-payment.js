(function () {
    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function ensureStyles() {
        if (document.getElementById('qris-payment-styles')) return;
        var style = document.createElement('style');
        style.id = 'qris-payment-styles';
        style.textContent = [
            '#qris-payment-modal{z-index:90}',
            '#qris-payment-modal .qris-modal{width:100%;max-width:24rem;border-radius:0.75rem;background:var(--surface-card,#fff);box-shadow:var(--shadow-lg);border:1px solid var(--surface-border-subtle,#efe6dc);overflow:hidden}',
            '#qris-payment-modal .qris-modal__header{display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;padding:1rem 1.25rem;border-bottom:1px solid var(--surface-border-subtle,#efe6dc);background:var(--surface-muted,#f3ebe3)}',
            '#qris-payment-modal .qris-modal__title{font-size:1.05rem;font-weight:600;color:var(--text-primary,#241308)}',
            '#qris-payment-modal .qris-modal__subtitle{margin-top:0.2rem;font-size:0.8125rem;color:var(--text-muted,#8a6a4e)}',
            '#qris-payment-modal .qris-modal__body{padding:1.25rem;display:flex;flex-direction:column;align-items:center;gap:0.85rem;text-align:center}',
            '#qris-payment-modal .qris-modal__canvas-wrap{padding:0.75rem;border-radius:0.75rem;border:1px solid var(--surface-border,#e0d0c0);background:#fff}',
            '#qris-payment-modal .qris-modal__amount{font-size:1.35rem;font-weight:700;color:var(--color-primary-700,#5c3317)}',
            '#qris-payment-modal .qris-modal__meta{font-size:0.75rem;line-height:1.4;color:var(--text-muted,#8a6a4e);max-width:18rem}',
            '#qris-payment-modal .qris-modal__status{display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:600}',
            '#qris-payment-modal .qris-modal__status--pending{background:var(--color-accent-100,#f5edd4);color:var(--color-accent-800,#5c4819)}',
            '#qris-payment-modal .qris-modal__status--paid{background:var(--color-primary-100,#f0e6dc);color:var(--color-primary-800,#4a2912)}',
            '#qris-payment-modal .qris-modal__actions{display:flex;flex-wrap:wrap;justify-content:center;gap:0.5rem;margin-top:0.25rem}',
            '.dark #qris-payment-modal .qris-modal{background:var(--surface-card,#24180f);border-color:var(--surface-border,#4a3524)}',
            '.dark #qris-payment-modal .qris-modal__header{background:var(--surface-muted,#2e1f14);border-color:var(--surface-border,#4a3524)}',
            '.dark #qris-payment-modal .qris-modal__canvas-wrap{border-color:var(--surface-border,#4a3524)}',
        ].join('');
        document.head.appendChild(style);
    }

    function ensureModal() {
        ensureStyles();
        var existing = document.getElementById('qris-payment-modal');
        if (existing) return existing;

        var modal = document.createElement('div');
        modal.id = 'qris-payment-modal';
        modal.className = 'fixed inset-0 hidden items-center justify-center bg-slate-900/50 p-4';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = ''
            + '<div class="qris-modal">'
            + '  <div class="qris-modal__header">'
            + '    <div>'
            + '      <h3 class="qris-modal__title">Scan QRIS</h3>'
            + '      <p id="qris-modal-subtitle" class="qris-modal__subtitle"></p>'
            + '    </div>'
            + '    <button type="button" id="qris-modal-close" class="btn-secondary btn-sm" aria-label="Tutup">Tutup</button>'
            + '  </div>'
            + '  <div class="qris-modal__body">'
            + '    <div class="qris-modal__canvas-wrap">'
            + '      <canvas id="qris-modal-canvas" width="220" height="220"></canvas>'
            + '    </div>'
            + '    <p id="qris-modal-amount" class="qris-modal__amount"></p>'
            + '    <p id="qris-modal-meta" class="qris-modal__meta"></p>'
            + '    <p id="qris-modal-status" class="qris-modal__status qris-modal__status--pending">'
            + '      <i class="ti ti-loader-2"></i> Menunggu pembayaran'
            + '    </p>'
            + '    <div class="qris-modal__actions">'
            + '      <button type="button" id="qris-modal-check" class="btn-primary btn-sm">'
            + '        <i class="ti ti-refresh mr-1"></i> Cek Status'
            + '      </button>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(modal);
        return modal;
    }

    function loadQrious(callback) {
        if (window.QRious) {
            callback();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
        script.onload = callback;
        script.onerror = function () {
            if (window.showAlert) {
                window.showAlert({ title: 'Gagal', message: 'Library QR gagal dimuat.', variant: 'danger' });
            }
        };
        document.head.appendChild(script);
    }

    window.QrisPaymentUi = {
        open: function (options) {
            var cfg = options || {};
            var modal = ensureModal();
            var canvas = document.getElementById('qris-modal-canvas');
            var subtitle = document.getElementById('qris-modal-subtitle');
            var amountEl = document.getElementById('qris-modal-amount');
            var metaEl = document.getElementById('qris-modal-meta');
            var statusEl = document.getElementById('qris-modal-status');
            var checkBtn = document.getElementById('qris-modal-check');
            var closeBtn = document.getElementById('qris-modal-close');
            var pollTimer = null;
            var current = cfg.qris || {};
            var paidNotified = false;

            function stopPoll() {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }

            function closeModal() {
                stopPoll();
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function renderQr(raw) {
                loadQrious(function () {
                    // eslint-disable-next-line no-new
                    new window.QRious({
                        element: canvas,
                        value: raw || '',
                        size: 220,
                        level: 'M',
                    });
                });
            }

            function applyQris(qris) {
                current = qris || current;
                if (subtitle) {
                    subtitle.textContent = (current.siswa && current.siswa.name)
                        ? (current.siswa.name + (current.siswa.nis ? ' · NIS ' + current.siswa.nis : ''))
                        : 'Scan kode dengan aplikasi e-wallet / m-banking';
                }
                if (amountEl) amountEl.textContent = current.amount_label || formatRupiah(current.amount);
                if (metaEl) {
                    var parts = [];
                    if (current.qris_id) parts.push('ID ' + current.qris_id);
                    if (current.expired_at) parts.push('Berlaku s/d ' + current.expired_at);
                    metaEl.textContent = parts.join(' · ') || 'QR dinamis untuk pelunasan tagihan';
                }
                if (statusEl) {
                    if (current.paid_flag || current.status === 'paid') {
                        statusEl.innerHTML = '<i class="ti ti-circle-check"></i> Pembayaran berhasil';
                        statusEl.className = 'qris-modal__status qris-modal__status--paid';
                        stopPoll();
                        if (!paidNotified && typeof cfg.onPaid === 'function') {
                            paidNotified = true;
                            cfg.onPaid(current);
                        }
                    } else {
                        statusEl.innerHTML = '<i class="ti ti-loader-2"></i> Menunggu pembayaran';
                        statusEl.className = 'qris-modal__status qris-modal__status--pending';
                    }
                }
                if (current.raw_qr_data) {
                    renderQr(current.raw_qr_data);
                }
            }

            function pollStatus(forceCheck) {
                if (!cfg.statusUrl && !cfg.checkUrl) return;
                var url = forceCheck && cfg.checkUrl ? cfg.checkUrl : cfg.statusUrl;
                var method = forceCheck && cfg.checkUrl ? 'POST' : 'GET';

                fetch(url, {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                })
                    .then(function (res) { return res.json().then(function (body) { return { res: res, body: body }; }); })
                    .then(function (result) {
                        if (!result.res.ok || !result.body || !result.body.success) {
                            return;
                        }
                        applyQris(result.body.data.qris || {});
                    })
                    .catch(function () {});
            }

            closeBtn.onclick = closeModal;
            modal.onclick = function (event) {
                if (event.target === modal) closeModal();
            };
            checkBtn.onclick = function () {
                pollStatus(true);
            };

            applyQris(current);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            stopPoll();
            pollTimer = setInterval(function () { pollStatus(false); }, 5000);
        },
    };
})();
