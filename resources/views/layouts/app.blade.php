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
    @livewireStyles
    
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
        }
        
        .connected {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }
        
        .disconnected {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Connection Status -->
    <div id="connection-status" class="connection-status disconnected">
        🔴 กำลังเชื่อมต่อ...
    </div>

    <!-- Audio for notifications -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('storage/sounds/notification.wav') }}" type="audio/wav">
    </audio>

    @include('sweetalert::alert')
    
    <main>
        @yield('content')
    </main>
    
    <footer class="footer d-flex align-items-center shadow-lg bg-white text-dark">
        <div class="container text-center">
            <small>Copyright &copy; {{ date('Y') }} Nonghan Hospital, All rights reserved.</small>
            <br>
            <small>Powered by Laravel Reverb | Made with ❤️ by IT Department</small>
        </div>
    </footer>
    
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <script>
        // Global WebSocket connection status
        window.connectionStatus = {
            connected: false,
            updateStatus: function(status) {
                this.connected = status;
                const statusEl = document.getElementById('connection-status');
                if (statusEl) {
                    if (status) {
                        statusEl.className = 'connection-status connected';
                        statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
                    } else {
                        statusEl.className = 'connection-status disconnected';
                        statusEl.innerHTML = '🔴 การเชื่อมต่อขาดหาย';
                    }
                }
            }
        };

        // WebSocket connection events
        if (window.Echo) {
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('🟢 Stretcher WebSocket connected');
                window.connectionStatus.updateStatus(true);
            });

            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('🔴 Stretcher WebSocket disconnected');
                window.connectionStatus.updateStatus(false);
            });

            window.Echo.connector.pusher.connection.bind('error', (error) => {
                console.error('❌ WebSocket error:', error);
                window.connectionStatus.updateStatus(false);
            });
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
            },
            
            formatTime: function(datetime) {
                return new Date(datetime).toLocaleTimeString('th-TH', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        };
    </script>
    
    @stack('scripts')
</body>
</html>