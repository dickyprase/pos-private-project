const VERSION = 'kopipos-v1';
const STATIC_CACHE = `${VERSION}-static`;
const APP_SHELL = [
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;

    const url = new URL(request.url);
    if (url.pathname === '/manifest.webmanifest' || url.pathname.startsWith('/icons/')) {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
        return;
    }

    if (request.destination === 'script' || request.destination === 'style' || request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fresh = fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
                return cached || fresh;
            }),
        );
    }
});
