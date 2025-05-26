<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Debug - Stretcher System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .log { background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; max-height: 300px; overflow-y: auto; }
        button { padding: 10px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 WebSocket Debug - Stretcher System</h1>
        
        <div class="card">
            <h2>Connection Status</h2>
            <div id="connection-status" class="status disconnected">
                🔴 Checking connection...
            </div>
            
            <div class="info-grid">
                <div>
                    <h3>Configuration</h3>
                    <pre id="config-info">Loading...</pre>
                </div>
                <div>
                    <h3>Echo Info</h3>
                    <pre id="echo-info">Loading...</pre>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Actions</h2>
            <button class="btn-primary" onclick="refreshConnection()">🔄 Refresh Connection</button>
            <button class="btn-success" onclick="testChannel()">📡 Test Channel</button>
            <button class="btn-warning" onclick="triggerTestBroadcast()">🧪 Trigger Test Broadcast</button>
            <button class="btn-danger" onclick="clearLogs()">🗑️ Clear Logs</button>
        </div>

        <div class="card">
            <h2>Real-time Logs</h2>
            <div id="logs" class="log">
                <div>System ready for logging...</div>
            </div>
        </div>

        <div class="card">
            <h2>Channel Listeners</h2>
            <div id="listeners-info">
                <p>Loading listener information...</p>
            </div>
        </div>
    </div>

    <script>
        let logContainer = document.getElementById('logs');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const color = {
                'info': '#333',
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107'
            }[type] || '#333';
            
            const logEntry = document.createElement('div');
            logEntry.style.color = color;
            logEntry.style.marginBottom = '5px';
            logEntry.innerHTML = `[${timestamp}] ${message}`;
            
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateConnectionStatus(connected) {
            const statusEl = document.getElementById('connection-status');
            if (connected) {
                statusEl.className = 'status connected';
                statusEl.innerHTML = '🟢 Connected to WebSocket';
            } else {
                statusEl.className = 'status disconnected';
                statusEl.innerHTML = '🔴 Disconnected from WebSocket';
            }
        }

        function updateConfigInfo() {
            const configEl = document.getElementById('config-info');
            const config = {
                'REVERB_HOST': '{{ env("REVERB_HOST", "127.0.0.1") }}',
                'REVERB_PORT': '{{ env("REVERB_PORT", "8080") }}',
                'REVERB_SCHEME': '{{ env("REVERB_SCHEME", "http") }}',
                'REVERB_APP_KEY': '{{ env("REVERB_APP_KEY") ? "SET" : "NOT SET" }}',
                'BROADCAST_DRIVER': '{{ env("BROADCAST_DRIVER") }}'
            };
            configEl.textContent = JSON.stringify(config, null, 2);
        }

        function updateEchoInfo() {
            const echoEl = document.getElementById('echo-info');
            const info = {
                'Echo Available': !!window.Echo,
                'Connection State': window.Echo?.connector?.pusher?.connection?.state || 'N/A',
                'Channels': window.Echo?.connector?.channels ? Object.keys(window.Echo.connector.channels) : [],
                'Stretcher Channel': !!window.stretcherChannel
            };
            echoEl.textContent = JSON.stringify(info, null, 2);
        }

        function refreshConnection() {
            log('🔄 Refreshing connection info...', 'info');
            updateConfigInfo();
            updateEchoInfo();
            
            if (window.Echo?.connector?.pusher) {
                const state = window.Echo.connector.pusher.connection.state;
                log(`Connection state: ${state}`, state === 'connected' ? 'success' : 'warning');
                updateConnectionStatus(state === 'connected');
            }
        }

        function testChannel() {
            log('📡 Testing channel subscription...', 'info');
            
            if (!window.Echo) {
                log('❌ Echo not available', 'error');
                return;
            }

            try {
                // Test creating channel
                const testChannel = window.Echo.channel('test-channel');
                log('✅ Test channel created successfully', 'success');
                
                // Test stretcher channel
                if (window.stretcherChannel) {
                    log('✅ Stretcher channel is available', 'success');
                } else {
                    log('❌ Stretcher channel not found', 'error');
                }
                
            } catch (error) {
                log(`❌ Channel test failed: ${error.message}`, 'error');
            }
        }

        function triggerTestBroadcast() {
            log('🧪 Triggering test broadcast via server...', 'info');
            
            fetch('/debug/test-broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    log('✅ Test broadcast triggered successfully', 'success');
                } else {
                    log(`❌ Test broadcast failed: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                log(`❌ Request failed: ${error.message}`, 'error');
            });
        }

        function clearLogs() {
            logContainer.innerHTML = '<div>Logs cleared...</div>';
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Debug page loaded', 'info');
            
            // Setup connection monitoring
            if (window.Echo?.connector?.pusher) {
                const pusher = window.Echo.connector.pusher;
                
                pusher.connection.bind('connected', () => {
                    log('🟢 WebSocket connected', 'success');
                    updateConnectionStatus(true);
                    refreshConnection();
                });
                
                pusher.connection.bind('disconnected', () => {
                    log('🔴 WebSocket disconnected', 'error');
                    updateConnectionStatus(false);
                    refreshConnection();
                });
                
                pusher.connection.bind('error', (error) => {
                    log(`❌ WebSocket error: ${JSON.stringify(error)}`, 'error');
                    updateConnectionStatus(false);
                });
            }

            // Setup stretcher channel listener
            if (window.Echo) {
                log('🔗 Setting up stretcher channel listener...', 'info');
                
                try {
                    const channel = window.Echo.channel('stretcher-updates');
                    
                    // Listen for the exact event name
                    channel.listen('StretcherUpdated', (e) => {
                        log(`📨 StretcherUpdated event received:`, 'success');
                        log(`   Action: ${e.action}`, 'success');
                        log(`   HN: ${e.stretcher?.hn}`, 'success');
                        log(`   Full data: ${JSON.stringify(e, null, 2)}`, 'info');
                    });
                    
                    // Listen for any events on this channel (fallback)
                    channel.listen('*', (eventName, data) => {
                        log(`📻 Wildcard event - Name: ${eventName}`, 'warning');
                        log(`   Data: ${JSON.stringify(data, null, 2)}`, 'warning');
                    });
                    
                    // Listen for raw pusher events
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        window.Echo.connector.pusher.bind('message', (data) => {
                            if (data.channel === 'stretcher-updates') {
                                log(`🔄 Raw pusher message on stretcher-updates:`, 'info');
                                log(`   ${JSON.stringify(data, null, 2)}`, 'info');
                            }
                        });
                    }
                    
                    channel.error((error) => {
                        log(`❌ Channel error: ${JSON.stringify(error)}`, 'error');
                    });
                    
                    // Test subscription status
                    setTimeout(() => {
                        const channelState = channel.subscription_succeeded;
                        log(`📊 Channel subscription status: ${channelState ? 'SUCCESS' : 'PENDING/FAILED'}`, channelState ? 'success' : 'warning');
                    }, 1000);
                    
                    // Log channel subscription
                    log(`✅ Subscribed to 'stretcher-updates' channel`, 'success');
                    
                } catch (error) {
                    log(`❌ Error setting up channel: ${error.message}`, 'error');
                }
            } else {
                log('❌ Echo not available for channel setup', 'error');
            }

            // Initial refresh
            setTimeout(refreshConnection, 1000);
        });
    </script>
</body>
</html>