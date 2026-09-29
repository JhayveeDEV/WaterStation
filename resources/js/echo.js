import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const realtimeEnabled = window.AppEnableRealtime !== false && !!window.AppUserId && !!import.meta.env.VITE_REVERB_APP_KEY && !!import.meta.env.VITE_REVERB_HOST;

if (realtimeEnabled) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    document.addEventListener('DOMContentLoaded', () => {
        const banner = document.getElementById('connection-banner');
        if (!banner || !window.Echo?.connector?.pusher) return;

        const pusher = window.Echo.connector.pusher;
        pusher.connection.bind('state_change', (states) => {
            banner.classList.toggle('hidden', states.current === 'connected');
        });
    });
}
