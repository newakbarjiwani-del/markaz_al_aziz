/**
 * Kontrol RFID — populate student detail panel when edit modal opens.
 * Also harden RFID input against keyboard-wedge scanners (Enter submits form).
 */
(function () {
    'use strict';

    function sanitizeRfid(value) {
        return String(value || '').replace(/[\x00-\x1F\x7F]/g, '').trim();
    }

    function bindRfidScannerField(input) {
        if (!input || input.dataset.rfidScanBound) return;
        input.dataset.rfidScanBound = '1';

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                input.value = sanitizeRfid(input.value);
            }
        });

        input.addEventListener('paste', function (event) {
            event.preventDefault();
            var text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
            input.value = sanitizeRfid(text);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        input.addEventListener('blur', function () {
            var cleaned = sanitizeRfid(input.value);
            if (input.value !== cleaned) {
                input.value = cleaned;
            }
        });
    }

    bindRfidScannerField(document.getElementById('rfid-update-uid'));

    document.addEventListener('edit-record-populated', function (event) {
        var detail = event.detail || {};
        var form = detail.form;
        if (!form || form.id !== 'rfid-update-form') {
            return;
        }

        var record = detail.record || {};
        form.querySelectorAll('[data-rfid-detail]').forEach(function (el) {
            var key = el.getAttribute('data-rfid-detail');
            var value = record[key];
            el.textContent = value !== null && value !== undefined && String(value).trim() !== ''
                ? String(value)
                : '—';
        });

        var uidInput = form.querySelector('[name="rfid_uid"]');
        if (uidInput) {
            uidInput.value = sanitizeRfid(uidInput.value);
            bindRfidScannerField(uidInput);
            setTimeout(function () { uidInput.focus(); }, 50);
        }

        var link = document.getElementById('rfid-edit-siswa-link');
        var button = detail.button;
        if (link && button && button.dataset.siswaShowUrl) {
            link.href = button.dataset.siswaShowUrl;
            link.classList.remove('hidden');
        } else if (link) {
            link.href = '#';
            link.classList.add('hidden');
        }
    });
})();
