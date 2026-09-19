document.addEventListener('DOMContentLoaded', function () {
    window.resolveModalTitle = function (modalId, mode, explicitTitle) {
        if (explicitTitle && String(explicitTitle).trim()) {
            return String(explicitTitle).trim();
        }

        var titleEl = document.getElementById(modalId + '-title');
        if (!titleEl) return '';

        var defaultTitle = titleEl.dataset.defaultTitle || titleEl.textContent.trim();
        if (!titleEl.dataset.defaultTitle) {
            titleEl.dataset.defaultTitle = defaultTitle;
        }

        var combined = defaultTitle.match(/^Tambah\s*\/\s*Ubah\s+(.+)$/i);
        if (combined) {
            return (mode === 'edit' ? 'Ubah ' : 'Tambah ') + combined[1];
        }

        if (mode === 'edit' && /^Tambah\s+/i.test(defaultTitle)) {
            return defaultTitle.replace(/^Tambah\s+/i, 'Ubah ');
        }

        if (mode === 'create' && /^Ubah\s+/i.test(defaultTitle)) {
            return defaultTitle.replace(/^Ubah\s+/i, 'Tambah ');
        }

        return defaultTitle;
    };

    window.applyModalTitle = function (modalId, mode, explicitTitle) {
        var titleEl = document.getElementById(modalId + '-title');
        if (!titleEl) return;
        titleEl.textContent = window.resolveModalTitle(modalId, mode, explicitTitle);
    };

    window.initTheme?.();
    window.initDialogs?.();
    window.initToast?.();
    window.initPasswordToggle?.();
    window.initSidebar?.();
    window.initFetchForms?.();
    window.initFormattedNumbers?.();
    window.initDataTables?.();
    window.initEditRecordButtons?.();
    window.initExportButtons?.();
    window.initAjaxSelects?.();
    window.initOfflineSelect2s?.();

    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const modalId = btn.dataset.openModal;
            document.getElementById(modalId)?.classList.remove('hidden');
            const form = document.getElementById(btn.dataset.formReset);
            if (form) {
                window.clearPartialEditLock?.(form);
                form.reset();
                window.resetAjaxSelects?.(form);
                window.resetBuktiUploadZone?.(form);
                form.action = btn.dataset.storeUrl || form.dataset.defaultAction;
                form.dataset.method = 'POST';
                window.syncProfilePhotoForForm?.(form, '');
                form.dispatchEvent(new CustomEvent('modalReset', { detail: { formId: form.id } }));
            }
            window.applyModalTitle?.(modalId, 'create', btn.dataset.modalTitle);
            const modal = document.getElementById(modalId);
            if (modal) {
                setTimeout(function () {
                    window.initAjaxSelects?.(modal);
                    window.initOfflineSelect2s?.(modal);
                }, 0);
            }
        });
    });

    document.addEventListener('click', function (e) {
        const closeTrigger = e.target.closest('[data-modal-close]');
        if (closeTrigger) {
            document.getElementById(closeTrigger.dataset.modalClose)?.classList.add('hidden');
        }

        const copyBtn = e.target.closest('[data-copy-text]');
        if (copyBtn) {
            e.preventDefault();
            window.copyTextToClipboard?.(copyBtn.dataset.copyText, copyBtn.dataset.copySuccess);
        }
    });

    window.copyTextToClipboard = async function (text, successMessage) {
        var value = String(text || '').trim();
        if (!value) {
            window.showToast?.('Tidak ada teks untuk disalin.', 'warning');
            return false;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = value;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            window.showToast?.(successMessage || 'Berhasil disalin.', 'success');
            return true;
        } catch (error) {
            window.showToast?.('Gagal menyalin ke clipboard.', 'error');
            return false;
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;

        var alertDialog = document.getElementById('app-alert-dialog');
        var confirmDialog = document.getElementById('app-confirm-dialog');
        if (alertDialog && !alertDialog.classList.contains('hidden')) return;
        if (confirmDialog && !confirmDialog.classList.contains('hidden')) return;

        var openModals = Array.from(document.querySelectorAll('[id$="-modal"]')).filter(function (el) {
            return !el.classList.contains('hidden');
        });

        if (!openModals.length) return;

        openModals[openModals.length - 1].classList.add('hidden');
    });
});
