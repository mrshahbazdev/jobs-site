/**
 * Client-side helper for registering the JobsPic service worker and
 * subscribing the user to web-push notifications.
 *
 * Exposes window.JobsPicPush with:
 *   isSupported(): boolean
 *   permission(): NotificationPermission
 *   subscribe(): Promise<PushSubscription>
 *   unsubscribe(): Promise<boolean>
 *   getStatus(): Promise<'unsupported'|'blocked'|'subscribed'|'unsubscribed'>
 */

const SW_URL = '/sw.js';
const PUBLIC_KEY_URL = '/push/public-key';
const SUBSCRIBE_URL = '/push/subscribe';
const UNSUBSCRIBE_URL = '/push/unsubscribe';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = window.atob(base64);
  const output = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
  return output;
}

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : null;
}

async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) return null;
  const existing = await navigator.serviceWorker.getRegistration(SW_URL);
  if (existing) return existing;
  return navigator.serviceWorker.register(SW_URL, { scope: '/' });
}

function isSupported() {
  return (
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window
  );
}

async function getStatus() {
  if (!isSupported()) return 'unsupported';
  if (Notification.permission === 'denied') return 'blocked';

  const reg = await navigator.serviceWorker.getRegistration(SW_URL);
  if (!reg) return 'unsubscribed';
  const sub = await reg.pushManager.getSubscription();
  return sub ? 'subscribed' : 'unsubscribed';
}

async function fetchPublicKey() {
  const res = await fetch(PUBLIC_KEY_URL, { credentials: 'same-origin' });
  if (!res.ok) {
    throw new Error(`VAPID key fetch failed: ${res.status}`);
  }
  const { publicKey } = await res.json();
  if (!publicKey) throw new Error('VAPID public key is empty');
  return publicKey;
}

async function sendSubscriptionToServer(subscription, extra = {}) {
  const payload = {
    ...subscription.toJSON(),
    contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm']).includes('aes128gcm')
      ? 'aes128gcm'
      : 'aesgcm',
    ...extra,
  };

  const res = await fetch(SUBSCRIBE_URL, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    throw new Error(`Subscribe failed: ${res.status}`);
  }
  return res.json();
}

async function sendUnsubscribeToServer(endpoint) {
  try {
    await fetch(UNSUBSCRIBE_URL, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ endpoint }),
    });
  } catch (_) {
    /* best effort */
  }
}

async function subscribe(extra = {}) {
  if (!isSupported()) throw new Error('Push notifications are not supported in this browser.');

  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    throw new Error(`Notification permission ${permission}`);
  }

  const reg = await registerServiceWorker();
  if (!reg) throw new Error('Service worker registration failed.');

  const publicKey = await fetchPublicKey();

  let subscription = await reg.pushManager.getSubscription();
  if (!subscription) {
    subscription = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(publicKey),
    });
  }

  await sendSubscriptionToServer(subscription, extra);
  return subscription;
}

async function unsubscribe() {
  const reg = await navigator.serviceWorker.getRegistration(SW_URL);
  if (!reg) return false;

  const sub = await reg.pushManager.getSubscription();
  if (!sub) return false;

  await sendUnsubscribeToServer(sub.endpoint);
  return sub.unsubscribe();
}

// Always register the SW on load (even if the user hasn't opted in to push yet,
// so caching + install prompt work).
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    registerServiceWorker().catch((err) => {
      console.warn('[JobsPic] SW registration failed:', err);
    });
  });
}

window.JobsPicPush = {
  isSupported,
  getStatus,
  subscribe,
  unsubscribe,
  permission: () => (isSupported() ? Notification.permission : 'default'),
};
