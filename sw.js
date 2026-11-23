// Service worker leevel.app : réception des notifications push et ouverture de la bonne page au clic.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

self.addEventListener('push', e => {
  let d = {};
  try { d = e.data ? e.data.json() : {}; } catch (_) { d = { title: 'leevel.app', body: e.data ? e.data.text() : '' }; }
  e.waitUntil(self.registration.showNotification(d.title || 'leevel.app', {
    body: d.body || '', icon: d.icon || 'assets/icon-192.png', badge: d.badge || 'assets/icon-192.png',
    tag: d.tag || undefined, renotify: !!d.tag, data: { url: d.url || 'index.php' }, lang: 'fr'
  }));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const target = new URL(e.notification.data && e.notification.data.url || 'index.php', self.registration.scope).href;
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
    for (const c of list) { if ('focus' in c) { c.navigate(target).catch(() => {}); return c.focus(); } }
    return self.clients.openWindow(target);
  }));
});
