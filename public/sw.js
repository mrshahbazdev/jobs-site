/* JobsPic service worker — handles web-push notifications + minimal offline shell. */

const SW_VERSION = 'v1';
const RUNTIME_CACHE = `jobspic-runtime-${SW_VERSION}`;

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((k) => k.startsWith('jobspic-runtime-') && k !== RUNTIME_CACHE)
            .map((k) => caches.delete(k)),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Stale-while-revalidate for static build assets.
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
    event.respondWith(
      caches.open(RUNTIME_CACHE).then(async (cache) => {
        const cached = await cache.match(request);
        const network = fetch(request)
          .then((res) => {
            if (res && res.status === 200) cache.put(request, res.clone());
            return res;
          })
          .catch(() => cached);
        return cached || network;
      }),
    );
  }
});

self.addEventListener('push', (event) => {
  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = { title: 'JobsPic', body: event.data.text() };
    }
  }

  const title = data.title || 'New job on JobsPic';
  const options = {
    body: data.body || 'A new job has just been posted.',
    icon: data.icon || '/icons/icon-192x192.png',
    badge: data.badge || '/icons/icon-192x192.png',
    tag: data.tag || 'jobspic-default',
    renotify: true,
    data: { url: data.url || '/' },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = (event.notification.data && event.notification.data.url) || '/';

  event.waitUntil(
    self.clients
      .matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        for (const client of clientList) {
          if ('focus' in client) {
            try {
              const clientUrl = new URL(client.url);
              if (clientUrl.pathname === new URL(target, self.location.origin).pathname) {
                return client.focus();
              }
            } catch (_) {
              /* ignore */
            }
          }
        }
        if (self.clients.openWindow) {
          return self.clients.openWindow(target);
        }
        return null;
      }),
  );
});
