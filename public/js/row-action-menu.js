(function () {
    function closeRowActionMenus() {
        document.querySelectorAll('[data-row-menu-panel].is-open').forEach(function (panel) {
            panel.classList.remove('is-open');
            window.resetFloatingMenuPanelStyles?.(panel);
        });

        document.querySelectorAll('[data-row-menu-toggle][aria-expanded="true"]').forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    function positionOpenPanel(toggle, panel) {
        window.positionFloatingMenuPanel?.(toggle, panel, { align: 'end' });

        requestAnimationFrame(function () {
            if (panel.classList.contains('is-open')) {
                window.positionFloatingMenuPanel?.(toggle, panel, { align: 'end' });
            }
        });
    }

    function openRowActionMenu(toggle) {
        var menu = toggle.closest('[data-row-menu]');
        var panel = menu?.querySelector('[data-row-menu-panel]');
        if (!panel) {
            return;
        }

        var wasOpen = panel.classList.contains('is-open');
        closeRowActionMenus();

        if (wasOpen) {
            return;
        }

        panel.classList.add('is-open');
        positionOpenPanel(toggle, panel);
        toggle.setAttribute('aria-expanded', 'true');
    }

    window.closeRowActionMenus = closeRowActionMenus;

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-row-menu-toggle]');
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            openRowActionMenu(toggle);
            return;
        }

        if (event.target.closest('[data-row-menu-panel]')) {
            if (!event.target.closest('[data-portal-wa]')) {
                closeRowActionMenus();
            }
            return;
        }

        closeRowActionMenus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeRowActionMenus();
        }
    });

    window.addEventListener('resize', closeRowActionMenus);
    window.addEventListener('scroll', closeRowActionMenus, true);
})();
