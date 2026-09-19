(function () {
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        document.getElementById('id-card-modal-content').innerHTML = '';
    }

    function resetCloneStyles(card) {
        card.style.position = 'relative';
        card.style.transform = 'none';
        card.style.left = 'auto';
        card.style.top = 'auto';
        card.style.margin = '0';
        card.style.boxShadow = 'none';
    }

    function printCards(cardNodes) {
        if (!cardNodes.length) return;

        var existing = document.getElementById('id-card-print-root');
        if (existing) {
            existing.remove();
        }

        var root = document.createElement('div');
        root.id = 'id-card-print-root';
        root.setAttribute('aria-hidden', 'true');

        var sheet = document.createElement('div');
        sheet.className = 'id-card-print-sheet';

        cardNodes.forEach(function (node) {
            var clone = node.cloneNode(true);
            resetCloneStyles(clone);
            sheet.appendChild(clone);
        });

        root.appendChild(sheet);
        document.body.appendChild(root);

        var cleaned = false;
        function cleanup() {
            if (cleaned) {
                return;
            }

            cleaned = true;
            root.remove();
            window.removeEventListener('afterprint', cleanup);
        }

        window.addEventListener('afterprint', cleanup);

        function startPrint() {
            window.print();
            setTimeout(cleanup, 10000);
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () {
                requestAnimationFrame(startPrint);
            }).catch(startPrint);
        } else {
            requestAnimationFrame(startPrint);
        }
    }

    window.initIdCards = function () {
        var modal = document.getElementById('id-card-modal');
        var content = document.getElementById('id-card-modal-content');
        var titleEl = document.getElementById('id-card-modal-title');

        document.querySelectorAll('[data-id-card-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var template = document.getElementById(btn.dataset.idCardOpen);
                if (!template || !content || !modal) return;

                content.innerHTML = template.innerHTML;
                if (titleEl) titleEl.textContent = btn.dataset.idCardTitle || 'Detail Kartu';
                modal.classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-id-card-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () { closeModal(modal); });
        });

        document.querySelector('[data-id-card-print]')?.addEventListener('click', function () {
            if (!content) return;
            var cards = content.querySelectorAll('.id-card');
            printCards(Array.from(cards));
        });

        document.querySelectorAll('[data-id-card-print-target]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.querySelector(btn.dataset.idCardPrintTarget);
                if (!target) return;
                printCards(Array.from(target.querySelectorAll('.id-card')));
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal(modal);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initIdCards?.();
    });
})();
