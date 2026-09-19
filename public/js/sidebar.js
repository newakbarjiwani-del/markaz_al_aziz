(function () {
    var STORAGE_KEY = 'sidebar-collapsed';

    function setSubmenuOpen(btn, panel, open) {
        if (!btn || !panel) return;

        panel.classList.toggle('hidden', !open);
        btn.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.querySelector('[data-chevron]')?.classList.toggle('rotate-180', open);
    }

    function closeAllSubmenus(exceptId) {
        document.querySelectorAll('[data-submenu-toggle]').forEach(function (btn) {
            if (btn.dataset.submenuToggle === exceptId) return;
            var panel = document.getElementById(btn.dataset.submenuToggle);
            setSubmenuOpen(btn, panel, false);
        });
    }

    function clearFlyoutOpenState() {
        document.querySelectorAll('[data-submenu-toggle].is-flyout-open').forEach(function (btn) {
            btn.classList.remove('is-flyout-open');
        });
    }

    function isDesktop() {
        return window.matchMedia('(min-width: 1024px)').matches;
    }

    function isCollapsed() {
        return isDesktop() && document.documentElement.classList.contains('sidebar-collapsed');
    }

    function closeMobileSidebar(sidebar, overlay) {
        if (!sidebar || isDesktop()) return;
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    }

    function hideTooltip(tooltip) {
        if (!tooltip) return;
        tooltip.classList.add('hidden');
        tooltip.setAttribute('aria-hidden', 'true');
        tooltip.textContent = '';
        tooltip.style.left = '';
        tooltip.style.top = '';
        tooltip.style.visibility = '';
    }

    function showTooltip(tooltip, anchor) {
        if (!tooltip || !anchor || !isCollapsed()) return;
        if (anchor.classList.contains('is-flyout-open')) return;

        var text = anchor.dataset.sidebarTooltip;
        if (!text) return;

        tooltip.textContent = text;
        tooltip.classList.remove('hidden');
        tooltip.setAttribute('aria-hidden', 'false');
        tooltip.style.visibility = 'hidden';

        var rect = anchor.getBoundingClientRect();
        tooltip.style.left = (rect.right + 8) + 'px';
        tooltip.style.top = rect.top + 'px';

        var tooltipRect = tooltip.getBoundingClientRect();
        var top = Math.max(8, Math.min(
            rect.top + (rect.height / 2) - (tooltipRect.height / 2),
            window.innerHeight - tooltipRect.height - 8
        ));
        tooltip.style.top = top + 'px';
        tooltip.style.visibility = 'visible';
    }

    function bindSidebarTooltips(sidebar, tooltip) {
        if (!sidebar || !tooltip) return;

        sidebar.querySelectorAll('[data-sidebar-tooltip]').forEach(function (anchor) {
            anchor.addEventListener('mouseenter', function () {
                showTooltip(tooltip, anchor);
            });
            anchor.addEventListener('mouseleave', function () {
                hideTooltip(tooltip);
            });
            anchor.addEventListener('focus', function () {
                showTooltip(tooltip, anchor);
            });
            anchor.addEventListener('blur', function () {
                hideTooltip(tooltip);
            });
        });

        sidebar.querySelector('.sidebar-nav')?.addEventListener('scroll', function () {
            hideTooltip(tooltip);
        }, { passive: true });
    }

    function hideFlyout(flyout) {
        if (!flyout) return;
        flyout.classList.add('hidden');
        flyout.setAttribute('aria-hidden', 'true');
        flyout.innerHTML = '';
        flyout.dataset.anchor = '';
        clearFlyoutOpenState();
        hideTooltip(document.getElementById('sidebar-tooltip'));
    }

    function showFlyout(flyout, btn, panel) {
        if (!flyout || !btn || !panel) return;

        var title = panel.dataset.submenuTitle || 'Menu';
        var links = panel.querySelectorAll('.sidebar-sublink');
        if (!links.length) return;

        clearFlyoutOpenState();
        btn.classList.add('is-flyout-open');
        hideTooltip(document.getElementById('sidebar-tooltip'));

        var html = '<p class="sidebar-flyout-title">' + title + '</p><div class="sidebar-flyout-links">';
        links.forEach(function (link) {
            var active = link.classList.contains('is-active') ? ' is-active' : '';
            var icon = link.querySelector('.sidebar-sublink-icon');
            var label = link.querySelector('.sidebar-sublink-label');
            html += '<a href="' + link.getAttribute('href') + '" class="sidebar-flyout-link' + active + '" role="menuitem">';
            html += icon ? icon.outerHTML : '';
            html += '<span class="sidebar-sublink-label">' + (label ? label.textContent.trim() : link.textContent.trim()) + '</span>';
            html += '</a>';
        });
        html += '</div>';

        flyout.innerHTML = html;
        flyout.classList.remove('hidden');
        flyout.setAttribute('aria-hidden', 'false');
        flyout.style.visibility = 'hidden';

        var rect = btn.getBoundingClientRect();
        flyout.style.left = (rect.right + 8) + 'px';
        flyout.style.top = rect.top + 'px';

        var flyoutRect = flyout.getBoundingClientRect();
        var top = Math.max(8, Math.min(rect.top, window.innerHeight - flyoutRect.height - 8));
        flyout.style.top = top + 'px';
        flyout.style.visibility = 'visible';
    }

    function applyCollapsedState(sidebar, collapseBtn, collapsed) {
        var desktop = isDesktop();
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        sidebar.classList.toggle('is-collapsed', collapsed && desktop);
        localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');

        if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            collapseBtn.setAttribute('aria-label', collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar');
            collapseBtn.setAttribute('title', collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar');
            var icon = collapseBtn.querySelector('.sidebar-collapse-icon');
            if (icon) {
                icon.innerHTML = collapsed
                    ? '<i class="ti ti-layout-sidebar-left-expand text-2xl leading-none" aria-hidden="true"></i>'
                    : '<i class="ti ti-layout-sidebar-left-collapse text-2xl leading-none" aria-hidden="true"></i>';
            }
        }

        if (collapsed && desktop) {
            closeAllSubmenus(null);
            hideFlyout(document.getElementById('sidebar-flyout'));
            hideTooltip(document.getElementById('sidebar-tooltip'));
        } else if (desktop) {
            hideTooltip(document.getElementById('sidebar-tooltip'));
            var activeSublink = sidebar.querySelector('.sidebar-sublink.is-active');
            if (activeSublink) {
                var panel = activeSublink.closest('[data-submenu]');
                var btn = panel?.id
                    ? sidebar.querySelector('[data-submenu-toggle="' + panel.id + '"]')
                    : null;
                setSubmenuOpen(btn, panel, true);
            }
        }
    }

    window.initSidebar = function () {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var toggle = document.getElementById('sidebar-toggle');
        var collapseBtn = document.getElementById('sidebar-collapse');
        var flyout = document.getElementById('sidebar-flyout');
        var tooltip = document.getElementById('sidebar-tooltip');

        if (!sidebar) return;

        if (flyout && flyout.parentElement !== document.body) {
            document.body.appendChild(flyout);
        }

        if (tooltip && tooltip.parentElement !== document.body) {
            document.body.appendChild(tooltip);
        }

        applyCollapsedState(sidebar, collapseBtn, localStorage.getItem(STORAGE_KEY) === 'true');
        bindSidebarTooltips(sidebar, tooltip);

        toggle?.addEventListener('click', function () {
            sidebar.classList.toggle('-translate-x-full');
            overlay?.classList.toggle('hidden');
            hideFlyout(flyout);
        });

        document.querySelectorAll('[data-mobile-sidebar-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (isDesktop()) return;
                sidebar.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
                hideFlyout(flyout);
            });
        });

        document.querySelectorAll('.mobile-bottom-nav__item[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                closeMobileSidebar(sidebar, overlay);
                hideFlyout(flyout);
            });
        });

        overlay?.addEventListener('click', function () {
            closeMobileSidebar(sidebar, overlay);
            hideFlyout(flyout);
        });

        collapseBtn?.addEventListener('click', function () {
            applyCollapsedState(sidebar, collapseBtn, !document.documentElement.classList.contains('sidebar-collapsed'));
        });

        document.querySelectorAll('[data-submenu-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();

                var targetId = btn.dataset.submenuToggle;
                var target = document.getElementById(targetId);

                if (isCollapsed()) {
                    var flyoutVisible = flyout && !flyout.classList.contains('hidden')
                        && flyout.dataset.anchor === targetId;

                    if (flyoutVisible) {
                        hideFlyout(flyout);
                        return;
                    }

                    hideFlyout(flyout);
                    showFlyout(flyout, btn, target);
                    if (flyout) flyout.dataset.anchor = targetId;
                    return;
                }

                var willOpen = target?.classList.contains('hidden');
                hideFlyout(flyout);

                if (willOpen) {
                    closeAllSubmenus(targetId);
                }

                setSubmenuOpen(btn, target, willOpen);
            });
        });

        sidebar.querySelectorAll('.sidebar-sublink').forEach(function (link) {
            link.addEventListener('click', function () {
                closeMobileSidebar(sidebar, overlay);
                hideFlyout(flyout);
            });
        });

        flyout?.addEventListener('click', function (e) {
            if (e.target.closest('.sidebar-flyout-link')) {
                closeMobileSidebar(sidebar, overlay);
                hideFlyout(flyout);
            }
        });

        document.addEventListener('click', function (e) {
            if (!flyout || flyout.classList.contains('hidden')) return;
            if (flyout.contains(e.target)) return;
            if (e.target.closest('[data-submenu-toggle]')) return;
            hideFlyout(flyout);
        });

        window.addEventListener('resize', function () {
            var desktop = isDesktop();
            sidebar.classList.toggle('is-collapsed', desktop && document.documentElement.classList.contains('sidebar-collapsed'));

            if (desktop) {
                sidebar.classList.remove('-translate-x-full');
                overlay?.classList.add('hidden');
            } else {
                hideFlyout(flyout);
                hideTooltip(tooltip);
                sidebar.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hideFlyout(flyout);
                hideTooltip(tooltip);
                closeMobileSidebar(sidebar, overlay);
            }
        });
    };
})();
