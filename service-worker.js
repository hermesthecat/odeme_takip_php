/**
 * @author A. Kerem Gök
 * Service Worker
 */

const CACHE_NAME = 'odeme-takip-v1';
const OFFLINE_URL = '/offline.html';

// Önbelleğe alınacak dosyalar
const CACHE_FILES = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/css/dark-theme.css',
    '/assets/js/main.js',
    '/assets/js/auth.js',
    '/assets/js/charts.js',
    '/assets/js/dashboard.js',
    '/assets/js/income.js',
    '/assets/js/expense.js',
    '/assets/js/savings.js',
    '/assets/js/bills.js',
    '/assets/js/reports.js',
    '/assets/img/icon-72x72.png',
    '/assets/img/icon-96x96.png',
    '/assets/img/icon-128x128.png',
    '/assets/img/icon-144x144.png',
    '/assets/img/icon-152x152.png',
    '/assets/img/icon-192x192.png',
    '/assets/img/icon-384x384.png',
    '/assets/img/icon-512x512.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Service Worker kurulumu
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Önbellek açıldı');
                return cache.addAll(CACHE_FILES);
            })
            .then(() => self.skipWaiting())
    );
});

// Service Worker aktivasyonu
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            );
        })
        .then(() => self.clients.claim())
    );
});

// Fetch olayı yakalama
self.addEventListener('fetch', event => {
    // API isteklerini kontrol et
    if (event.request.url.includes('/api/')) {
        return handleApiRequest(event);
    }

    // Diğer istekler için önbellek stratejisi
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Önbellekte varsa döndür
                if (response) {
                    return response;
                }

                // Önbellekte yoksa ağdan al
                return fetch(event.request)
                    .then(response => {
                        // Geçerli yanıt değilse veya temel URL değilse direkt döndür
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Yanıtı önbelleğe al
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        // Ağ hatası varsa ve HTML isteğiyse offline sayfasını göster
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// API isteklerini yönet
function handleApiRequest(event) {
    return fetch(event.request)
        .then(response => {
            // API yanıtı başarılıysa döndür
            if (response.ok) return response;

            // Hata durumunda offline veriyi kontrol et
            return caches.match(event.request);
        })
        .catch(() => {
            // Ağ hatası durumunda offline veriyi döndür
            return caches.match(event.request);
        });
}

// Push bildirim olayı
self.addEventListener('push', event => {
    const options = {
        body: event.data.text(),
        icon: '/assets/img/icon-192x192.png',
        badge: '/assets/img/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Görüntüle',
                icon: '/assets/img/checkmark-72x72.png'
            },
            {
                action: 'close',
                title: 'Kapat',
                icon: '/assets/img/close-72x72.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Ödeme Takip', options)
    );
});

// Bildirim tıklama olayı
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Senkronizasyon olayı
self.addEventListener('sync', event => {
    if (event.tag === 'sync-expenses') {
        event.waitUntil(syncExpenses());
    }
});

// Giderleri senkronize et
async function syncExpenses() {
    try {
        const db = await openDB();
        const expenses = await db.getAll('offline-expenses');
        
        for (const expense of expenses) {
            try {
                const response = await fetch('/api/expenses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(expense)
                });

                if (response.ok) {
                    await db.delete('offline-expenses', expense.id);
                }
            } catch (error) {
                console.error('Gider senkronizasyon hatası:', error);
            }
        }
    } catch (error) {
        console.error('IndexedDB hatası:', error);
    }
}

// IndexedDB bağlantısı
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('odeme-takip', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('offline-expenses')) {
                db.createObjectStore('offline-expenses', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
} 