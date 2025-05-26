// resources/js/app.js
/* import './bootstrap';
 */
console.log('🚀 App.js starting...');

// Import Livewire
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Import WebSocket dependencies
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Setup Pusher
window.Pusher = Pusher;

// ตรวจสอบว่าต้องการ WebSocket ไหม
const enableWebSocket = import.meta.env.VITE_REVERB_APP_KEY ? true : false;

console.log('WebSocket config:', {
    enabled: enableWebSocket,
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    port: import.meta.env.VITE_REVERB_PORT || 8080,
    scheme: import.meta.env.VITE_REVERB_SCHEME || 'http'
});

// Setup WebSocket if enabled
if (enableWebSocket) {
    try {
        console.log('🔄 Setting up WebSocket...');
        
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
            wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            enableLogging: true,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            },
        });
        
        console.log('✅ Echo instance created:', window.Echo);
        
        // Connection event handlers
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ WebSocket connected');
            updateConnectionStatus(true);
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('🔴 WebSocket disconnected');
            updateConnectionStatus(false);
        });
        
        window.Echo.connector.pusher.connection.bind('connecting', () => {
            console.log('🟡 WebSocket connecting...');
            updateConnectionStatus('connecting');
        });
        
        window.Echo.connector.pusher.connection.bind('error', (err) => {
            console.error('❌ WebSocket error:', err);
            updateConnectionStatus(false);
        });

        // Livewire จะจัดการ channel subscription เอง
        // ไม่ต้อง setup channel manually ใน JavaScript
        console.log('✅ Echo setup complete, Livewire will handle channels');
        
    } catch (error) {
        console.error('❌ Failed to setup WebSocket:', error);
        updateConnectionStatus(false);
    }
} else {
    console.log('⚙️ WebSocket disabled (no VITE_REVERB_APP_KEY)');
    updateConnectionStatus(false);
}

// Setup stretcher channel globally
function setupStretcherChannel() {
    if (!window.Echo) {
        console.error('Echo not available for channel setup');
        return;
    }

    try {
        console.log('📡 Setting up stretcher channel...');
        
        // ลองทั้ง public และ normal channel
        const channelName = 'stretcher-updates';
        const channel = window.Echo.channel(channelName);
        
        channel.listen('StretcherUpdated', (e) => {
            console.log('📨 Stretcher update received:', e);
            
            // Notify Livewire components
            if (window.Livewire) {
                // Dispatch to all listening components
                window.Livewire.dispatch('stretcher-updated', e);
                
                // Also dispatch the old event name for backward compatibility
                window.Livewire.dispatch('refreshData');
            }
            
            // Handle UI notifications
            handleStretcherNotification(e);
        });

        channel.error((error) => {
            console.error('❌ Stretcher channel error:', error);
        });

        // Store channel reference globally
        window.stretcherChannel = channel;
        
        console.log(`✅ Stretcher channel setup complete: ${channelName}`);
        
    } catch (error) {
        console.error('❌ Failed to setup stretcher channel:', error);
    }
}

// Handle stretcher notifications
function handleStretcherNotification(event) {
    const { action, stretcher, team_name } = event;
    
    switch(action) {
        case 'new':
            showNewRequestNotification(stretcher);
            break;
        case 'accepted':
            showAcceptedNotification(stretcher, team_name);
            break;
        case 'sent':
            showSentNotification(stretcher);
            break;
        case 'completed':
            showCompletedNotification(stretcher);
            break;
        default:
            console.log('Unknown stretcher action:', action);
    }
}

function showNewRequestNotification(stretcher) {
    // Play sound
    if (window.stretcherUtils) {
        window.stretcherUtils.playNotificationSound();
    }
    
    // Show notification if on dashboard page
    if (window.location.pathname === '/' && window.Swal) {
        window.Swal.fire({
            title: '🔔 มีรายการขอเปลใหม่!',
            html: `
                <div class="text-start">
                    <strong>HN:</strong> ${stretcher.hn}<br>
                    <strong>ชื่อ:</strong> ${stretcher.pname}${stretcher.fname} ${stretcher.lname}<br>
                    <strong>จาก:</strong> ${stretcher.department}<br>
                    <strong>ไป:</strong> ${stretcher.department2}<br>
                    <strong>ความเร่งด่วน:</strong> <span class="text-danger">${stretcher.stretcher_priority_name}</span>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'รับทราบ',
            timer: 15000,
            timerProgressBar: true,
            showClass: {
                popup: 'animate__animated animate__bounceIn'
            }
        });
    }
}

function showAcceptedNotification(stretcher, teamName) {
    if (window.stretcherUtils) {
        window.stretcherUtils.showToast(
            '✅ มีคนรับงานแล้ว',
            `${teamName} รับงาน HN: ${stretcher.hn}`,
            'success',
            4000
        );
    }
}

function showSentNotification(stretcher) {
    if (window.stretcherUtils) {
        window.stretcherUtils.showToast(
            '🚗 เริ่มปฏิบัติงาน',
            `HN: ${stretcher.hn} - ไปรับผู้ป่วย`,
            'info'
        );
    }
}

function showCompletedNotification(stretcher) {
    if (window.stretcherUtils) {
        window.stretcherUtils.showToast(
            '🎉 งานสำเร็จ!',
            `HN: ${stretcher.hn} - เสร็จสิ้น`,
            'success'
        );
    }
}

// Update connection status
function updateConnectionStatus(status = false) {
    const statusEl = document.getElementById('connection-status');
    if (statusEl) {
        if (status === true) {
            statusEl.className = 'connection-status connected';
            statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
        } else if (status === false) {
            statusEl.className = 'connection-status disconnected';
            statusEl.innerHTML = enableWebSocket ? '🔴 ไม่ได้เชื่อมต่อ' : '⚙️ โหมดพื้นฐาน';
        } else if (status === 'connecting') {
            statusEl.className = 'connection-status connecting';
            statusEl.innerHTML = '🟡 กำลังเชื่อมต่อ...';
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
            window.Swal.fire({
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
    },
    
    formatTime: function(datetime) {
        return new Date(datetime).toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

// Debug function
window.debugWebSocket = function() {
    console.log('=== WebSocket Debug Info ===');
    console.log('Echo:', window.Echo);
    console.log('Connection enabled:', enableWebSocket);
    
    if (window.Echo && window.Echo.connector) {
        console.log('Connector:', window.Echo.connector);
        if (window.Echo.connector.pusher) {
            console.log('Pusher state:', window.Echo.connector.pusher.connection.state);
            console.log('Pusher config:', window.Echo.connector.pusher.config);
        }
    }
    
    console.log('Stretcher channel:', window.stretcherChannel);
    
    if (window.stretcherChannel) {
        console.log('Testing channel...');
        // You can manually trigger test events here if needed
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏗️ DOM loaded, initializing...');
    updateConnectionStatus();
    
    // Initialize global connection status
    if (typeof window.connectionStatus === 'undefined') {
        window.connectionStatus = {
            connected: false,
            updateStatus: updateConnectionStatus
        };
    }
});

// Start Livewire
Livewire.start();

console.log('✅ App.js setup complete, WebSocket:', enableWebSocket ? 'enabled' : 'disabled');