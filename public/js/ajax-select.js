(function () {
    var MIN_LENGTH_DEFAULT = 3;

    function parseExtraParams(el) {
        try {
            return JSON.parse(el.dataset.extraParams || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function resolveUrlFor(el, id) {
        var base = el.dataset.resolveUrl || el.dataset.url || '';
        return base.replace(/\/\d*$/, '') + '/' + id;
    }

    function dropdownParent($el) {
        var modal = $el.closest('[id$="-modal"]');
        if (modal.length) {
            return modal;
        }
        return $(document.body);
    }

    function buildConfig(el) {
        var $el = $(el);
        var minLength = parseInt(el.dataset.minLength || MIN_LENGTH_DEFAULT, 10);
        var extraParams = parseExtraParams(el);
        var isMultiple = el.multiple || el.dataset.allowMultiple === '1';

        return {
            width: '100%',
            placeholder: el.dataset.placeholder || (isMultiple ? 'Pilih satu atau lebih...' : 'Pilih...'),
            allowClear: el.dataset.allowClear !== '0',
            minimumInputLength: minLength,
            multiple: isMultiple,
            closeOnSelect: !isMultiple,
            dropdownParent: dropdownParent($el),
            language: {
                inputTooShort: function () {
                    return 'Ketik minimal ' + minLength + ' karakter';
                },
                searching: function () {
                    return 'Mencari...';
                },
                noResults: function () {
                    return 'Data tidak ditemukan';
                },
            },
            ajax: {
                delay: 300,
                url: el.dataset.url,
                dataType: 'json',
                data: function (params) {
                    return Object.assign({}, extraParams, {
                        term: params.term || '',
                    });
                },
                processResults: function (data) {
                    return {
                        results: data.results || [],
                        pagination: data.pagination || { more: false },
                    };
                },
            },
        };
    }

    function ensureOption(el, value, text) {
        var $el = $(el);
        if (!$el.find('option[value="' + value + '"]').length) {
            $el.append(new Option(text, value, true, true));
        }
    }

    window.setAjaxSelectValue = function (select, value, text) {
        if (!select) return;

        var $el = $(select);
        if (!$el.data('select2')) {
            window.initAjaxSelects?.(select.parentElement || document);
        }

        if (!value) {
            $el.val(null).trigger('change');
            return;
        }

        if (text) {
            ensureOption(select, value, text);
            $el.val(String(value)).trigger('change');
            return;
        }

        fetch(resolveUrlFor(select, value), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (res) {
                if (!res.ok) throw new Error('resolve failed');
                return res.json();
            })
            .then(function (payload) {
                ensureOption(select, payload.id, payload.text);
                $el.val(String(payload.id)).trigger('change');
            })
            .catch(function () {
                ensureOption(select, value, 'ID ' + value);
                $el.val(String(value)).trigger('change');
            });
    };

    function hasSelect2(el) {
        return el.classList.contains('select2-hidden-accessible');
    }

    window.resetAjaxSelects = function (container) {
        (container || document).querySelectorAll('[data-ajax-select], [data-s2]').forEach(function (el) {
            if (el.multiple) {
                if (hasSelect2(el)) {
                    $(el).val([]).trigger('change');
                } else {
                    el.value = '';
                }
                return;
            }

            var defaultOption = Array.from(el.options || []).find(function (option) {
                return option.defaultSelected;
            });
            var resetValue = defaultOption ? defaultOption.value : (el.options?.[0]?.value ?? '');

            if (hasSelect2(el)) {
                $(el).val(resetValue).trigger('change');
            } else {
                el.value = resetValue;
            }
        });
    };

    window.initAjaxSelects = function (root) {
        if (!window.jQuery || !jQuery.fn.select2) return;

        (root || document).querySelectorAll('[data-ajax-select]').forEach(function (el) {
            if (hasSelect2(el)) return;

            $(el).select2(buildConfig(el));
        });
    };

    window.destroyAjaxSelects = function (root) {
        if (!window.jQuery || !jQuery.fn.select2) return;

        (root || document).querySelectorAll('[data-ajax-select]').forEach(function (el) {
            if (hasSelect2(el)) {
                $(el).select2('destroy');
            }
        });
    };

    window.initOfflineSelect2s = function (root) {
        if (!window.jQuery || !jQuery.fn.select2) return;

        (root || document).querySelectorAll('[data-s2]').forEach(function (el) {
            if (hasSelect2(el)) return;

            $(el).select2({
                width: '100%',
                placeholder: el.dataset.placeholder || 'Pilih...',
                allowClear: el.dataset.allowClear !== '0',
                closeOnSelect: !el.multiple,
                dropdownParent: dropdownParent($(el)),
                language: {
                    searching: function () { return 'Mencari...'; },
                    noResults: function () { return 'Data tidak ditemukan'; },
                },
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initAjaxSelects?.();
        window.initOfflineSelect2s?.();
    });

    document.addEventListener('theme-changed', function () {
        if (!window.jQuery || !jQuery.fn.select2) return;

        document.querySelectorAll('[data-ajax-select], [data-s2]').forEach(function (el) {
            if (!hasSelect2(el)) return;

            var $select2 = $(el).data('select2');
            if ($select2 && $select2.isOpen()) {
                $(el).select2('close');
            }
        });
    });
})();
