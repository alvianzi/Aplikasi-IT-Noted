const CACHE_NAME = 'it-noted-shell-v3';
const SHELL_FILES = [
  './login.html',
  './app.html',
  './admin.html',
  './manifest.webmanifest',
  './assets/simpan-ui.css',
  './assets/simpan-ui.js?v=1.9.4',
  './assets/logo.png',
  './assets/favicon.png',
  './assets/pwa-192.png',
  './assets/pwa-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_FILES)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(key => key.startsWith('it-noted-shell-') && key !== CACHE_NAME)
        .map(key => caches.delete(key))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Login/session, notes, labels and uploaded files are always requested from the server.
  if (/\.php$/i.test(url.pathname) || url.pathname.includes('/uploads/')) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).then(response => {
      if (response.ok) {
        const copy = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
      }
      return response;
    }).catch(async () => (await caches.match(request)) || (await caches.match('./login.html'))));
    return;
  }

  event.respondWith(fetch(request).then(response => {
      if (response.ok && (url.pathname.includes('/assets/') || url.pathname.endsWith('.html') || url.pathname.endsWith('.webmanifest'))) {
        const copy = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
      }
      return response;
    }).catch(() => caches.match(request)));
});
