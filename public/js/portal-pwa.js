(function () {
    var installPrompt = null;
    var banner = null;

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function dismissBanner() {
        if (banner) {
            banner.remove();
            banner = null;
        }
    }

    function appName() {
        return window.PORTAL_PWA_APP_NAME || 'ITTIHAD APP';
    }

    function showInstallBanner() {
        if (isStandalone() || banner || !installPrompt) {
            return;
        }

        if (localStorage.getItem('portal-pwa-install-dismissed') === '1') {
            return;
        }

        banner = document.createElement('div');
        banner.className = 'portal-pwa-install';
        banner.innerHTML = ''
            + '<div class="portal-pwa-install__content">'
            + '  <img src="' + (window.PORTAL_PWA_ICON || '/pwa/icon-192.png') + '" alt="" class="portal-pwa-install__icon" width="40" height="40">'
            + '  <div class="portal-pwa-install__text">'
            + '    <p class="portal-pwa-install__title">Pasang aplikasi ' + appName() + '</p>'
            + '    <p class="portal-pwa-install__desc">Akses lebih cepat dari layar utama perangkat Anda.</p>'
            + '  </div>'
            + '</div>'
            + '<div class="portal-pwa-install__actions">'
            + '  <button type="button" class="btn-secondary btn-sm" data-portal-pwa-dismiss>'
            + '    <i class="ti ti-clock" aria-hidden="true"></i> Nanti'
            + '  </button>'
            + '  <button type="button" class="btn-primary btn-sm" data-portal-pwa-install>'
            + '    <i class="ti ti-download" aria-hidden="true"></i> Pasang'
            + '  </button>'
            + '</div>';

        document.body.appendChild(banner);

        banner.querySelector('[data-portal-pwa-dismiss]')?.addEventListener('click', function () {
            localStorage.setItem('portal-pwa-install-dismissed', '1');
            dismissBanner();
        });

        banner.querySelector('[data-portal-pwa-install]')?.addEventListener('click', async function () {
            if (!installPrompt) {
                return;
            }

            installPrompt.prompt();
            var result = await installPrompt.userChoice;
            installPrompt = null;
            dismissBanner();

            if (result.outcome === 'accepted') {
                window.showToast?.(appName() + ' siap digunakan dari layar utama.', 'success');
            }
        });
    }

    async function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        try {
            await navigator.serviceWorker.register(window.PORTAL_PWA_SW_URL || '/portal/sw.js', {
                scope: '/portal/',
            });
        } catch (error) {
            console.warn('Portal PWA service worker registration failed:', error);
        }
    }

    window.initPortalPwa = function () {
        if (!document.documentElement.dataset.portalPwa) {
            return;
        }

        registerServiceWorker();

        window.addEventListener('beforeinstallprompt', function (event) {
            event.preventDefault();
            installPrompt = event;
            showInstallBanner();
        });

        window.addEventListener('appinstalled', function () {
            installPrompt = null;
            dismissBanner();
            window.showToast?.(appName() + ' berhasil dipasang.', 'success');
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initPortalPwa?.();
    });
})();
