(function () {
    var state = {
        group: '',
        items: [],
        index: 0,
        active: false,
    };

    var root = null;
    var image = null;
    var caption = null;
    var prevBtn = null;
    var nextBtn = null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureElements() {
        if (root) return true;
        root = document.getElementById('app-lightbox');
        image = document.getElementById('app-lightbox-image');
        caption = document.getElementById('app-lightbox-caption');
        if (!root || !image || !caption) return false;

        prevBtn = root.querySelector('[data-lightbox-prev]');
        nextBtn = root.querySelector('[data-lightbox-next]');
        return true;
    }

    function collectGroupItems(group) {
        var selector = '[data-lightbox-group="' + group + '"]';
        var nodes = document.querySelectorAll(selector);
        return Array.prototype.slice.call(nodes).map(function (node) {
            return {
                src: node.getAttribute('href') || node.dataset.src || '',
                caption: node.dataset.caption || '',
            };
        }).filter(function (item) {
            return item.src;
        });
    }

    function updateNavButtons() {
        var showNav = state.items.length > 1;
        if (!prevBtn || !nextBtn) return;
        prevBtn.classList.toggle('hidden', !showNav);
        nextBtn.classList.toggle('hidden', !showNav);
    }

    function renderCurrent() {
        if (!state.items.length || !image || !caption) return;
        var current = state.items[state.index];
        image.src = current.src;
        image.alt = current.caption || 'Bukti';
        caption.textContent = current.caption || '';
        updateNavButtons();
    }

    function openLightbox(group, index) {
        if (!ensureElements()) return;
        state.group = group;
        state.items = collectGroupItems(group);
        state.index = Math.max(0, Math.min(index, state.items.length - 1));
        if (!state.items.length) return;

        state.active = true;
        renderCurrent();
        root.classList.remove('hidden');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
    }

    function closeLightbox() {
        if (!ensureElements() || !state.active) return;
        state.active = false;
        root.classList.add('hidden');
        root.setAttribute('aria-hidden', 'true');
        image.src = '';
        caption.textContent = '';
        document.body.classList.remove('lightbox-open');
        if (prevBtn) prevBtn.classList.add('hidden');
        if (nextBtn) nextBtn.classList.add('hidden');
    }

    function navigate(step) {
        if (!state.active || state.items.length <= 1) return;
        var next = state.index + step;
        if (next < 0) next = state.items.length - 1;
        if (next >= state.items.length) next = 0;
        state.index = next;
        renderCurrent();
    }

    function renderBuktiDetailList(container, bukti, options) {
        if (!container) return;
        var group = options && options.group ? options.group : ('bukti-' + Date.now());
        container.innerHTML = '';

        if (!bukti || !bukti.length) {
            container.innerHTML = '<p class="text-xs text-slate-400">Tidak ada bukti.</p>';
            return;
        }

        bukti.forEach(function (item) {
            var name = escapeHtml(item.original_name || 'File');
            if (item.is_image) {
                var html = ''
                    + '<a href="' + item.url + '"'
                    + ' class="block detail-bukti-image"'
                    + ' data-lightbox-group="' + group + '"'
                    + ' data-caption="' + name + '">'
                    + '<img src="' + item.url + '" class="max-h-32 rounded border" alt="' + name + '">'
                    + '</a>';
                container.insertAdjacentHTML('beforeend', html);
            } else {
                var fileHtml = ''
                    + '<a href="' + item.url + '" target="_blank" rel="noopener"'
                    + ' class="flex items-center gap-2 text-sm text-primary-600 hover:underline">'
                    + '<i class="ti ti-file-pdf"></i> ' + name
                    + '</a>';
                container.insertAdjacentHTML('beforeend', fileHtml);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-lightbox-group]');
        if (trigger && (trigger.tagName === 'A' || trigger.dataset.src)) {
            event.preventDefault();
            var group = trigger.dataset.lightboxGroup;
            var items = collectGroupItems(group);
            var href = trigger.getAttribute('href') || trigger.dataset.src || '';
            var index = items.findIndex(function (item) { return item.src === href; });
            openLightbox(group, index >= 0 ? index : 0);
            return;
        }

        if (event.target.closest('[data-lightbox-close]')) {
            event.preventDefault();
            closeLightbox();
            return;
        }

        if (event.target.closest('[data-lightbox-prev]')) {
            event.preventDefault();
            navigate(-1);
            return;
        }

        if (event.target.closest('[data-lightbox-next]')) {
            event.preventDefault();
            navigate(1);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!state.active) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            event.stopPropagation();
            navigate(-1);
            return;
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            event.stopPropagation();
            navigate(1);
        }
    }, true);

    window.renderBuktiDetailList = renderBuktiDetailList;
})();
