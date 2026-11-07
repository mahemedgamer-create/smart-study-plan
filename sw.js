// Service Worker — عمل الموقع دون إنترنت بعد أول تحميل
const CACHE = "khattati-v1";

self.addEventListener("install", () => {
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(clients.claim());
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET" || !req.url.startsWith(self.location.origin)) return;

  // صفحات التنقل: الشبكة أولًا مع نسخة مخبأة كاحتياط
  if (req.mode === "navigate") {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => caches.match(req).then((m) => m || caches.match("/")))
    );
    return;
  }

  // الأصول الثابتة: الكاش أولًا
  event.respondWith(
    caches.match(req).then(
      (hit) =>
        hit ||
        fetch(req).then((res) => {
          try {
            const path = new URL(req.url).pathname;
            if (res.ok && /\.(js|css|png|jpg|svg|woff2?|json|webmanifest)$/.test(path)) {
              const copy = res.clone();
              caches.open(CACHE).then((c) => c.put(req, copy));
            }
          } catch (e) {}
          return res;
        })
    )
  );
});
