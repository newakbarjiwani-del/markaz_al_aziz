const CACHE_NAME = 'portal-shell-v{{ $version }}';
const OFFLINE_URL = '/portal/offline';

/*
 * Note: Tailwind CDN (@tailwindcss/browser) is intentionally NOT precached —
 * it is third-party and large. Critical chrome visibility/layout fallbacks live
 * in /css/app.css so the portal shell remains usable if the CDN is unavailable.
 */
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/css/app.css',
    '/js/theme.js',
    '/js/toast.js',
    '/js/dialog.js',
    '/js/fetch-form.js',
    '/js/app.js',
    '/js/lightbox.js',
    '/js/portal-pwa.js',
    '/js/sidebar.js',
    '/js/portal-dashboard.js',
    '/logo.png',
    '/pwa/icon-192.png',
    '/pwa/icon-512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(PRECACHE_URLS);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key.startsWith('portal-shell-v') && key !== CACHE_NAME;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    var url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (!url.pathname.startsWith('/portal')) {
        return;
    }

    // Keep SPA-like feel in installed PWA: never leave portal shell for images/assets.
    if (event.request.mode === 'navigate' || event.request.destination === 'document') {
        event.respondWith(
            fetch(event.request).catch(function () {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function (cached) {
            var networkFetch = fetch(event.request).then(function (response) {
                if (response && response.status === 200 && response.type === 'basic') {
                    var copy = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(event.request, copy);
                    });
                }
                return response;
            }).catch(function () {
                return cached;
            });

            return cached || networkFetch;
        })
    );
});
