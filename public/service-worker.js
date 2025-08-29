const CACHE_NAME = 'presensi-rfid-cache-v1';
const REQUIRED_FILES = [
    "/",
    "/css/landing.css",
    "/icons/72x72.png",
    "/icons/96x96.png",
    "/icons/128x128.png",
    "/icons/144x144.png",
    "/icons/152x152.png",
    "/icons/192x192.png",
    "/icons/384x384.png",
    "/icons/512x512.png"
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(REQUIRED_FILES))
            .catch(err => console.error('Gagal menyimpan cache:', err))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => response || fetch(event.request))
            .catch(() => caches.match('/'))
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames =>
            Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            )
        ).then(() => self.clients.claim())
    );
});
