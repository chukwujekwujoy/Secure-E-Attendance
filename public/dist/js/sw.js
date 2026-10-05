// ============================================================
// NIKKI Service Worker — nikki-sw.js
// Responsibilities:
//   • Cache-first strategy for app shell (HTML/CSS/JS)
//   • Network-first strategy for API calls
//   • Background Sync via SyncManager API
//   • Push notifications for sync events
//   • Periodic delta pull when supported
// ============================================================

const SW_VERSION    = "nikki-v3";
const CACHE_NAME    = `${SW_VERSION}-shell`;
const API_PREFIX    = "/api/";

// App shell assets to pre-cache on install
const SHELL_ASSETS  = [
  "/",
  "/index.html",
  "/nikki-journal.js",
  "/nikki-sw.js",
  "/styles.css",
  "/manifest.json",
];

// Background sync tag names
const SYNC_TAG_PUSH = "nikki-push-sync";
const SYNC_TAG_PULL = "nikki-pull-delta";


// ─────────────────────────────────────────────
// INSTALL — Pre-cache shell assets
// ─────────────────────────────────────────────

self.addEventListener("install", (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(SHELL_ASSETS))
      .then(() => self.skipWaiting()) // activate immediately
  );
});


// ─────────────────────────────────────────────
// ACTIVATE — Purge old caches
// ─────────────────────────────────────────────

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== CACHE_NAME)
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim()) // take control of existing tabs
  );
});


// ─────────────────────────────────────────────
// FETCH — Routing strategy
// ─────────────────────────────────────────────

self.addEventListener("fetch", (e) => {
  const { request } = e;
  const url         = new URL(request.url);

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) return;

  if (url.pathname.startsWith(API_PREFIX)) {
    // API calls: network-first, fall back to a queued-sync response
    e.respondWith(networkFirst(request));
  } else {
    // Static assets: cache-first, fall back to network then cache
    e.respondWith(cacheFirst(request));
  }
});

/**
 * Cache-first: return from cache immediately if available,
 * else fetch from network and cache the response for next time.
 */
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    // Offline and not in cache — return offline page if available
    return caches.match("/index.html");
  }
}

/**
 * Network-first: try the network, fall back to cache.
 * If both fail, return a synthetic offline JSON response
 * so the app knows the request was queued.
 */
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;

    return new Response(
      JSON.stringify({ offline: true, queued: true }),
      { status: 202, headers: { "Content-Type": "application/json" } }
    );
  }
}


// ─────────────────────────────────────────────
// BACKGROUND SYNC — SyncManager API
// ─────────────────────────────────────────────

/**
 * Registered sync tags (from the main thread):
 *   "nikki-push-sync" — flush outgoing sync queue
 *   "nikki-pull-delta" — pull remote changes
 *
 * When back online after being offline, the browser fires
 * these events automatically.
 */
self.addEventListener("sync", (e) => {
  if (e.tag === SYNC_TAG_PUSH) {
    e.waitUntil(notifyClientsToSync("SYNC_REQUESTED"));
  }

  if (e.tag === SYNC_TAG_PULL) {
    e.waitUntil(notifyClientsToSync("PULL_REQUESTED"));
  }
});


// ─────────────────────────────────────────────
// PERIODIC SYNC — Pull delta on a schedule
// ─────────────────────────────────────────────

/**
 * Fires periodically (browser-controlled, ~1hr minimum).
 * Prompts clients to pull remote changes even if the tab was idle.
 */
self.addEventListener("periodicsync", (e) => {
  if (e.tag === SYNC_TAG_PULL) {
    e.waitUntil(notifyClientsToSync("PULL_REQUESTED"));
  }
});


// ─────────────────────────────────────────────
// PUSH NOTIFICATIONS
// ─────────────────────────────────────────────

/**
 * Receive a push from the server (e.g. "new note synced from another device").
 * Shows a notification and triggers a pull on the next active client.
 */
self.addEventListener("push", (e) => {
  const data    = e.data?.json() ?? {};
  const title   = data.title   ?? "NIKKI Journal";
  const body    = data.body    ?? "Your notes have been updated.";
  const options = {
    body,
    icon:  "/icons/icon-192.png",
    badge: "/icons/badge-72.png",
    tag:   "nikki-sync-notification",
    data:  { url: "/" },
  };

  e.waitUntil(
    self.registration.showNotification(title, options)
      .then(() => notifyClientsToSync("PULL_REQUESTED"))
  );
});

self.addEventListener("notificationclick", (e) => {
  e.notification.close();
  e.waitUntil(
    self.clients.matchAll({ type: "window" }).then(clients => {
      const focused = clients.find(c => c.focused);
      if (focused) return focused.focus();
      if (clients.length) return clients[0].focus();
      return self.clients.openWindow(e.notification.data?.url ?? "/");
    })
  );
});


// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

/**
 * Post a message to all active window clients.
 * The main thread's service worker message handler
 * calls flushSyncQueue() or pullDelta() in response.
 */
async function notifyClientsToSync(type) {
  const clients = await self.clients.matchAll({ type: "window" });
  for (const client of clients) {
    client.postMessage({ type });
  }
}