/**
 * Admin Kontrol RFID: set/change/reset student cashless PIN.
 */
(function () {
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function openPinModal(button) {
        var form = document.getElementById('cashless-pin-form');
        var modal = document.getElementById('cashless-pin-modal');
        var label = document.getElementById('cashless-pin-siswa-label');
        var currentWrap = document.getElementById('cashless-pin-current-wrap');
        var currentInput = document.getElementById('cashless-pin-current');
        if (!form || !modal) return;

        var pinSet = button.getAttribute('data-pin-set') === '1';
        var updateUrl = button.getAttribute('data-update-url') || '';
        var siswaLabel = button.getAttribute('data-siswa-label') || '-';

        form.setAttribute('action', updateUrl);
        form.reset();
        if (label) {
            label.textContent = siswaLabel + (pinSet ? ' (ubah PIN)' : ' (set PIN baru)');
        }
        if (currentWrap) {
            currentWrap.classList.toggle('hidden', !pinSet);
        }
        if (currentInput) {
            currentInput.required = pinSet;
            currentInput.value = '';
        }

        modal.classList.remove('hidden');
    }

    async function resetPin(button) {
        var resetUrl = button.getAttribute('data-reset-url') || '';
        var siswaLabel = button.getAttribute('data-siswa-label') || 'siswa';
        var siswaNis = button.getAttribute('data-siswa-nis') || '';
        if (!resetUrl) return;

        var detail = [
            { label: 'Siswa', value: siswaLabel },
        ];
        if (siswaNis) {
            detail.push({ label: 'NIS', value: siswaNis });
        }

        var confirmed = true;
        if (typeof window.showConfirm === 'function') {
            confirmed = await window.showConfirm({
                title: 'Reset PIN Cashless',
                message: 'PIN akan dihapus dari akun siswa. Tarik saldo di atas limit harian tidak bisa dilakukan sampai PIN diset ulang.',
                detail: detail,
                tone: 'warning',
                headerIcon: 'ti-key-off',
                confirmIcon: 'ti-key-off',
                confirmText: 'Ya, reset PIN',
                cancelText: 'Batal',
                footnote: 'PIN yang direset dapat diset ulang kapan saja oleh admin, cashless, atau orang tua.',
            });
        } else {
            confirmed = window.confirm('Reset PIN cashless untuk ' + siswaLabel + '?');
        }
        if (!confirmed) return;

        try {
            var res = await fetch(resetUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            var json = await res.json();
            if (!json.success) {
                window.showToast?.(json.message || 'Gagal reset PIN.', 'error');
                return;
            }
            window.showToast?.(json.message || 'PIN direset.', 'success');
            window.reloadMainTable?.();
        } catch (e) {
            window.showToast?.('Gagal reset PIN.', 'error');
        }
    }

    document.addEventListener('click', function (event) {
        var editBtn = event.target.closest('[data-cashless-pin-edit]');
        if (editBtn) {
            event.preventDefault();
            openPinModal(editBtn);
            return;
        }

        var resetBtn = event.target.closest('[data-cashless-pin-reset]');
        if (resetBtn) {
            event.preventDefault();
            resetPin(resetBtn);
        }
    });
})();
