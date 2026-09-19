/**
 * Currency / number formatting for text inputs.
 * - .formattedNumber — Indonesian locale grouping (1.000.000)
 * - .onlyNumber — digits only, no grouping
 * Use type="text" with inputmode="numeric". Set min/max as integers.
 */
(function () {
    'use strict';

    var ALLOWED = ['formattedNumber', 'onlyNumber'];

    function hasAllowedClass(el) {
        return ALLOWED.some(function (cls) { return el.classList.contains(cls); });
    }

    function parseRaw(value) {
        var digits = String(value || '').replace(/\./g, '').replace(/[^0-9]/g, '');
        return digits === '' ? NaN : parseInt(digits, 10);
    }

    function getBounds(el) {
        var min = el.hasAttribute('min')
            ? parseInt(el.getAttribute('min'), 10)
            : parseInt(el.getAttribute('data-min') || '', 10);
        var max = el.hasAttribute('max')
            ? parseInt(el.getAttribute('max'), 10)
            : parseInt(el.getAttribute('data-max') || '', 10);

        return {
            min: isNaN(min) ? null : min,
            max: isNaN(max) ? null : max,
        };
    }

    function clamp(num, el) {
        if (isNaN(num)) return num;

        var bounds = getBounds(el);
        if (bounds.max !== null && num > bounds.max) num = bounds.max;
        if (bounds.min !== null && num < bounds.min) num = bounds.min;

        return num;
    }

    function formatsOnBlur(el) {
        return el.dataset.formatOn === 'blur';
    }

    function formatsLive(el) {
        return el.dataset.formatOn === 'live';
    }

    function minDigitsLength(el) {
        var bounds = getBounds(el);
        return bounds.min === null ? 0 : String(bounds.min).length;
    }

    function resolveBoundedAmount(el, rawValue) {
        var digits = String(rawValue || '').replace(/[^0-9]/g, '');
        if (digits === '') {
            return 0;
        }

        var num = parseInt(digits, 10);
        if (isNaN(num)) {
            return 0;
        }

        var bounds = getBounds(el);
        if (bounds.max !== null && num > bounds.max) {
            return bounds.max;
        }

        if (bounds.min !== null && num < bounds.min) {
            if (!formatsLive(el) || digits.length >= minDigitsLength(el)) {
                return bounds.min;
            }
        }

        return num;
    }

    function applyLiveAmountInput(el) {
        var digits = String(el.value || '').replace(/[^0-9]/g, '');
        if (digits === '') {
            el.value = '';
            return;
        }

        var num = resolveBoundedAmount(el, digits);
        if (!num) {
            el.value = '';
            return;
        }

        formatValue(el, num);
        el.dispatchEvent(new CustomEvent('formatted-amount-updated', { bubbles: true }));
    }

    window.readBoundedFormattedAmount = function (input) {
        if (!input) {
            return 0;
        }

        return resolveBoundedAmount(input, input.value);
    };

    function formatValue(el, num) {
        if (isNaN(num)) {
            el.value = '';
            return;
        }
        el.value = el.classList.contains('onlyNumber')
            ? String(num)
            : num.toLocaleString('id-ID');
    }

    window.parseFormattedNumber = function (value) {
        var n = parseRaw(value);
        return isNaN(n) ? 0 : n;
    };

    window.formatNumberId = function (value) {
        var n = typeof value === 'number' ? value : parseRaw(value);
        return isNaN(n) ? '' : n.toLocaleString('id-ID');
    };

    window.stripFormattedFields = function (formData, root) {
        (root || document).querySelectorAll('.formattedNumber, .onlyNumber').forEach(function (input) {
            if (!input.name) {
                return;
            }

            var raw = parseRaw(input.value);
            formData.set(input.name, isNaN(raw) ? '' : String(raw));
        });

        return formData;
    };

    document.addEventListener('keypress', function (e) {
        if (!hasAllowedClass(e.target)) return;
        var charCode = e.which || e.keyCode;
        if (charCode < 48 || charCode > 57) e.preventDefault();
    });

    document.addEventListener('paste', function (e) {
        var el = e.target;
        if (!hasAllowedClass(el) || el.readOnly || el.disabled) return;
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        el.value = pasted;
        if (formatsLive(el)) {
            applyLiveAmountInput(el);
            return;
        }
        var num = clamp(parseRaw(pasted), el);
        formatValue(el, num);
    });

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!hasAllowedClass(el)) return;

        if (formatsLive(el)) {
            applyLiveAmountInput(el);
            return;
        }

        if (formatsOnBlur(el)) {
            var digits = String(el.value || '').replace(/[^0-9]/g, '');
            if (digits !== el.value) {
                el.value = digits;
            }
            return;
        }

        var num = clamp(parseRaw(el.value), el);
        formatValue(el, num);
    });

    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!hasAllowedClass(el) || el.readOnly || el.disabled) return;

        if (!formatsOnBlur(el) && !formatsLive(el)) {
            return;
        }

        var num = clamp(parseRaw(el.value), el);
        formatValue(el, num);
    }, true);

    window.initFormattedNumbers = function (root) {
        (root || document).querySelectorAll('.formattedNumber').forEach(function (el) {
            if (!el.value) return;
            var num = parseRaw(el.value);
            if (!isNaN(num)) formatValue(el, num);
        });
    };

    window.initFormattedAmountFields = window.initFormattedNumbers;
})();
