// resources/js/app.js

// Import Laravel Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Laravel Echo with Reverb
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Connection event listeners
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Connected to Reverb WebSocket');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('❌ Disconnected from Reverb WebSocket');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('❌ Reverb WebSocket error:', error);
});

window.Echo.connector.pusher.connection.bind('failed', () => {
    console.error('❌ Failed to connect to Reverb WebSocket');
});

// Auto-reconnection logic
window.Echo.connector.pusher.connection.bind('unavailable', () => {
    console.log('🔄 Connection unavailable, attempting to reconnect...');
    setTimeout(() => {
        window.Echo.connector.pusher.connect();
    }, 5000);
});

// Global notification function
window.showNotification = function(title, message, type = 'info') {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: message,
            icon: '/storage/img/stretcher.png',
            tag: 'stretcher-notification'
        });
    }
};

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
