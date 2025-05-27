// resources/js/app.js
console.log('🚀 App.js starting...');

// Import WebSocket dependencies
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Setup Pusher
window.Pusher = Pusher;

// ตรวจสอบ WebSocket config
const wsConfig = {
    enabled: !!import.meta.env.VITE_REVERB_APP_KEY,
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',
    port: import.meta.env.VITE_REVERB_PORT || 8080,
    scheme: import.meta.env.VITE_REVERB_SCHEME || 'http'
};

console.log('🔧 WebSocket Configuration:', wsConfig);
console.log('🔍 Environment Variables:', {
    VITE_REVERB_APP_KEY: import.meta.env.VITE_REVERB_APP_KEY,
    VITE_REVERB_HOST: import.meta.env.VITE_REVERB_HOST,
    VITE_REVERB_PORT: import.meta.env.VITE_REVERB_PORT,
    VITE_REVERB_SCHEME: import.meta.env.VITE_REVERB_SCHEME
});

// Setup WebSocket if enabled
if (wsConfig.enabled) {
    try {
        console.log('🔄 Setting up WebSocket...');
        
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: wsConfig.key,
            wsHost: wsConfig.host,
            wsPort: wsConfig.port,
            wssPort: wsConfig.port,
            forceTLS: false, // ปิด TLS สำหรับ local development
            enabledTransports: ['ws'], // ใช้แค่ ws ไม่ใช้ wss
            enableLogging: true,
            disableStats: true,
            encrypted: false, // ไม่เข้ารหัส
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            },
        });
        
        console.log('✅ Echo instance created');
        
        // Setup connection event handlers
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;
            
            pusher.connection.bind('connected', () => {
                console.log('✅ WebSocket connected');
                updateConnectionStatus(true);
            });
            
            pusher.connection.bind('disconnected', () => {
                console.log('🔴 WebSocket disconnected');
                updateConnectionStatus(false);
            });
            
            pusher.connection.bind('connecting', () => {
                console.log('🟡 WebSocket connecting...');
                updateConnectionStatus('connecting');
            });
            
            pusher.connection.bind('error', (error) => {
                console.error('❌ WebSocket error:', error);
                updateConnectionStatus(false);
            });

            // Log current state
            console.log('🔍 Initial connection state:', pusher.connection.state);
        }

        // Test the stretcher channel manually (for debugging)
        setupStretcherChannel();
        
        console.log('✅ Echo setup complete');
        
    } catch (error) {
        console.error('❌ Failed to setup WebSocket:', error);
        updateConnectionStatus(false);
    }
} else {
    console.log('⚙️ WebSocket disabled (no VITE_REVERB_APP_KEY)');
    updateConnectionStatus(false);
}

// Setup stretcher channel for testing/debugging
function setupStretcherChannel() {
    if (!window.Echo) {
        console.error('❌ Echo not available for channel setup');
        return;
    }

    try {
        console.log('📡 Setting up stretcher channel...');
        
        const channel = window.Echo.channel('stretcher-updates');
        
        channel.listen('StretcherUpdated', (e) => {
            console.log('📨 StretcherUpdated received:', e);
            
            // Play notification sound
            playNotificationSound();
            
            // Show notification based on action
            handleStretcherNotification(e);
            
            // Trigger page-specific refresh functions
            if (typeof window.refreshDashboard === 'function') {
                window.refreshDashboard();
            }
            
            if (typeof window.refreshPublicView === 'function') {
                window.refreshPublicView();
            }
            
            // Generic refresh for any page that has loadData function
            if (typeof window.loadData === 'function') {
                window.loadData();
            }
        });

        channel.error((error) => {
            console.error('❌ Channel error:', error);
        });
        
        // Store for debugging
        window.stretcherChannel = channel;
        
        console.log('✅ Stretcher channel setup complete');
        
    } catch (error) {
        console.error('❌ Failed to setup stretcher channel:', error);
    }
}

// Handle different types of stretcher notifications
function handleStretcherNotification(event) {
    const { action, stretcher, team_name } = event;
    
    console.log('🔔 Handling notification:', action);
    
    // Update last updated time
    const lastUpdatedEl = document.getElementById('last-updated');
    if (lastUpdatedEl) {
        lastUpdatedEl.textContent = new Date().toLocaleTimeString();
    }
    
    // Update realtime counter
    const realtimeCountEl = document.getElementById('realtime-count');
    if (realtimeCountEl) {
        let currentCount = parseInt(realtimeCountEl.textContent) || 0;
        realtimeCountEl.textContent = currentCount + 1;
    }
    
    // Highlight specific row if it exists
    if (stretcher.stretcher_register_id) {
        const row = document.querySelector(`[data-id="${stretcher.stretcher_register_id}"]`);
        if (row) {
            row.classList.add('highlight');
            setTimeout(() => row.classList.remove('highlight'), 3000);
        }
    }
    
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
            console.log('❓ Unknown action:', action);
    }
}

