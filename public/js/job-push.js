(() => {
    const button = document.querySelector('[data-job-push-button]');
    const status = document.querySelector('[data-job-push-status]');
    if (!button || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const decodeKey = (value) => {
        const padded = value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - value.length % 4) % 4);
        return Uint8Array.from(atob(padded), (character) => character.charCodeAt(0));
    };
    const updateButton = (subscribed) => {
        button.hidden = false;
        button.textContent = subscribed ? 'Turn off job alerts' : 'Enable job alerts';
        button.dataset.subscribed = String(subscribed);
    };

    fetch('/government-jobs/push-key', { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then(async ({ publicKey }) => {
            if (!publicKey || !csrfToken || !window.isSecureContext) {
                return;
            }
            const registration = await navigator.serviceWorker.register('/job-alerts-sw.js');
            const subscription = await registration.pushManager.getSubscription();
            updateButton(Boolean(subscription));
        })
        .catch(() => {});

    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            const keyResponse = await fetch('/government-jobs/push-key', { headers: { Accept: 'application/json' } });
            const { publicKey } = await keyResponse.json();
            if (!publicKey) {
                throw new Error('Job alerts are not configured yet.');
            }
            const registration = await navigator.serviceWorker.register('/job-alerts-sw.js');
            let subscription = await registration.pushManager.getSubscription();
            if (subscription && button.dataset.subscribed === 'true') {
                await fetch('/government-jobs/push-subscriptions', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });
                await subscription.unsubscribe();
                updateButton(false);
                status.textContent = 'Job alerts turned off.';
                return;
            }
            if (Notification.permission !== 'granted') {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    throw new Error('Allow notifications in your browser to receive job alerts.');
                }
            }
            subscription ??= await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: decodeKey(publicKey) });
            const response = await fetch('/government-jobs/push-subscriptions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify(subscription.toJSON()),
            });
            if (!response.ok) {
                throw new Error('Could not save this browser subscription.');
            }
            updateButton(true);
            status.textContent = 'Job alerts enabled for this browser.';
        } catch (error) {
            status.textContent = error instanceof Error ? error.message : 'Could not update job alerts.';
        } finally {
            button.disabled = false;
        }
    });
})();
