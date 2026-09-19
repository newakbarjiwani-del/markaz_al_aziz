<script>
(function () {
    var vaPrefix = @json(\App\Support\VirtualAccountNumber::prefix());
    var nisMaxLength = @json(\App\Support\VirtualAccountNumber::NIS_MAX_LENGTH);
    var vaNisLength = @json(\App\Support\VirtualAccountNumber::VA_NIS_LENGTH);
    var form = document.getElementById('student-form');
    var nisInput = form?.querySelector('[name="nis"]');
    var rfidInput = document.getElementById('student-rfid-input');
    var vaDisplay = document.getElementById('student-va-display');
    var warningBox = document.getElementById('student-va-suffix-warning');
    var warningText = document.getElementById('student-va-suffix-warning-text');
    var vaCheckUrl = warningBox?.dataset.vaCheckUrl || '';
    var lockedNis = null;
    var ignoreSiswaId = null;
    var vaCheckTimer = null;
    var vaCheckSeq = 0;

    function sanitizeRfid(value) {
        return String(value || '').replace(/[\x00-\x1F\x7F]/g, '').trim();
    }

    function digitsOnly(value, maxLen) {
        return String(value || '').replace(/\D/g, '').slice(0, maxLen);
    }

    function clearVaWarning() {
        if (!warningBox || !warningText) return;
        warningBox.classList.add('hidden');
        warningText.textContent = '';
    }

    function showVaWarning(message) {
        if (!warningBox || !warningText) return;
        if (!message) {
            clearVaWarning();
            return;
        }
        warningText.textContent = message;
        warningBox.classList.remove('hidden');
    }

    function resolveIgnoreSiswaId() {
        if (ignoreSiswaId) return ignoreSiswaId;
        var action = form?.action || '';
        var match = action.match(/\/data-siswa\/(\d+)(?:\/|$|\?)/);
        return match ? parseInt(match[1], 10) : null;
    }

    function checkVaSuffixCollision() {
        if (!vaCheckUrl || !nisInput) return;
        var digits = digitsOnly(nisInput.value, nisMaxLength);
        if (!digits) {
            clearVaWarning();
            return;
        }

        var seq = ++vaCheckSeq;
        var params = new URLSearchParams({ nis: digits });
        var ignoreId = resolveIgnoreSiswaId();
        if (ignoreId) {
            params.set('ignore_id', String(ignoreId));
        }

        fetch(vaCheckUrl + '?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json(); })
            .then(function (payload) {
                if (seq !== vaCheckSeq) return;
                showVaWarning(payload?.data?.warning || null);
            })
            .catch(function () {
                if (seq !== vaCheckSeq) return;
                clearVaWarning();
            });
    }

    function scheduleVaSuffixCheck() {
        if (vaCheckTimer) clearTimeout(vaCheckTimer);
        vaCheckTimer = setTimeout(checkVaSuffixCollision, 350);
    }

    function syncVirtualAccount() {
        if (!nisInput || !vaDisplay) return;
        var digits = digitsOnly(nisInput.value, nisMaxLength);
        if (nisInput.value !== digits) {
            nisInput.value = digits;
        }
        // Match VirtualAccountNumber::fromNis — VA uses the last 10 digits.
        var suffix = digits.slice(-vaNisLength);
        vaDisplay.value = digits ? vaPrefix + suffix.padStart(vaNisLength, '0') : '';
        scheduleVaSuffixCheck();
    }

    function restoreLockedNis() {
        if (nisInput && lockedNis !== null) {
            nisInput.value = lockedNis;
            syncVirtualAccount();
        }
    }

    function applyMisplacedRfidScan(raw) {
        var scanned = sanitizeRfid(raw);
        if (!scanned || !rfidInput) return false;
        restoreLockedNis();
        rfidInput.value = scanned;
        rfidInput.focus();
        rfidInput.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    }

    function bindRfidScannerField(input) {
        if (!input || input.dataset.rfidScanBound) return;
        input.dataset.rfidScanBound = '1';

        input.addEventListener('keydown', function (event) {
            // Keyboard-wedge RFID scanners end with Enter; don't submit the student form.
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

    // Capture-phase: block Enter submit from the RFID field even if bubble handlers miss it.
    form?.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        if (event.target === rfidInput || event.target?.getAttribute?.('name') === 'rfid_uid') {
            event.preventDefault();
            event.stopPropagation();
            if (rfidInput) {
                rfidInput.value = sanitizeRfid(rfidInput.value);
            }
        }
    }, true);

    nisInput?.addEventListener('input', function () {
        var raw = nisInput.value || '';
        // Scanner often lands on the focused NIS field first; move letter/hex UIDs to RFID.
        if (/[A-Za-z_-]/.test(raw) || /^S\d/i.test(raw.trim())) {
            applyMisplacedRfidScan(raw);
            return;
        }
        syncVirtualAccount();
    });

    bindRfidScannerField(rfidInput);

    document.addEventListener('edit-record-populated', function (event) {
        if (event.detail?.form !== form) return;
        lockedNis = digitsOnly(event.detail?.record?.nis ?? nisInput?.value, nisMaxLength);
        ignoreSiswaId = event.detail?.record?.id ? parseInt(event.detail.record.id, 10) : null;
        // Re-apply after maxlength/browser quirks — keep full NIS intact.
        if (nisInput && lockedNis) {
            nisInput.value = lockedNis;
        }
        syncVirtualAccount();
        
        // Populate alamat field
        var addressField = document.getElementById('student-address');
        if (addressField) {
            addressField.value = event.detail?.record?.address || '';
        }
        
        // Populate birth_date correctly (avoid timezone offset)
        var birthDateField = document.getElementById('student-birth-date');
        if (birthDateField && event.detail?.record?.birth_date) {
            var dateStr = String(event.detail.record.birth_date).substring(0, 10);
            birthDateField.value = dateStr;
        }
        
        if (rfidInput) {
            rfidInput.value = sanitizeRfid(rfidInput.value);
            // Default focus for edit: RFID scan won't overwrite NIS.
            setTimeout(function () {
                rfidInput.focus();
                rfidInput.select();
            }, 50);
        }
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-open-modal="student-modal"]')) return;
        lockedNis = null;
        ignoreSiswaId = null;
        clearVaWarning();
        // Create mode: clear VA preview only (RFID stays empty by default).
        setTimeout(syncVirtualAccount, 80);
    });

    document.addEventListener('fetch-success', function (event) {
        if (event.target !== form) return;
        lockedNis = null;
        ignoreSiswaId = null;
        if (vaDisplay) vaDisplay.value = '';
        if (rfidInput) rfidInput.value = '';
        clearVaWarning();
    });

    function virtualAccountFromNis(nis) {
        var digits = digitsOnly(nis, nisMaxLength);
        if (!digits) return '-';
        var suffix = digits.slice(-vaNisLength);
        return vaPrefix + suffix.padStart(vaNisLength, '0');
    }

    window.buildStudentNisUpdateConfirm = function () {
        // Create: no confirm. Update without NIS change: no confirm.
        if (lockedNis === null) {
            return { skipConfirm: true };
        }

        var nextNis = digitsOnly(nisInput?.value, nisMaxLength);
        if (!nextNis || nextNis === lockedNis) {
            return { skipConfirm: true };
        }

        return {
            title: 'Konfirmasi Ubah NIS',
            message: 'Mengubah NIS dapat mengubah nomor virtual account siswa.',
            tone: 'warning',
            confirmText: 'Ya, Simpan Perubahan',
            confirmIcon: 'ti-device-floppy',
            headerIcon: 'ti-alert-triangle',
            footnote: 'Pastikan NIS dan No. VA sudah sesuai sebelum melanjutkan.',
            detail: [
                { label: 'NIS lama', value: lockedNis },
                { label: 'NIS baru', value: nextNis },
                { label: 'VA lama', value: virtualAccountFromNis(lockedNis) },
                { label: 'VA baru', value: virtualAccountFromNis(nextNis) },
            ],
        };
    };
})();
</script>
