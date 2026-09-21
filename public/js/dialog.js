(function () {
    var WARNING_INPUT_CLASS = 'form-input--warning';
    var WARNING_TEXT_CLASS = 'field-warning mt-1 text-xs text-amber-700 dark:text-amber-300';

    var alertDialog = null;
    var confirmDialog = null;
    var alertResolve = null;
    var confirmResolve = null;

    var variantConfig = {
        danger: {
            headerClass: 'app-dialog__header--danger',
            icon: 'ti-alert-circle',
        },
        warning: {
            headerClass: 'app-dialog__header--warning',
            icon: 'ti-alert-triangle',
        },
        info: {
            headerClass: 'app-dialog__header--info',
            icon: 'ti-info-circle',
        },
        success: {
            headerClass: 'app-dialog__header--success',
            icon: 'ti-circle-check',
        },
    };

    function getAlertElements() {
        if (alertDialog) {
            return alertDialog;
        }

        alertDialog = {
            root: document.getElementById('app-alert-dialog'),
            header: document.getElementById('app-alert-header'),
            icon: document.getElementById('app-alert-icon'),
            title: document.getElementById('app-alert-title'),
            message: document.getElementById('app-alert-message'),
            list: document.getElementById('app-alert-list'),
            detail: document.getElementById('app-alert-detail'),
            ok: document.getElementById('app-alert-ok'),
        };

        return alertDialog;
    }

    function getConfirmElements() {
        if (confirmDialog) {
            return confirmDialog;
        }

        confirmDialog = {
            root: document.getElementById('app-confirm-dialog'),
            header: document.getElementById('app-confirm-header'),
            title: document.getElementById('app-confirm-title'),
            message: document.getElementById('app-confirm-message'),
            detail: document.getElementById('app-confirm-detail'),
            footnote: document.getElementById('app-confirm-footnote'),
            footnoteText: document.getElementById('app-confirm-footnote-text'),
            ok: document.getElementById('app-confirm-ok'),
            cancelButtons: document.querySelectorAll('[data-confirm-cancel]'),
        };

        return confirmDialog;
    }

    function setVariant(headerEl, iconEl, variant) {
        var config = variantConfig[variant] || variantConfig.warning;
        headerEl.className = 'app-dialog__header flex items-center gap-2 rounded-t-xl px-5 py-4 ' + config.headerClass;
        iconEl.innerHTML = '<i class="ti ' + config.icon + '"></i>';
    }

    function decodeHtmlEntities(text) {
        if (!text || (text.indexOf('&') === -1 && text.indexOf('\\u') === -1)) {
            return text;
        }

        var textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
    }

    function parseDetailJson(text) {
        var trimmed = String(text || '').trim();
        if (!trimmed) {
            return null;
        }

        // Unwrap accidental Js::from() / JSON.parse('…') attribute values
        var jsFrom = trimmed.match(/^JSON\.parse\(\s*(['"])([\s\S]*)\1\s*\)$/);
        if (jsFrom) {
            trimmed = jsFrom[2]
                .replace(/\\u([0-9a-fA-F]{4})/g, function (_, hex) {
                    return String.fromCharCode(parseInt(hex, 16));
                })
                .replace(/\\'/g, "'")
                .replace(/\\"/g, '"')
                .replace(/\\\\/g, '\\');
        }

        trimmed = decodeHtmlEntities(trimmed).trim();

        if (trimmed.charAt(0) !== '[') {
            return null;
        }

        try {
            var parsed = JSON.parse(trimmed);
            return Array.isArray(parsed) ? parsed : null;
        } catch (e) {
            return null;
        }
    }

    function normalizeDetail(detail) {
        if (!detail) {
            return null;
        }

        if (typeof detail === 'string') {
            var parsed = parseDetailJson(detail);
            if (parsed) {
                return parsed;
            }

            return detail.trim();
        }

        if (Array.isArray(detail)) {
            return detail;
        }

        return null;
    }

    function escapeDetailHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderDetail(el, detail) {
        var normalized = normalizeDetail(detail);

        if (!normalized) {
            el.classList.add('hidden');
            el.innerHTML = '';
            return;
        }

        el.classList.remove('hidden');

        if (Array.isArray(normalized)) {
            el.innerHTML = normalized.map(function (row) {
                var label = escapeDetailHtml(row && row.label != null ? row.label : '');
                var value = escapeDetailHtml(row && row.value != null ? row.value : '');
                return '<div class="app-dialog__detail-row"><span class="text-slate-500">' + label + '</span><strong>' + value + '</strong></div>';
            }).join('');
            return;
        }

        el.innerHTML = escapeDetailHtml(normalized);
    }

    function closeAlert() {
        var els = getAlertElements();
        if (!els.root) return;

        els.root.classList.add('hidden');
        if (alertResolve) {
            alertResolve();
            alertResolve = null;
        }
    }

    function closeConfirm(result) {
        var els = getConfirmElements();
        if (!els.root) return;

        els.root.classList.add('hidden');
        if (confirmResolve) {
            confirmResolve(!!result);
            confirmResolve = null;
        }
    }

    window.normalizeConfirmDetail = normalizeDetail;

    window.showAlert = function (options) {
        var opts = typeof options === 'string' ? { message: options } : (options || {});
        var els = getAlertElements();
        if (!els.root) {
            window.alert(opts.message || opts.title || 'Perhatian');
            return Promise.resolve();
        }

        setVariant(els.header, els.icon, opts.variant || 'warning');
        els.title.textContent = opts.title || 'Perhatian';
        els.message.textContent = opts.message || '';
        els.message.classList.toggle('hidden', !opts.message);

        if (opts.items && opts.items.length) {
            els.list.classList.remove('hidden');
            els.list.innerHTML = opts.items.map(function (item) {
                return '<li>' + item + '</li>';
            }).join('');
        } else {
            els.list.classList.add('hidden');
            els.list.innerHTML = '';
        }

        renderDetail(els.detail, opts.detail);
        els.ok.textContent = opts.confirmText || 'Mengerti';
        els.root.classList.remove('hidden');

        return new Promise(function (resolve) {
            alertResolve = resolve;
        });
    };

    window.showConfirm = function (options) {
        var opts = typeof options === 'string' ? { message: options } : (options || {});
        var els = getConfirmElements();
        if (!els.root) {
            return Promise.resolve(window.confirm(opts.message || 'Lanjutkan?'));
        }

        els.title.textContent = opts.title || 'Konfirmasi Hapus';
        els.message.textContent = opts.message || 'Yakin ingin melanjutkan?';
        renderDetail(els.detail, opts.detail);

        var tone = opts.tone || 'danger';

        if (els.footnote && els.footnoteText) {
            var footnote = opts.footnote;
            if (footnote === undefined) {
                footnote = tone === 'danger'
                    ? 'Tindakan ini tidak dapat dibatalkan.'
                    : false;
            }

            if (footnote === false) {
                els.footnote.classList.add('hidden');
            } else {
                els.footnote.classList.remove('hidden');
                els.footnoteText.textContent = footnote;
            }
        }

        var toneConfig = {
            primary: {
                headerClass: 'app-dialog__header--info',
                headerIcon: 'ti-help-circle',
                buttonClass: 'btn-primary',
                buttonIcon: 'ti-check',
                defaultText: 'Ya, Lanjutkan',
            },
            info: {
                headerClass: 'app-dialog__header--info',
                headerIcon: 'ti-info-circle',
                buttonClass: 'btn-primary',
                buttonIcon: 'ti-check',
                defaultText: 'Ya, Lanjutkan',
            },
            warning: {
                headerClass: 'app-dialog__header--warning',
                headerIcon: 'ti-alert-triangle',
                buttonClass: 'btn-warning',
                buttonIcon: 'ti-check',
                defaultText: 'Ya, Lanjutkan',
            },
            danger: {
                headerClass: 'app-dialog__header--danger',
                headerIcon: 'ti-alert-triangle',
                buttonClass: 'btn-danger',
                buttonIcon: 'ti-trash',
                defaultText: 'Ya, Hapus',
            },
        };
        var config = toneConfig[tone] || toneConfig.danger;
        var headerIcon = opts.headerIcon || config.headerIcon;
        var buttonIcon = opts.confirmIcon || config.buttonIcon;
        els.header.className = 'app-dialog__header flex items-center gap-2 rounded-t-xl px-5 py-4 ' + config.headerClass;
        els.header.querySelector('.app-dialog__icon').innerHTML = '<i class="ti ' + headerIcon + '"></i>';

        els.ok.className = config.buttonClass;
        els.ok.innerHTML = opts.confirmHtml || ('<i class="ti ' + buttonIcon + '"></i> ' + (opts.confirmText || config.defaultText));
        els.root.classList.remove('hidden');

        return new Promise(function (resolve) {
            confirmResolve = resolve;
        });
    };

    function fieldLabel(form, input) {
        if (input.id) {
            var label = form.querySelector('label[for="' + input.id + '"]');
            if (label) return label.textContent.trim();
        }

        var wrapper = input.closest('div');
        var groupLabel = wrapper && wrapper.querySelector('.form-label');
        return groupLabel ? groupLabel.textContent.trim() : (input.name || 'Field');
    }

    function messageForInvalidInput(form, input) {
        var label = fieldLabel(form, input);

        if (input.validity.valueMissing) {
            return label + ' wajib diisi.';
        }
        if (input.validity.typeMismatch && input.type === 'email') {
            return label + ' harus berupa email yang valid.';
        }
        if (input.validity.rangeUnderflow) {
            return label + ' terlalu kecil.';
        }
        if (input.validity.rangeOverflow) {
            return label + ' terlalu besar.';
        }
        if (input.validity.tooShort) {
            return label + ' terlalu pendek.';
        }
        if (input.validity.patternMismatch) {
            return label + ' tidak sesuai format.';
        }

        return label + ' tidak valid.';
    }

    function getSelect2Selection(input) {
        if (!input || !input.classList?.contains('select2-hidden-accessible')) {
            return null;
        }

        var container = input.nextElementSibling;
        if (!container || !container.classList?.contains('select2')) {
            return null;
        }

        return container.querySelector('.select2-selection');
    }

    function removeWarningState(input) {
        if (!input) return;

        input.classList.remove(WARNING_INPUT_CLASS);
        input.removeAttribute('aria-invalid');

        var select2Selection = getSelect2Selection(input);
        if (select2Selection) {
            select2Selection.classList.remove('select2-selection--warning');
        }
    }

    function addWarningState(input) {
        if (!input) return;

        input.classList.add(WARNING_INPUT_CLASS);
        input.setAttribute('aria-invalid', 'true');

        var select2Selection = getSelect2Selection(input);
        if (select2Selection) {
            select2Selection.classList.add('select2-selection--warning');
        }
    }

    function appendWarningMessage(form, input, message) {
        if (!input || !message) return;

        var warning = document.createElement('p');
        warning.className = WARNING_TEXT_CLASS;
        warning.setAttribute('data-field-warning', '1');
        warning.textContent = message;

        var select2Selection = getSelect2Selection(input);
        if (select2Selection) {
            var container = input.nextElementSibling;
            if (container && container.parentNode) {
                container.parentNode.insertBefore(warning, container.nextSibling);
                return;
            }
        }

        if (input.parentNode) {
            input.parentNode.appendChild(warning);
        }
    }

    function clearValidationWarnings(form) {
        if (!form) return;

        form.querySelectorAll('[data-field-warning="1"]').forEach(function (el) { el.remove(); });
        Array.from(form.elements || []).forEach(removeWarningState);
    }

    function getInvalidFields(form) {
        var invalidFields = [];
        var seenMessages = new Set();

        Array.from(form.elements).forEach(function (input) {
            if (!input || !input.name || isValidationSkippedInput(form, input)) return;
            if (!input.willValidate || input.checkValidity()) return;

            var message = messageForInvalidInput(form, input);
            invalidFields.push({ input: input, message: message });
            seenMessages.add(message);
        });

        return {
            fields: invalidFields,
            messages: Array.from(seenMessages),
        };
    }

    function bindWarningCleanup(form) {
        if (!form || form.dataset.validationWarningBound === '1') {
            return;
        }

        form.dataset.validationWarningBound = '1';

        var clearForTarget = function (target) {
            if (!(target instanceof HTMLElement)) return;

            var input = target.closest('input, select, textarea');
            if (!input || !form.contains(input)) return;

            removeWarningState(input);

            var wrapper = input.parentElement || form;
            if (wrapper) {
                wrapper.querySelectorAll('[data-field-warning="1"]').forEach(function (el) { el.remove(); });
            }
        };

        form.addEventListener('input', function (event) {
            clearForTarget(event.target);
        });

        form.addEventListener('change', function (event) {
            clearForTarget(event.target);
        });
    }

    function isValidationSkippedInput(form, input) {
        if (!input || input.disabled) {
            return true;
        }

        if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') {
            return true;
        }

        var node = input;
        while (node && node !== form) {
            if (node.classList?.contains('hidden') || node.hidden) {
                return true;
            }
            node = node.parentElement;
        }

        return false;
    }

    window.collectFormValidationErrors = function (form) {
        return getInvalidFields(form).messages;
    };

    window.validateFormWithDialog = async function (form) {
        bindWarningCleanup(form);
        clearValidationWarnings(form);

        var validationState = getInvalidFields(form);
        if (!validationState.messages.length) {
            return true;
        }

        validationState.fields.forEach(function (field) {
            addWarningState(field.input);
            appendWarningMessage(form, field.input, field.message);
        });

        await window.showAlert({
            title: 'Periksa Data',
            message: 'Beberapa field belum diisi dengan benar:',
            items: validationState.messages,
            variant: 'warning',
        });

        var firstInvalid = validationState.fields[0]?.input;
        firstInvalid?.focus();

        return false;
    };

    window.initDialogs = function () {
        var alertEls = getAlertElements();
        var confirmEls = getConfirmElements();

        alertEls.ok?.addEventListener('click', closeAlert);
        alertEls.root?.querySelector('[data-alert-close]')?.addEventListener('click', closeAlert);

        confirmEls.ok?.addEventListener('click', function () { closeConfirm(true); });
        confirmEls.cancelButtons?.forEach(function (btn) {
            btn.addEventListener('click', function () { closeConfirm(false); });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (!alertEls.root?.classList.contains('hidden')) closeAlert();
            if (!confirmEls.root?.classList.contains('hidden')) closeConfirm(false);
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.setAttribute('novalidate', '');
            bindWarningCleanup(form);
        });

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.skipDialogValidation === 'true') return;

            bindWarningCleanup(form);
            clearValidationWarnings(form);

            var validationState = getInvalidFields(form);
            if (!validationState.messages.length) return;

            validationState.fields.forEach(function (field) {
                addWarningState(field.input);
                appendWarningMessage(form, field.input, field.message);
            });

            e.preventDefault();
            e.stopPropagation();

            window.showAlert({
                title: 'Periksa Data',
                message: 'Beberapa field belum diisi dengan benar:',
                items: validationState.messages,
                variant: 'warning',
            });

            validationState.fields[0]?.input?.focus();
        }, true);
    };
})();
