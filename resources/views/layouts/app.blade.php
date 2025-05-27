<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ศูนย์เปล - โรงพยาบาลหนองหาน')</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=kanit:400,500,600" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('storage/img/stretcher.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    @vite(['resources/js/app.js'])
    
    <style>
        * {
            padding: 0;
            margin: 0;
        }
        
        html {
            position: relative;
            min-height: 100%;
        }
        
        body {
            margin-bottom: 100px;
            font-family: 'Kanit', sans-serif;
            background-color: #f5f5f9;
        }
        
        .footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100px;
        }
        
        .stretcher-card {
            background-color: #f3f3f3;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .stretcher-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stretcher-card.highlight {
            animation: highlightCard 2s ease-in-out;
        }
        
        @keyframes highlightCard {
            0% { transform: scale(1); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            50% { transform: scale(1.02); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3); }
            100% { transform: scale(1); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        }
        
        .priority-urgent {
            border-left: 4px solid #dc3545 !important;
        }
        
        .priority-normal {
            border-left: 4px solid #28a745 !important;
        }
        
        .status-new {
            background: linear-gradient(45deg, #6c757d, #8b8b8b);
        }
        
        .status-accepted {
            background: linear-gradient(45deg, #ffc107, #ffce33);
        }
        
        .status-sent {
            background: linear-gradient(45deg, #17a2b8, #1cbbd3);
        }
        
        .status-completed {
            background: linear-gradient(45deg, #28a745, #34ce57);
        }
        
        .notification-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .connection-status {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .connected {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }
        
        .disconnected {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }

        .connecting {
            background: rgba(255, 193, 7, 0.9);
            color: black;
        }
        
        /* Optimistic updates and real-time effects */
        .stretcher-card.processing {
            opacity: 0.8;
            transform: scale(0.98);
            border: 2px solid #ffc107;
            animation: processing 1s ease-in-out infinite alternate;
        }
        
        .stretcher-card.confirmed {
            border: 2px solid #28a745;
            animation: confirmed 0.5s ease-in-out;
        }
        
        .stretcher-card.error {
            border: 2px solid #dc3545;
            animation: error 0.5s ease-in-out;
        }
        
        @keyframes processing {
            0% { box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3); }
            100% { box-shadow: 0 8px 25px rgba(255, 193, 7, 0.6); }
        }
        
        @keyframes confirmed {
            0% { transform: scale(1); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
            50% { transform: scale(1.02); box-shadow: 0 8px 25px rgba(40, 167, 69, 0.6); }
            100% { transform: scale(1); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        }
        
        @keyframes error {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
            100% { transform: translateX(0); }
        }
        
        /* Real-time update indicators */
        .realtime-indicator {
            position: fixed;
            top: 70px;
            right: 20px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .realtime-indicator.show {
            transform: translateX(0);
        }
        
        .realtime-indicator.error {
            background: rgba(220, 53, 69, 0.9);
        }
        
        /* Button loading states enhancement */
        .action-btn[disabled] {
            position: relative;
            overflow: hidden;
        }
        
        .action-btn[disabled]::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Toast notifications enhancement */
        .swal2-toast {
            font-family: 'Kanit', sans-serif;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .connection-status {
                font-size: 10px;
                padding: 6px 10px;
            }
            
            body {
                margin-bottom: 80px;
            }
            
            .footer {
                height: 80px;
            }
        }
        
        /* Debug panel styles */
        .debug-panel {
            position: fixed;
            bottom: 120px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 12px;
            max-width: 300px;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .debug-panel.collapsed {
            transform: translateX(calc(100% - 40px));
        }
        
        .debug-toggle {
            position: absolute;
            left: -30px;
            top: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            padding: 5px 8px;
            border-radius: 5px 0 0 5px;
            cursor: pointer;
        }
        
        /* Offline indicator */
        .offline-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 20px;
            border-radius: 10px;
            z-index: 2000;
            display: none;
        }
        
        body.offline .offline-indicator {
            display: block;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Connection Status -->
    <div id="connection-status" class="connection-status connecting">
        🟡 กำลังเชื่อมต่อ...
    </div>

    <!-- Offline Indicator -->
    <div class="offline-indicator" id="offline-indicator">
        <h5><i class="fas fa-wifi-slash me-2"></i>ไม่มีการเชื่อมต่อ</h5>
        <p class="mb-0">กรุณาตรวจสอบการเชื่อมต่ออินเทอร์เน็ต</p>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
        <div class="text-center">
            <div class="loading-spinner"></div>
            <p class="mt-3 text-muted">กำลังโหลด...</p>
        </div>
    </div>

    <!-- Audio for notifications -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('storage/sounds/notification.wav') }}" type="audio/wav">
        <source src="{{ asset('storage/sounds/notification.ogg') }}" type="audio/ogg">
    </audio>

    <!-- Debug Panel (only in development) -->
    @if(config('app.debug'))
    <div id="debug-panel" class="debug-panel collapsed">
        <button class="debug-toggle" onclick="toggleDebugPanel()">🐛</button>
        <div class="debug-content">
            <h6>🔧 Debug Info</h6>
            <div class="mb-2">
                <strong>Connection:</strong> <span id="debug-connection">Unknown</span>
            </div>
            <div class="mb-2">
                <strong>Last Update:</strong> <span id="debug-last-update">Never</span>
            </div>
            <div class="mb-2">
                <strong>Events:</strong> <span id="debug-event-count">0</span>
            </div>
            <div class="mb-2">
                <button class="btn btn-sm btn-outline-light" onclick="testBroadcast()">Test Broadcast</button>
                <button class="btn btn-sm btn-outline-light" onclick="debugWebSocket()">Debug WS</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 1050; max-width: 400px;">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed" 
             style="top: 80px; right: 20px; z-index: 1050; max-width: 400px;">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <main>
        @yield('content')
    </main>
    
    <footer class="footer d-flex align-items-center shadow-lg bg-white text-dark">
        <div class="container text-center">
            <small>Copyright &copy; {{ date('Y') }} Nonghan Hospital, All rights reserved.</small>
            <br>
            <small>
                Powered by Laravel + WebSocket
                @if(config('app.debug'))
                    | <span class="text-muted">Debug Mode</span>
                @endif
                | Made with ❤️ by IT Department
            </small>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    
    <script>
        // Global variables
        window.isOnline = navigator.onLine;
        window.eventCount = 0;
        
        // Network status monitoring
        window.addEventListener('online', function() {
            window.isOnline = true;
            document.body.classList.remove('offline');
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast('🟢 กลับมาออนไลน์', 'การเชื่อมต่อกลับมาแล้ว', 'success');
            }
        });

        window.addEventListener('offline', function() {
            window.isOnline = false;
            document.body.classList.add('offline');
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast('🔴 ขาดการเชื่อมต่อ', 'ไม่มีการเชื่อมต่ออินเทอร์เน็ต', 'error');
            }
        });

        // Global loading functions
        window.showLoading = function(message = 'กำลังโหลด...') {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.querySelector('p').textContent = message;
                overlay.style.display = 'flex';
            }
        };

        window.hideLoading = function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        };

        // Debug panel functions
        @if(config('app.debug'))
        window.toggleDebugPanel = function() {
            const panel = document.getElementById('debug-panel');
            panel.classList.toggle('collapsed');
        };

        window.updateDebugInfo = function() {
            const connectionEl = document.getElementById('debug-connection');
            const lastUpdateEl = document.getElementById('debug-last-update');
            const eventCountEl = document.getElementById('debug-event-count');
            
            if (connectionEl) {
                connectionEl.textContent = window.Echo && window.Echo.connector?.pusher?.connection?.state || 'Unknown';
            }
            
            if (lastUpdateEl) {
                lastUpdateEl.textContent = new Date().toLocaleTimeString('th-TH');
            }
            
            if (eventCountEl) {
                eventCountEl.textContent = window.eventCount;
            }
        };

        // Update debug info every 5 seconds
        setInterval(window.updateDebugInfo, 5000);
        @endif

        // Global connection status helper
        window.connectionStatus = {
            connected: false,
            updateStatus: function(status, message = null) {
                this.connected = status;
                const statusEl = document.getElementById('connection-status');
                if (statusEl) {
                    if (status === true) {
                        statusEl.className = 'connection-status connected';
                        statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
                    } else if (status === false) {
                        statusEl.className = 'connection-status disconnected';
                        statusEl.innerHTML = '🔴 การเชื่อมต่อขาดหาย';
                    } else {
                        statusEl.className = 'connection-status connecting';
                        statusEl.innerHTML = message || '🟡 กำลังเชื่อมต่อ...';
                    }
                }
                
                @if(config('app.debug'))
                window.updateDebugInfo();
                @endif
            }
        };

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
            },
            
            formatTime: function(datetime) {
                return new Date(datetime).toLocaleTimeString('th-TH', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            
            showConfirm: function(title, text, confirmText = 'ยืนยัน', cancelText = 'ยกเลิก') {
                return Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545'
                });
            }
        };

        // Wait for Echo to be ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking Echo...');
            
            // Auto-dismiss flash messages after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(alert => {
                    const closeBtn = alert.querySelector('.btn-close');
                    if (closeBtn) closeBtn.click();
                });
            }, 5000);
            
            // Function to setup Echo listeners
            function setupEchoListeners() {
                if (!window.Echo) {
                    console.error('❌ Echo not available');
                    window.connectionStatus.updateStatus(false, '🔴 Echo ไม่พร้อม');
                    return false;
                }

                console.log('✅ Echo available, setting up listeners...');
                
                try {
                    // Monitor connection state
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        const pusher = window.Echo.connector.pusher;
                        
                        pusher.connection.bind('connected', () => {
                            console.log('🟢 WebSocket connected');
                            window.connectionStatus.updateStatus(true);
                        });

                        pusher.connection.bind('disconnected', () => {
                            console.log('🔴 WebSocket disconnected');
                            window.connectionStatus.updateStatus(false);
                        });

                        pusher.connection.bind('error', (error) => {
                            console.error('❌ WebSocket error:', error);
                            window.connectionStatus.updateStatus(false);
                        });

                        pusher.connection.bind('connecting', () => {
                            console.log('🟡 WebSocket connecting...');
                            window.connectionStatus.updateStatus('connecting');
                        });

                        // Check current state
                        const state = pusher.connection.state;
                        console.log('Current connection state:', state);
                        
                        if (state === 'connected') {
                            window.connectionStatus.updateStatus(true);
                        } else if (state === 'disconnected') {
                            window.connectionStatus.updateStatus(false);
                        } else {
                            window.connectionStatus.updateStatus('connecting');
                        }
                    }

                    // Setup stretcher channel listener
                    const channel = window.Echo.channel('stretcher-updates');
                    
                    channel.listen('StretcherUpdated', (e) => {
                        console.log('📨 Stretcher update received:', e);
                        window.eventCount++;
                        
                        @if(config('app.debug'))
                        window.updateDebugInfo();
                        @endif
                        
                        // Trigger page-specific refresh functions
                        if (typeof window.loadDashboardData === 'function') {
                            window.loadDashboardData(false);
                        }
                        
                        if (typeof window.loadPublicViewData === 'function') {
                            window.loadPublicViewData(false);
                        }
                    });

                    console.log('✅ Echo listeners setup complete');
                    return true;
                    
                } catch (error) {
                    console.error('❌ Error setting up Echo listeners:', error);
                    window.connectionStatus.updateStatus(false, '🔴 Setup ล้มเหลว');
                    return false;
                }
            }

            // Try to setup Echo listeners immediately
            if (!setupEchoListeners()) {
                // If failed, retry after a short delay
                setTimeout(() => {
                    console.log('Retrying Echo setup...');
                    setupEchoListeners();
                }, 1000);
            }
        });

        // Debug function for console
        window.debugConnection = function() {
            console.log('=== Connection Debug Info ===');
            console.log('Echo:', window.Echo);
            console.log('Online:', window.isOnline);
            console.log('Event count:', window.eventCount);
            
            if (window.Echo && window.Echo.connector) {
                console.log('Connector:', window.Echo.connector);
                if (window.Echo.connector.pusher) {
                    console.log('Pusher state:', window.Echo.connector.pusher.connection.state);
                    console.log('Pusher options:', window.Echo.connector.pusher.config);
                }
            }
            
            console.log('Connection status:', window.connectionStatus.connected);
        };

        console.log('🚀 Stretcher system initialized. Use debugConnection() for troubleshooting.');
    </script>
    
    @stack('scripts')
</body>
</html>