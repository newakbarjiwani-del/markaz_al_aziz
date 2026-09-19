(function () {
    function closeDropdownButtons(exceptPanel) {
        document.querySelectorAll('[data-dropdown-button-panel].is-open').forEach(function (panel) {
            if (exceptPanel && panel === exceptPanel) {
                return;
            }

            panel.classList.remove('is-open');
            window.resetFloatingMenuPanelStyles?.(panel);
        });

        document.querySelectorAll('[data-dropdown-button-toggle][aria-expanded="true"]').forEach(function (button) {
            if (exceptPanel && button.closest('[data-dropdown-button]')?.querySelector('[data-dropdown-button-panel]') === exceptPanel) {
                return;
            }

            button.setAttribute('aria-expanded', 'false');
        });
    }

    function positionOpenPanel(toggle, panel) {
        window.positionFloatingMenuPanel?.(toggle, panel);

        requestAnimationFrame(function () {
            if (panel.classList.contains('is-open')) {
                window.positionFloatingMenuPanel?.(toggle, panel);
            }
        });
    }

    function openDropdownButton(toggle) {
        var root = toggle.closest('[data-dropdown-button]');
        var panel = root?.querySelector('[data-dropdown-button-panel]');

        if (!panel) {
            return;
        }

        var wasOpen = panel.classList.contains('is-open');
        closeDropdownButtons();

        if (wasOpen) {
            return;
        }

        panel.classList.add('is-open');
        positionOpenPanel(toggle, panel);
        toggle.setAttribute('aria-expanded', 'true');
    }

    window.closeDropdownButtons = closeDropdownButtons;

    window.initDropdownButtons = function () {
        // Listeners registered once on document.
    };

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-dropdown-button-toggle]');
        if (toggle) {
            if (toggle.disabled || toggle.closest('[data-dropdown-button]')?.classList.contains('is-loading')) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            openDropdownButton(toggle);
            return;
        }

        if (event.target.closest('[data-dropdown-button-panel]')) {
            if (!event.target.closest('[data-portal-wa]')) {
                closeDropdownButtons();
            }
            return;
        }

        closeDropdownButtons();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdownButtons();
        }
    });

    window.addEventListener('resize', function () {
        closeDropdownButtons();
    });

    window.addEventListener('scroll', function () {
        closeDropdownButtons();
    }, true);
})();
