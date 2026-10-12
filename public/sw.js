// Service worker mínimo: hace la app instalable y muestra una página clara
// cuando no hay conexión. No guarda páginas del sistema (llevan sesión y
// tokens CSRF) ni toca las peticiones POST, como el envío del GPS.
const VERSION = 'obsidiana-v1';
const OFFLINE = '/offline.html';

self.addEventListener('install', event => {
    event.waitUntil(caches.open(VERSION).then(c => c.add(OFFLINE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== VERSION).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(() => caches.open(VERSION).then(c => c.match(OFFLINE)))
    );
});
