(function () {
    var VIEWPORT_PADDING = 8;
    var GAP = 6;

    function resolveAnchor(toggle) {
        var split = toggle.closest('.dropdown-button__split');
        if (split) {
            return split;
        }

        return toggle;
    }

    function resolveAlign(toggle) {
        var root = toggle.closest('[data-dropdown-button]');
        if (root && root.classList.contains('dropdown-button--align-start')) {
            return 'start';
        }

        return 'end';
    }

    function measurePanel(panel) {
        panel.style.position = 'fixed';
        panel.style.visibility = 'hidden';
        panel.style.display = 'block';
        panel.style.top = '0px';
        panel.style.left = '-9999px';
        panel.style.zIndex = '200';

        return {
            width: panel.offsetWidth,
            height: panel.offsetHeight,
        };
    }

    window.positionFloatingMenuPanel = function (toggle, panel, options) {
        options = options || {};

        var align = options.align || resolveAlign(toggle);
        var anchor = options.anchor || resolveAnchor(toggle);
        var gap = typeof options.gap === 'number' ? options.gap : GAP;
        var padding = typeof options.padding === 'number' ? options.padding : VIEWPORT_PADDING;

        var size = measurePanel(panel);
        var rect = anchor.getBoundingClientRect();

        var panelWidth = size.width > 0 ? size.width : 216;
        var panelHeight = size.height > 0 ? size.height : 0;

        var left;
        if (align === 'start') {
            left = rect.left;
        } else {
            left = rect.right - panelWidth;
        }

        left = Math.max(padding, Math.min(left, window.innerWidth - panelWidth - padding));

        var top = rect.bottom + gap;
        var placement = 'bottom';

        if (panelHeight > 0 && top + panelHeight > window.innerHeight - padding) {
            var topAbove = rect.top - panelHeight - gap;
            if (topAbove >= padding) {
                top = topAbove;
                placement = 'top';
            } else {
                top = Math.max(padding, window.innerHeight - panelHeight - padding);
            }
        }

        panel.style.top = top + 'px';
        panel.style.left = left + 'px';
        panel.style.visibility = '';
        panel.dataset.placement = placement;

        return placement;
    };

    window.resetFloatingMenuPanelStyles = function (panel) {
        panel.style.top = '';
        panel.style.left = '';
        panel.style.zIndex = '';
        panel.style.visibility = '';
        panel.style.position = '';
        panel.style.display = '';
        delete panel.dataset.placement;
    };
})();
