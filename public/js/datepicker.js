/**
 * Branded Flatpickr wrappers — single date + date range.
 * Requires Flatpickr (+ optional Indonesian locale) to be loaded first.
 *
 * Markup:
 *   <input class="form-input datepicker" data-datepicker>
 *   <input class="form-input daterange" data-daterange
 *          data-date-from="#from" data-date-to="#to">
 *
 * Shortcuts (right rail beside calendar; stacks below on narrow screens):
 *   datepicker — Hari ini, Hapus
 *   daterange  — Hari ini, Minggu ini, Bulan ini
 */
(function () {
    var instances = new WeakMap();

    function locale() {
        if (typeof flatpickr === 'undefined') return 'default';
        return (flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default';
    }

    function startOfDay(date) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    }

    function startOfWeek(date) {
        var d = startOfDay(date);
        var day = d.getDay(); // 0 Sun … 6 Sat
        var diff = day === 0 ? -6 : 1 - day; // Monday-first (ID / business week)
        d.setDate(d.getDate() + diff);
        return d;
    }

    function endOfWeek(date) {
        var d = startOfWeek(date);
        d.setDate(d.getDate() + 6);
        return d;
    }

    function startOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function endOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0);
    }

    function baseConfig(el) {
        var classes = ['form-input'];
        if (el.classList.contains('datepicker')) classes.push('datepicker');
        if (el.classList.contains('daterange')) classes.push('daterange');

        return {
            locale: locale(),
            dateFormat: el.dataset.dateFormat || 'Y-m-d',
            altInput: el.dataset.altInput !== '0',
            altFormat: el.dataset.altFormat || 'd/m/Y',
            altInputClass: classes.join(' '),
            allowInput: el.dataset.allowInput === '1',
            disableMobile: el.dataset.disableMobile === '1',
            animate: false,
            position: 'auto',
            onOpen: function (_dates, _str, instance) {
                boldMonthNavIcons(instance);
                pinCalendarToInput(instance);
            },
        };
    }

    function syncHiddenPair(fp, fromSel, toSel) {
        var fromEl = fromSel ? document.querySelector(fromSel) : null;
        var toEl = toSel ? document.querySelector(toSel) : null;
        var dates = fp.selectedDates || [];

        if (fromEl) {
            fromEl.value = dates[0] ? fp.formatDate(dates[0], 'Y-m-d') : '';
        }
        if (toEl) {
            toEl.value = dates[1] ? fp.formatDate(dates[1], 'Y-m-d') : '';
        }
    }

    function makeShortcut(label, opts) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'flatpickr-shortcut' + (opts && opts.muted ? ' flatpickr-shortcut--muted' : '');
        btn.textContent = label;
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (opts && typeof opts.onClick === 'function') {
                opts.onClick();
            }
        });
        return btn;
    }

    function boldMonthNavIcons(instance) {
        var calendar = instance.calendarContainer;
        if (!calendar || calendar.dataset.boldNav === '1') return;

        var prev = calendar.querySelector('.flatpickr-prev-month');
        var next = calendar.querySelector('.flatpickr-next-month');
        var left = '<svg class="flatpickr-nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>';
        var right = '<svg class="flatpickr-nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>';

        if (prev) prev.innerHTML = left;
        if (next) next.innerHTML = right;
        calendar.dataset.boldNav = '1';
    }

    /**
     * Flatpickr uses pageYOffset + absolute positioning. Our app scrolls
     * body.app-body (overflow-y: auto), so pageYOffset stays 0 and the popup
     * drifts. Pin with position:fixed from the input's viewport box instead.
     */
    function pinCalendarToInput(instance) {
        var calendar = instance && instance.calendarContainer;
        var input = instance && (instance._input || instance._positionElement);
        if (!calendar || !input || calendar.classList.contains('inline')) return;

        var run = function () {
            if (!instance.isOpen && !calendar.classList.contains('open')) return;

            var rect = input.getBoundingClientRect();
            var calH = calendar.offsetHeight || 0;
            var calW = calendar.offsetWidth || 0;
            var gap = 4;
            var spaceBelow = window.innerHeight - rect.bottom;
            var placeAbove = spaceBelow < calH + gap && rect.top > spaceBelow;

            var top = placeAbove ? rect.top - calH - gap : rect.bottom + gap;
            var left = rect.left;

            if (left + calW > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - calW - 8);
            }
            if (left < 8) left = 8;
            if (top < 8) top = 8;
            if (top + calH > window.innerHeight - 8) {
                top = Math.max(8, window.innerHeight - calH - 8);
            }

            calendar.style.position = 'fixed';
            calendar.style.top = Math.round(top) + 'px';
            calendar.style.left = Math.round(left) + 'px';
            calendar.style.right = 'auto';
            calendar.style.bottom = 'auto';
            calendar.style.margin = '0';
            calendar.classList.toggle('arrowTop', !placeAbove);
            calendar.classList.toggle('arrowBottom', placeAbove);
        };

        // After shortcuts/flex width settles.
        requestAnimationFrame(function () {
            run();
            requestAnimationFrame(run);
        });
    }

    function installFixedPositioning(instance) {
        if (!instance || instance.__fixedPos) return;
        instance.__fixedPos = true;

        instance._positionCalendar = function () {
            pinCalendarToInput(instance);
        };

        var onReposition = function () {
            if (instance.isOpen) pinCalendarToInput(instance);
        };

        // capture:true catches scroll on body.app-body and nested overflow areas
        window.addEventListener('scroll', onReposition, true);
        window.addEventListener('resize', onReposition);
    }

    function ensureShortcuts(instance, buttons) {
        var calendar = instance.calendarContainer;
        if (!calendar || calendar.querySelector('.flatpickr-shortcuts')) return;

        var main = calendar.querySelector('.flatpickr-main');
        if (!main) {
            main = document.createElement('div');
            main.className = 'flatpickr-main';
            while (calendar.firstChild) {
                main.appendChild(calendar.firstChild);
            }
            calendar.appendChild(main);
        }

        var bar = document.createElement('div');
        bar.className = 'flatpickr-shortcuts';
        buttons.forEach(function (btn) {
            bar.appendChild(btn);
        });
        calendar.appendChild(bar);
        calendar.classList.add('flatpickr-calendar--with-shortcuts');
        pinCalendarToInput(instance);
    }

    function decorateCalendar(instance, shortcutBuilder) {
        boldMonthNavIcons(instance);
        installFixedPositioning(instance);
        if (typeof shortcutBuilder === 'function') {
            shortcutBuilder(instance);
        }
        pinCalendarToInput(instance);
    }

    function datepickerShortcuts(instance) {
        ensureShortcuts(instance, [
            makeShortcut('Hari ini', {
                onClick: function () {
                    instance.setDate(startOfDay(new Date()), true);
                    instance.close();
                },
            }),
            makeShortcut('Hapus', {
                muted: true,
                onClick: function () {
                    instance.clear();
                    instance.close();
                },
            }),
        ]);
    }

    function daterangeShortcuts(instance, fromSel, toSel) {
        ensureShortcuts(instance, [
            makeShortcut('Hari ini', {
                onClick: function () {
                    var today = startOfDay(new Date());
                    instance.setDate([today, today], true);
                    syncHiddenPair(instance, fromSel, toSel);
                    instance.close();
                },
            }),
            makeShortcut('Minggu ini', {
                onClick: function () {
                    var now = new Date();
                    instance.setDate([startOfWeek(now), endOfWeek(now)], true);
                    syncHiddenPair(instance, fromSel, toSel);
                    instance.close();
                },
            }),
            makeShortcut('Bulan ini', {
                onClick: function () {
                    var now = new Date();
                    instance.setDate([startOfMonth(now), endOfMonth(now)], true);
                    syncHiddenPair(instance, fromSel, toSel);
                    instance.close();
                },
            }),
        ]);
    }

    function mergeHooks(config, name, handler) {
        var existing = config[name];
        config[name] = function () {
            var args = Array.prototype.slice.call(arguments);
            if (typeof existing === 'function') {
                existing.apply(null, args);
            } else if (Array.isArray(existing)) {
                existing.forEach(function (fn) {
                    if (typeof fn === 'function') fn.apply(null, args);
                });
            }
            handler.apply(null, args);
        };
        return config;
    }

    window.initDatepickers = function (root) {
        if (typeof flatpickr === 'undefined') return;

        (root || document).querySelectorAll('[data-datepicker]').forEach(function (el) {
            if (instances.has(el)) return;

            var config = Object.assign(baseConfig(el), {
                enableTime: el.dataset.enableTime === '1',
                noCalendar: el.dataset.noCalendar === '1',
                mode: 'single',
            });

            if (config.enableTime && !el.dataset.altFormat) {
                config.altFormat = 'd/m/Y H:i';
                config.dateFormat = el.dataset.dateFormat || 'Y-m-d H:i';
            }

            mergeHooks(config, 'onReady', function (_dates, _str, instance) {
                decorateCalendar(instance, datepickerShortcuts);
            });

            instances.set(el, flatpickr(el, config));
        });
    };

    window.initDateRanges = function (root) {
        if (typeof flatpickr === 'undefined') return;

        (root || document).querySelectorAll('[data-daterange]').forEach(function (el) {
            if (instances.has(el)) return;

            var fromSel = el.dataset.dateFrom || '';
            var toSel = el.dataset.dateTo || '';

            var config = Object.assign(baseConfig(el), {
                mode: 'range',
                altFormat: el.dataset.altFormat || 'd/m/Y',
                onChange: function (selectedDates, _dateStr, instance) {
                    syncHiddenPair(instance, fromSel, toSel);
                    if (typeof window[el.dataset.onChange] === 'function') {
                        window[el.dataset.onChange](selectedDates, instance);
                    }
                },
            });

            mergeHooks(config, 'onReady', function (_selectedDates, _dateStr, instance) {
                syncHiddenPair(instance, fromSel, toSel);
                decorateCalendar(instance, function (fp) {
                    daterangeShortcuts(fp, fromSel, toSel);
                });
            });

            // Prefill from hidden pair when present.
            var fromEl = fromSel ? document.querySelector(fromSel) : null;
            var toEl = toSel ? document.querySelector(toSel) : null;
            if (fromEl?.value && toEl?.value) {
                config.defaultDate = [fromEl.value, toEl.value];
            } else if (fromEl?.value) {
                config.defaultDate = [fromEl.value];
            }

            instances.set(el, flatpickr(el, config));
        });
    };

    /**
     * Periode picker — month + year selection via Flatpickr monthSelect plugin.
     * Requires flatpickr + monthSelectPlugin to be loaded first.
     *
     * Markup:
     *   <input class="form-input periodpicker" data-periodpicker
     *          data-period-format="Ym"   <!-- YYYYMM (default) | mY = MMYYYY -->
     *          data-period-alt-format="F Y"
     *          data-period-output="#raw" <!-- optional: write raw code to a field -->
     *          data-alt-input="0">       <!-- optional: show raw code directly -->
     *
     * Shortcuts (right rail): Bulan ini, Hapus.
     */
    window.initPeriodPickers = function (root) {
        if (typeof flatpickr === 'undefined' || typeof monthSelectPlugin === 'undefined') return;

        (root || document).querySelectorAll('[data-periodpicker]').forEach(function (el) {
            if (instances.has(el)) return;

            var format = el.dataset.periodFormat || 'Ym'; // YYYYMM (default) | mY = MMYYYY
            var altFormat = el.dataset.periodAltFormat || 'F Y';
            var shorthand = el.dataset.periodShorthand === '1';
            var outputSel = el.dataset.periodOutput || '';

            var config = Object.assign(baseConfig(el), {
                plugins: [monthSelectPlugin({
                    shorthand: shorthand,
                    dateFormat: format,
                    altFormat: altFormat,
                    theme: 'light',
                })],
                mode: 'single',
                minDate: el.dataset.periodMin ? new Date(el.dataset.periodMin) : undefined,
                maxDate: el.dataset.periodMax ? new Date(el.dataset.periodMax) : undefined,
                onChange: function (selectedDates, _dateStr, instance) {
                    var outputEl = outputSel ? document.querySelector(outputSel) : null;
                    if (outputEl) {
                        outputEl.value = selectedDates.length
                            ? instance.formatDate(selectedDates[0], format)
                            : '';
                    }
                },
            });

            mergeHooks(config, 'onReady', function (_dates, _str, instance) {
                decorateCalendar(instance, function (fp) {
                    ensureShortcuts(fp, [
                        makeShortcut('Bulan ini', {
                            onClick: function () {
                                var now = new Date();
                                fp.setDate(new Date(now.getFullYear(), now.getMonth(), 1), true);
                                fp.close();
                            },
                        }),
                        makeShortcut('Hapus', {
                            muted: true,
                            onClick: function () {
                                fp.clear();
                                fp.close();
                            },
                        }),
                    ]);
                });
            });

            instances.set(el, flatpickr(el, config));
        });
    };

    window.initUiDateControls = function (root) {
        window.initDatepickers?.(root);
        window.initDateRanges?.(root);
        window.initPeriodPickers?.(root);
    };

    document.addEventListener('theme-changed', function () {
        // Flatpickr calendars inherit CSS variables; no re-init required.
    });
})();