function showNewRequestNotification(stretcher) {
    if (window.Swal && stretcher) {
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
            position: 'top-end',
            toast: false,
            showClass: {
                popup: 'animate__animated animate__bounceIn'
            }
        });
    }
}

function showAcceptedNotification(stretcher, teamName) {
    showToast(
        '✅ มีคนรับงานแล้ว',
        `${teamName} รับงาน HN: ${stretcher.hn}`,
        'success'
    );
}

function showSentNotification(stretcher) {
    showToast(
        '🚗 เริ่มปฏิบัติงาน',
        `HN: ${stretcher.hn} - ไปรับผู้ป่วย`,
        'info'
    );
}

function showCompletedNotification(stretcher) {
    showToast(
        '🎉 งานสำเร็จ!',
        `HN: ${stretcher.hn} - เสร็จสิ้น`,
        'success'
    );
}

// Utility functions
function playNotificationSound() {
    const audio = document.getElementById('notification-sound');
    if (audio) {
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
}

function showToast(title, text, icon = 'info', timer = 4000) {
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
}

// Update connection status indicator
function updateConnectionStatus(status = false) {
    const statusEl = document.getElementById('connection-status');
    if (statusEl) {
        if (status === true) {
            statusEl.className = 'connection-status connected';
            statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
        } else if (status === false) {
            statusEl.className = 'connection-status disconnected';
            statusEl.innerHTML = wsConfig.enabled ? '🔴 ไม่ได้เชื่อมต่อ' : '⚙️ โหมดพื้นฐาน';
        } else if (status === 'connecting') {
            statusEl.className = 'connection-status connecting';
            statusEl.innerHTML = '🟡 กำลังเชื่อมต่อ...';
        }
    }
}

// Global utility object
window.stretcherUtils = {
    playNotificationSound,
    showToast,
    formatTime: function(datetime) {
        return new Date(datetime).toLocaleTimeString('th-TH', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
};

// Debug functions for console
window.debugWebSocket = function() {
    console.log('=== WebSocket Debug Info ===');
    console.log('Config:', wsConfig);
    console.log('Echo:', window.Echo);
    
    if (window.Echo && window.Echo.connector) {
        console.log('Connector:', window.Echo.connector);
        if (window.Echo.connector.pusher) {
            console.log('Pusher state:', window.Echo.connector.pusher.connection.state);
            console.log('Pusher config:', window.Echo.connector.pusher.config);
        }
    }
    
    console.log('Stretcher channel:', window.stretcherChannel);
};

window.testBroadcast = function() {
    console.log('🧪 Testing broadcast...');
    fetch('/debug/test-broadcast', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Test broadcast result:', data);
        if (data.success) {
            showToast('✅ Test Success', 'Test broadcast sent', 'success');
        } else {
            showToast('❌ Test Failed', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Test broadcast error:', error);
        showToast('❌ Test Error', error.message, 'error');
    });
};

// Test with fake data
window.testWithFakeData = function() {
    console.log('🧪 Testing with fake data...');
    
    const fakeStretcherData = {
        action: 'new',
        stretcher: {
            stretcher_register_id: 99999,
            hn: 'FAKE001',
            pname: 'นาย',
            fname: 'ทดสอบ',
            lname: 'ระบบ',
            department: 'IT Test Department',
            department2: 'Emergency Room',
            stretcher_priority_name: 'ด่วน',
            stretcher_work_status_id: 1,
            stretcher_work_status_name: 'รอรับงาน'
        },
        team_name: null,
        metadata: { source: 'fake_test' }
    };
    
    // Simulate receiving WebSocket event
    handleStretcherNotification(fakeStretcherData);
    
    console.log('🎉 Fake data test completed');
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏗️ DOM loaded, initializing...');
    updateConnectionStatus();
    
    // Global connection status helper
    window.connectionStatus = {
        connected: false,
        updateStatus: updateConnectionStatus
    };
    
    console.log('💡 Use debugWebSocket(), testBroadcast(), or testWithFakeData() in console for testing');
});

console.log('✅ App.js setup complete (without Livewire)');