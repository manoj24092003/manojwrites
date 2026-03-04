const CACHE_NAME = 'site-v5';

const FILES_TO_CACHE = [
    "manifest.json",
    "style.css"
];

// Install static cache
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(FILES_TO_CACHE);
        })
    );
    self.skipWaiting();
});

// Delete old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))
        )
    );
    self.clients.claim();
});

// Fetch logic
self.addEventListener('fetch', event => {

    const url = event.request.url;

    // ALWAYS fresh for PHP
    if (url.endsWith(".php")) {
        event.respondWith(fetch(event.request));
        return;
    }

    // Cache-first for everything else
    event.respondWith(
        caches.match(event.request).then(cacheRes => {
            return (
                cacheRes ||
                fetch(event.request).then(networkRes => {
                    return networkRes;
                })
            );
        })
    );
});
// --- FIX VIDEO SCREEN TURNING OFF ---
// Disable ALL fetch interception (very important)
self.addEventListener("fetch", event => {
    return; // do nothing → do NOT intercept requests
});

// =======================
// FIREBASE MESSAGING
// =======================
importScripts("https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js");

firebase.initializeApp({
  apiKey: "AIzaSyBIKsBWdgDJ12za2cbPFqeKC1PoAZ1JOLM",
  authDomain: "animewatch01.firebaseapp.com",
  projectId: "animewatch01",
  storageBucket: "animewatch01.appspot.com",
  messagingSenderId: "261269655638",
  appId: "1:261269655638:web:de3de7b794c979491a8bf7"
});

const messaging = firebase.messaging();

// =======================
// BACKGROUND MESSAGE
// =======================
messaging.onBackgroundMessage(payload => {
  console.log("Background message:", payload);

  const notif = payload.notification || {};
  const data = payload.data || {};
  const fcmLink = payload.fcmOptions?.link || payload.webpush?.fcm_options?.link;

  self.registration.showNotification(notif.title, {
    body: notif.body,
    icon: notif.icon,
    image: notif.image,
    data: { url: fcmLink }   // SAVE CLICK URL
  });
});



// ⬇️ PASTE THIS BLOCK HERE
self.addEventListener("push", (event) => {
  if (!event.data) return;

  const payload = event.data.json();
  const notif = payload.notification || {};
  const data = payload.data || {};
  const targetUrl =
    payload.fcmOptions?.link ||
    payload.webpush?.fcm_options?.link ||
    data.click_action ||
    "/";

  event.waitUntil(
    self.registration.showNotification(notif.title, {
      body: notif.body,
      icon: notif.icon,
      image: notif.image,
      data: { url: targetUrl }
    })
  );
});
// ⬆️ PASTE THIS BLOCK HERE



// =======================
// NOTIFICATION CLICK
// =======================
self.addEventListener("notificationclick", event => {
  event.notification.close();

  const targetUrl =
    event.notification.data?.url ||
    event.notification.data?.click_action ||
    "/";

  event.waitUntil(
    clients.matchAll({ includeUncontrolled: true, type: "window" })
      .then(clientList => {
        for (const client of clientList) {
          if (client.url.includes(targetUrl) && "focus" in client) {
            return client.focus();
          }
        }
        return clients.openWindow(targetUrl);
      })
  );
});