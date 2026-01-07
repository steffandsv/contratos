self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open('contract-manager-v1').then((cache) => {
            return cache.addAll([
                '/',
                '/index.php',
                '/manifest.json',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
            ]);
        })
    );
});

self.addEventListener('fetch', (e) => {
    e.respondWith(
        caches.match(e.request).then((response) => {
            return response || fetch(e.request);
        })
    );
});
