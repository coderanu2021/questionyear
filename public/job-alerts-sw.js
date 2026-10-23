self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { title: 'Government job update', body: 'A new official government notice is available.' };
    }
    event.waitUntil(self.registration.showNotification(payload.title || 'Government job update', {
        body: payload.body || 'A new official government notice is available.',
        icon: '/favicon.png',
        data: { url: payload.url || '/government-jobs' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = new URL(event.notification.data?.url || '/government-jobs', self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
        const existingClient = clients.find((client) => client.url === targetUrl);
        return existingClient ? existingClient.focus() : self.clients.openWindow(targetUrl);
    }));
});
