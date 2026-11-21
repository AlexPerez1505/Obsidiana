// Service worker mínimo: hace la app instalable y muestra una página clara
// cuando no hay conexión. No guarda páginas del sistema (llevan sesión y
// tokens CSRF) ni toca las peticiones POST, como el envío del GPS.
//
// Usa "navigation preload": el navegador pide la página al mismo tiempo que
// despierta este worker, en vez de esperar a que arranque (en Safari eso
// sumaba retraso a cada cambio de pantalla).
const VERSION = 'obsidiana-v2';
const OFFLINE = '/offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(VERSION).then(c => c.add(OFFLINE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
    event.waitUntil(
        Promise.all([
            self.registration.navigationPreload ? self.registration.navigationPreload.enable().catch(() => {}) : null,
            caches.keys().then(keys => Promise.all(keys.filter(k => k !== VERSION).map(k => caches.delete(k)))),
        ]).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith((async () => {
        try {
            const pre = await event.preloadResponse;
            if (pre) return pre;
            return await fetch(event.request);
        } catch (e) {
            const cache = await caches.open(VERSION);
            return (await cache.match(OFFLINE)) || Response.error();
        }
    })());
});
