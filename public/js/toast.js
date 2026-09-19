/**
 * Toast notifications.
 *
 * Plain: showToast('Tersimpan.', 'success')
 * ActionMessage HTML (auto-lists detail after ": "):
 *   showToast('Siswa berhasil <span class="toast-message__verb">diperbarui</span>: Ahmad · NIS 1 · X IPA 1.', 'success')
 * Options object:
 *   showToast({ message, type, duration, detail: ['A', 'B'] | [{label, value}] })
 *
 * Auto-dismiss pauses while the pointer/touch is on the toast body.
 */
(function () {
    var MAX_TOASTS = 5;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var TOAST_ICONS = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast__icon-svg" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l2 2l4 -4"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast__icon-svg" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast__icon-svg" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0"/><path d="M12 16h.01"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="toast__icon-svg" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
    };
    TOAST_ICONS.danger = TOAST_ICONS.error;

    function normalizeToastType(type) {
        var key = String(type || 'success').toLowerCase();
        if (key === 'danger') return 'error';
        if (TOAST_ICONS[key]) return key;
        return 'info';
    }

    function normalizeOptions(messageOrOptions, type, duration) {
        if (messageOrOptions && typeof messageOrOptions === 'object' && !Array.isArray(messageOrOptions)) {
            return {
                message: messageOrOptions.message ?? '',
                type: normalizeToastType(messageOrOptions.type || 'success'),
                duration: messageOrOptions.duration === undefined ? 5000 : messageOrOptions.duration,
                detail: messageOrOptions.detail ?? null,
            };
        }

        return {
            message: messageOrOptions ?? '',
            type: normalizeToastType(type || 'success'),
            duration: duration === undefined ? 5000 : duration,
            detail: null,
        };
    }

    function renderDetail(detail) {
        if (detail == null || detail === '') {
            return '';
        }

        if (typeof detail === 'string') {
            return '<div class="toast-message__detail">' + escapeHtml(detail) + '</div>';
        }

        if (!Array.isArray(detail) || !detail.length) {
            return '';
        }

        var hasRows = detail.some(function (item) {
            return item && typeof item === 'object' && ('label' in item || 'value' in item);
        });

        if (hasRows) {
            return '<dl class="toast-message__rows">' + detail.map(function (item) {
                var label = escapeHtml(item.label ?? '');
                var value = escapeHtml(item.value ?? '');
                return '<div class="toast-message__row">'
                    + (label ? '<dt>' + label + '</dt>' : '')
                    + '<dd>' + value + '</dd>'
                    + '</div>';
            }).join('') + '</dl>';
        }

        return '<ul class="toast-message__facts">' + detail.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul>';
    }

    /**
     * Split ActionMessage "Action: subject." into title + fact list.
     * Action may contain toast-message__verb HTML; subject is plain/escaped text.
     */
    function formatActionMessage(message) {
        if (typeof message !== 'string' || message === '') {
            return '';
        }

        if (message.includes('toast-message__action') || message.includes('toast-message__facts') || message.includes('toast-message__rows')) {
            return message;
        }

        var match = message.match(/^(.*): (.+)\.$/s);
        if (!match) {
            if (message.includes('toast-message__verb')) {
                return '<div class="toast-message__action">' + message + '</div>';
            }
            return escapeHtml(message);
        }

        var action = match[1].trim();
        var subject = match[2].trim().replace(/\.$/, '');
        var facts = subject.split(/\s·\s/).map(function (part) {
            return part.trim();
        }).filter(Boolean);

        var html = '<div class="toast-message__action">' + action + '</div>';

        if (facts.length > 1) {
            html += '<ul class="toast-message__facts">' + facts.map(function (fact) {
                return '<li>' + fact + '</li>';
            }).join('') + '</ul>';
        } else if (subject) {
            html += '<div class="toast-message__detail">' + subject + '</div>';
        }

        return html;
    }

    function setToastMessage(element, message, detail) {
        if (detail != null) {
            var actionHtml = typeof message === 'string' && message.includes('toast-message__verb')
                ? '<div class="toast-message__action">' + message + '</div>'
                : '<div class="toast-message__action">' + escapeHtml(message) + '</div>';
            element.innerHTML = actionHtml + renderDetail(detail);
            return;
        }

        if (typeof message === 'string' && (message.includes('toast-message__verb') || message.includes(':'))) {
            element.innerHTML = formatActionMessage(message);
            return;
        }

        element.textContent = message == null ? '' : String(message);
    }

    function bindHoldToPersist(toast, dismiss, duration) {
        if (!(duration > 0)) {
            return;
        }

        var timer = null;
        var remaining = duration;
        var startedAt = 0;
        var held = false;

        function clearTimer() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
        }

        function schedule() {
            clearTimer();
            if (remaining <= 0) {
                dismiss();
                return;
            }
            startedAt = Date.now();
            timer = setTimeout(dismiss, remaining);
        }

        function hold() {
            if (held) return;
            held = true;
            remaining -= Date.now() - startedAt;
            if (remaining < 0) remaining = 0;
            clearTimer();
            toast.classList.add('toast--held');
        }

        function release() {
            if (!held) return;
            held = false;
            toast.classList.remove('toast--held');
            schedule();
        }

        toast.addEventListener('pointerenter', hold);
        toast.addEventListener('pointerleave', release);
        toast.addEventListener('touchstart', hold, { passive: true });
        toast.addEventListener('touchend', release);
        toast.addEventListener('touchcancel', release);
        toast.addEventListener('focusin', hold);
        toast.addEventListener('focusout', function (event) {
            if (!toast.contains(event.relatedTarget)) {
                release();
            }
        });

        schedule();
    }

    window.showToast = function (messageOrOptions, type, duration) {
        var options = normalizeOptions(messageOrOptions, type, duration);
        var container = document.getElementById('toast-container');
        if (!container) return;

        var typeClass = {
            success: 'toast-success',
            error: 'toast-error',
            warning: 'toast-warning',
            info: 'toast-info',
        };

        while (container.children.length >= MAX_TOASTS) {
            container.lastElementChild?.remove();
        }

        var toast = document.createElement('div');
        toast.className = 'toast toast--enter ' + (typeClass[options.type] || typeClass.info);
        toast.setAttribute('role', 'status');
        toast.innerHTML = '<span class="toast__icon" aria-hidden="true">'
            + (TOAST_ICONS[options.type] || TOAST_ICONS.info)
            + '</span>'
            + '<div class="toast__message"></div>'
            + '<button type="button" class="toast__close" aria-label="Tutup">&times;</button>';
        setToastMessage(toast.querySelector('.toast__message'), options.message, options.detail);

        var dismissed = false;
        var dismiss = function () {
            if (dismissed) return;
            dismissed = true;
            toast.classList.remove('toast--held');
            toast.classList.add('toast--exit');
            setTimeout(function () { toast.remove(); }, 200);
        };

        toast.querySelector('.toast__close').addEventListener('click', dismiss);
        container.prepend(toast);

        requestAnimationFrame(function () {
            toast.classList.remove('toast--enter');
        });

        bindHoldToPersist(toast, dismiss, options.duration);
    };

    window.initToast = function () {};
})();
