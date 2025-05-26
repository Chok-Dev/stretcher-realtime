
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// ตั้งค่า Echo สำหรับ Reverb
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    enableLogging: import.meta.env.DEV, // เปิด logging ใน dev mode
});

// Global error handling
window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('Echo connection error:', err);
});

// เชื่อมต่อ channel หลัก
window.Echo.channel('stretcher-updates')
    .listen('StretcherUpdated', (e) => {
        console.log('Global stretcher update received:', e);
        // Livewire components จะรับ event นี้อัตโนมัติ
    });

console.log('Echo initialized for Stretcher System');