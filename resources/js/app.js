// resources/js/app.js

console.log('🚀 App.js with WebSocket starting...');

// ตรวจสอบว่าต้องการ WebSocket ไหม
const enableWebSocket = import.meta.env.VITE_REVERB_APP_KEY ? true : false;

if (enableWebSocket) {
    import('laravel-echo').then(({ default: Echo }) => {
        import('pusher-js').then(({ default: Pusher }) => {
            window.Pusher = Pusher;
            
            console.log('🔄 Setting up WebSocket...');
            
            try {
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: import.meta.env.VITE_REVERB_APP_KEY,
                    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
                    wsPort: import.meta.env.VITE_REVERB_PORT || 8081,
                    wssPort: import.meta.env.VITE_REVERB_PORT || 8081,
                    forceTLS: false,
                    enabledTransports: ['ws'],
                    enableLogging: true,
                });
                
                window.Echo.connector.pusher.connection.bind('connected', () => {
                    console.log('✅ WebSocket connected');
                    updateConnectionStatus(true);
                });
                
                window.Echo.connector.pusher.connection.bind('error', (err) => {
                    console.log('❌ WebSocket error:', err);
                    updateConnectionStatus(false);
                });
                
            } catch (error) {
                console.error('❌ Failed to setup WebSocket:', error);
                updateConnectionStatus(false);
            }
        });
    });
} else {
    console.log('⚙️ WebSocket disabled (no VITE_REVERB_APP_KEY)');
    updateConnectionStatus(false);
}

function updateConnectionStatus(connected = false) {
    const statusEl = document.getElementById('connection-status');
    if (statusEl) {
        if (connected) {
            statusEl.className = 'connection-status connected';
            statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
        } else {
            statusEl.className = 'connection-status disconnected';
            statusEl.innerHTML = enableWebSocket ? '🔴 ไม่ได้เชื่อมต่อ' : '⚙️ โหมดพื้นฐาน';
        }
    }
}

// Utility functions
window.stretcherUtils = {
    playNotificationSound: function() {
        const audio = document.getElementById('notification-sound');
        if (audio) {
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    },
    
    showToast: function(title, text, icon = 'info', timer = 3000) {
        if (window.Swal) {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                timer: timer,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', updateConnectionStatus);

console.log('✅ App.js setup complete');