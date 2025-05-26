<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WebSocket Debug - Stretcher System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .debug-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .debug-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .status-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 10px;
            animation: pulse 2s infinite;
        }
        
        .status-connected { background: #28a745; }
        .status-disconnected { background: #dc3545; }
        .status-connecting { background: #ffc107; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .log-container {
            background: #1e1e1e;
            color: #f8f9fa;
            border-radius: 10px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            height: 300px;
            overflow-y: auto;
            padding: 15px;
        }
        
        .log-entry {
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .log-timestamp { color: #6c757d; font-size: 12px; }
        .log-success { color: #28a745; }
        .log-error { color: #dc3545; }
        .log-warning { color: #ffc107; }
        .log-info { color: #17a2b8; }
        
        .btn-test {
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .config-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            height: 200px;
            overflow-y: auto;
        }
        
        .stats-box {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .test-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .test-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .test-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <!-- Header -->
        <div class="text-center text-white mb-4">
            <h1 class="display-4 fw-bold">
                <i class="fas fa-satellite-dish"></i>
                WebSocket Debug Center
            </h1>
            <p class="lead">ระบบทดสอบและแก้ไข WebSocket สำหรับ Stretcher System</p>
        </div>

        <!-- Connection Status -->
        <div class="debug-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-wifi"></i>
                    Connection Status
                </h5>
                <div class="d-flex align-items-center mb-3">
                    <span class="status-indicator status-disconnected" id="status-indicator"></span>
                    <span id="connection-status">🔴 ตรวจสอบการเชื่อมต่อ...</span>
                </div>
                <div id="connection-details" class="text-muted">
                    กำลังโหลด...
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-box">
                    <span class="stats-number" id="events-count">0</span>
                    <small>Events Received</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <span class="stats-number" id="broadcasts-count">0</span>
                    <small>Broadcasts Sent</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <span class="stats-number" id="errors-count">0</span>
                    <small>Errors</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box">
                    <span class="stats-number" id="uptime">00:00</span>
                    <small>Uptime</small>
                </div>
            </div>
        </div>

        <!-- Quick Tests -->
        <div class="debug-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-flask"></i>
                    Quick Tests
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>WebSocket Tests</h6>
                        <button class="btn btn-success btn-test" onclick="testBasicConnection()">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                        <button class="btn btn-primary btn-test" onclick="testBroadcast()">
                            <i class="fas fa-broadcast-tower"></i> Test Broadcast
                        </button>
                        <button class="btn btn-info btn-test" onclick="testWebSocketDebug()">
                            <i class="fas fa-search"></i> Debug WebSocket
                        </button>
                    </div>
                    <div class="col-md-6">
                        <h6>System Tests</h6>
                        <button class="btn btn-warning btn-test" onclick="testLivewire()">
                            <i class="fab fa-laravel"></i> Test Livewire
                        </button>
                        <button class="btn btn-secondary btn-test" onclick="testEnvironment()">
                            <i class="fas fa-cog"></i> Test Environment
                        </button>
                        <button class="btn btn-danger btn-test" onclick="clearAllData()">
                            <i class="fas fa-trash"></i> Clear Data
                        </button>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div id="test-results" class="mt-4"></div>
            </div>
        </div>

        <!-- Logs -->
        <div class="row">
            <div class="col-md-6">
                <div class="debug-card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                        <h5 class="mb-0">
                            <i class="fas fa-terminal"></i>
                            System Logs
                        </h5>
                        <button class="btn btn-sm btn-outline-light" onclick="clearLogs('system')">
                            Clear
                        </button>
                    </div>
                    <div class="log-container" id="system-logs">
                        <div class="log-entry log-info">
                            <span class="log-timestamp">[Ready]</span> System ready for testing...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="debug-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between">
                        <h5 class="mb-0">
                            <i class="fas fa-satellite-dish"></i>
                            WebSocket Events
                        </h5>
                        <button class="btn btn-sm btn-outline-light" onclick="clearLogs('websocket')">
                            Clear
                        </button>
                    </div>
                    <div class="log-container" id="websocket-logs">
                        <div class="log-entry log-info">
                            <span class="log-timestamp">[Ready]</span> Waiting for WebSocket events...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="row">
            <div class="col-md-6">
                <div class="debug-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs"></i>
                            Environment Config
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="config-box" id="environment-config">
                            Loading environment configuration...
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="debug-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-code"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="config-box" id="system-info">
                            Loading system information...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Include Laravel assets -->
    @vite(['resources/js/app.js'])

    <script>
        // Define all functions first before any other code
        window.testBasicConnection = function() {
            addLog('system', 'Testing basic connection...', 'info');
            
            try {
                if (typeof window.Echo === 'undefined') {
                    throw new Error('Echo is not loaded');
                }
                
                if (!window.Echo.connector) {
                    throw new Error('Echo connector is not available');
                }
                
                if (!window.Echo.connector.pusher) {
                    throw new Error('Pusher instance is not available');
                }
                
                const state = window.Echo.connector.pusher.connection.state;
                updateConnectionStatus(state, `Connection test completed - State: ${state}`);
                
                if (state === 'connected') {
                    showTestResult('✅ WebSocket connection is working!', 'success');
                    addLog('system', 'Connection test passed', 'success');
                } else {
                    showTestResult(`⚠️ WebSocket state: ${state}`, 'warning');
                    addLog('system', `Connection test warning: state is ${state}`, 'warning');
                    
                    // Try to connect
                    window.Echo.connector.pusher.connect();
                    addLog('system', 'Attempting to connect...', 'info');
                }
                
            } catch (error) {
                showTestResult('❌ Connection test failed: ' + error.message, 'error');
                addLog('system', 'Connection test failed: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            }
        };

        window.testBroadcast = function() {
            addLog('system', 'Testing broadcast...', 'info');
            broadcastsSent++;
            updateCounter('broadcasts-count', broadcastsSent);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                showTestResult('❌ CSRF token not found', 'error');
                addLog('system', 'CSRF token not found', 'error');
                return;
            }

            fetch('/debug/test-broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                },
                body: JSON.stringify({ test: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showTestResult('✅ Broadcast sent successfully!', 'success');
                    addLog('system', 'Broadcast test successful', 'success');
                    addLog('websocket', 'Test broadcast sent: ' + JSON.stringify(data), 'info');
                } else {
                    showTestResult('❌ Broadcast failed: ' + data.message, 'error');
                    addLog('system', 'Broadcast test failed: ' + data.message, 'error');
                    errorsCount++;
                    updateCounter('errors-count', errorsCount);
                }
            })
            .catch(error => {
                showTestResult('❌ Broadcast request failed: ' + error.message, 'error');
                addLog('system', 'Broadcast request failed: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            });
        };

        window.testWebSocketDebug = function() {
            addLog('system', 'Running WebSocket debug...', 'info');
            
            try {
                if (typeof window.debugWebSocket === 'function') {
                    window.debugWebSocket();
                    showTestResult('✅ WebSocket debug completed - check console', 'success');
                    addLog('system', 'WebSocket debug function executed', 'success');
                } else {
                    showTestResult('⚠️ debugWebSocket function not available', 'warning');
                    addLog('system', 'debugWebSocket function not found', 'warning');
                    
                    // Manual debug
                    if (window.Echo) {
                        const state = window.Echo.connector?.pusher?.connection?.state || 'unknown';
                        addLog('system', `Manual debug - Echo state: ${state}`, 'info');
                        showTestResult(`ℹ️ Manual debug - Echo state: ${state}`, 'info');
                    }
                }
            } catch (error) {
                showTestResult('❌ WebSocket debug failed: ' + error.message, 'error');
                addLog('system', 'WebSocket debug failed: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            }
        };

        window.testLivewire = function() {
            addLog('system', 'Testing Livewire...', 'info');
            
            try {
                if (typeof window.Livewire === 'undefined') {
                    throw new Error('Livewire is not loaded');
                }
                
                // Test basic Livewire functionality
                const components = window.Livewire.all();
                showTestResult(`✅ Livewire is working - Found ${components.length} components`, 'success');
                addLog('system', `Livewire test passed - ${components.length} components found`, 'success');
                
                // Test refresh function if available
                if (typeof window.testLivewireRefresh === 'function') {
                    window.testLivewireRefresh();
                    addLog('system', 'Livewire refresh test executed', 'info');
                } else {
                    // Manual Livewire test
                    window.Livewire.dispatch('refreshData');
                    addLog('system', 'Manual Livewire dispatch executed', 'info');
                }
                
            } catch (error) {
                showTestResult('❌ Livewire test failed: ' + error.message, 'error');
                addLog('system', 'Livewire test failed: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            }
        };

        window.testEnvironment = function() {
            addLog('system', 'Testing environment...', 'info');
            
            const tests = [
                { name: 'CSRF Token', test: () => !!document.querySelector('meta[name="csrf-token"]') },
                { name: 'WebSocket Support', test: () => 'WebSocket' in window },
                { name: 'Echo Library', test: () => typeof window.Echo !== 'undefined' },
                { name: 'Livewire Library', test: () => typeof window.Livewire !== 'undefined' },
                { name: 'Bootstrap CSS', test: () => !!document.querySelector('link[href*="bootstrap"]') },
                { name: 'Font Awesome', test: () => !!document.querySelector('link[href*="font-awesome"]') }
            ];
            
            let passedTests = 0;
            tests.forEach(test => {
                try {
                    const result = test.test();
                    if (result) {
                        addLog('system', `✅ ${test.name}: PASS`, 'success');
                        passedTests++;
                    } else {
                        addLog('system', `❌ ${test.name}: FAIL`, 'error');
                    }
                } catch (error) {
                    addLog('system', `❌ ${test.name}: ERROR - ${error.message}`, 'error');
                }
            });
            
            showTestResult(`Environment test completed: ${passedTests}/${tests.length} tests passed`, 
                          passedTests === tests.length ? 'success' : passedTests > 0 ? 'warning' : 'error');
            
            // Reload configs
            loadEnvironmentConfig();
            loadSystemInfo();
        };

        window.clearLogs = function(type) {
            const containerId = type === 'system' ? 'system-logs' : 'websocket-logs';
            const container = document.getElementById(containerId);
            container.innerHTML = `
                <div class="log-entry log-info">
                    <span class="log-timestamp">[${new Date().toLocaleTimeString()}]</span> Logs cleared
                </div>
            `;
            addLog('system', `${type} logs cleared`, 'info');
        };

        window.clearAllData = function() {
            // Clear logs
            clearLogs('system');
            clearLogs('websocket');
            
            // Clear test results
            document.getElementById('test-results').innerHTML = '';
            
            // Reset counters
            eventsReceived = 0;
            broadcastsSent = 0;
            errorsCount = 0;
            startTime = Date.now();
            
            updateCounter('events-count', 0);
            updateCounter('broadcasts-count', 0);
            updateCounter('errors-count', 0);
            
            showTestResult('🧹 All data cleared', 'success');
            addLog('system', 'All data cleared and counters reset', 'info');
        };

        // Global variables
        let eventsReceived = 0;
        let broadcastsSent = 0;
        let errorsCount = 0;
        let startTime = Date.now();

        // Utility functions
        function startUptimeCounter() {
            setInterval(() => {
                const uptime = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(uptime / 60);
                const seconds = uptime % 60;
                document.getElementById('uptime').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }

        function addLog(type, message, level = 'info') {
            const containerId = type === 'system' ? 'system-logs' : 'websocket-logs';
            const container = document.getElementById(containerId);
            
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${level}`;
            logEntry.innerHTML = `
                <span class="log-timestamp">[${timestamp}]</span> ${message}
            `;
            
            container.appendChild(logEntry);
            container.scrollTop = container.scrollHeight;
            
            // Keep only last 50 entries
            while (container.children.length > 50) {
                container.removeChild(container.firstChild);
            }

            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function showTestResult(message, type = 'success') {
            const resultsContainer = document.getElementById('test-results');
            const resultDiv = document.createElement('div');
            resultDiv.className = `test-result test-${type}`;
            resultDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            resultsContainer.appendChild(resultDiv);
            
            // Auto remove after 10 seconds
            setTimeout(() => {
                if (resultDiv.parentNode) {
                    resultDiv.parentNode.removeChild(resultDiv);
                }
            }, 10000);
        }

        function updateCounter(counterId, value) {
            const element = document.getElementById(counterId);
            if (element) {
                element.textContent = value;
                // Add animation effect
                element.style.transform = 'scale(1.2)';
                setTimeout(() => element.style.transform = 'scale(1)', 200);
            }
        }

        function updateConnectionStatus(status, details = '') {
            const statusEl = document.getElementById('connection-status');
            const indicatorEl = document.getElementById('status-indicator');
            const detailsEl = document.getElementById('connection-details');
            
            if (status === 'connected') {
                statusEl.innerHTML = '🟢 เชื่อมต่อแล้ว';
                indicatorEl.className = 'status-indicator status-connected';
            } else if (status === 'connecting') {
                statusEl.innerHTML = '🟡 กำลังเชื่อมต่อ...';
                indicatorEl.className = 'status-indicator status-connecting';
            } else {
                statusEl.innerHTML = '🔴 ไม่ได้เชื่อมต่อ';
                indicatorEl.className = 'status-indicator status-disconnected';
            }
            
            if (details) {
                detailsEl.textContent = details;
            }
        }

        function loadEnvironmentConfig() {
            try {
                const config = {
                    websocket: {
                        key: 'Check console for details',
                        host: 'Check console for details',
                        port: 'Check console for details',
                        scheme: 'Check console for details'
                    },
                    echo: {
                        available: typeof window.Echo !== 'undefined',
                        connected: false
                    },
                    csrf: document.querySelector('meta[name="csrf-token"]') ? 'Available' : 'Missing'
                };

                // Try to get environment variables safely
                try {
                    if (typeof import !== 'undefined' && import.meta && import.meta.env) {
                        config.websocket.key = import.meta.env.VITE_REVERB_APP_KEY || 'Not set';
                        config.websocket.host = import.meta.env.VITE_REVERB_HOST || 'Not set';
                        config.websocket.port = import.meta.env.VITE_REVERB_PORT || 'Not set';
                        config.websocket.scheme = import.meta.env.VITE_REVERB_SCHEME || 'Not set';
                    }
                } catch (e) {
                    config.websocket.note = 'Environment variables not accessible via import.meta';
                }

                if (typeof window.Echo !== 'undefined' && window.Echo.connector) {
                    config.echo.connected = window.Echo.connector.pusher ? 
                        window.Echo.connector.pusher.connection.state === 'connected' : false;
                    config.echo.state = window.Echo.connector.pusher ? 
                        window.Echo.connector.pusher.connection.state : 'Unknown';
                }

                document.getElementById('environment-config').textContent = JSON.stringify(config, null, 2);
                addLog('system', 'Environment config loaded', 'info');
                
            } catch (error) {
                addLog('system', 'Failed to load environment config: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            }
        }

        function loadSystemInfo() {
            try {
                const systemInfo = {
                    browser: {
                        userAgent: navigator.userAgent,
                        webSocketSupport: 'WebSocket' in window,
                        language: navigator.language,
                        platform: navigator.platform
                    },
                    laravel: {
                        livewire: typeof window.Livewire !== 'undefined',
                        echo: typeof window.Echo !== 'undefined'
                    },
                    functions: {
                        testBasicConnection: typeof window.testBasicConnection === 'function',
                        testBroadcast: typeof window.testBroadcast === 'function',
                        testWebSocketDebug: typeof window.testWebSocketDebug === 'function',
                        testLivewire: typeof window.testLivewire === 'function',
                        testEnvironment: typeof window.testEnvironment === 'function',
                        clearAllData: typeof window.clearAllData === 'function'
                    },
                    timestamp: new Date().toISOString(),
                    url: window.location.href
                };

                document.getElementById('system-info').textContent = JSON.stringify(systemInfo, null, 2);
                addLog('system', 'System info loaded', 'info');
                
            } catch (error) {
                addLog('system', 'Failed to load system info: ' + error.message, 'error');
                errorsCount++;
                updateCounter('errors-count', errorsCount);
            }
        }

        function checkInitialConnection() {
            addLog('system', 'Checking initial connection...', 'info');
            
            if (typeof window.Echo === 'undefined') {
                updateConnectionStatus('disconnected', 'Echo not loaded');
                addLog('system', 'Echo is not available', 'error');
                return;
            }
            
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const state = window.Echo.connector.pusher.connection.state;
                updateConnectionStatus(state, `WebSocket state: ${state}`);
                addLog('system', `Initial WebSocket state: ${state}`, 'info');
            } else {
                updateConnectionStatus('disconnected', 'Pusher connector not available');
                addLog('system', 'Pusher connector not available', 'warning');
            }
        }

        function setupWebSocketMonitoring() {
            if (typeof window.Echo !== 'undefined' && window.stretcherChannel) {
                try {
                    window.stretcherChannel.listen('StretcherUpdated', (e) => {
                        eventsReceived++;
                        updateCounter('events-count', eventsReceived);
                        addLog('websocket', `Event received: ${e.action} for HN: ${e.stretcher?.hn || 'N/A'}`, 'success');
                    });
                    
                    addLog('system', 'WebSocket monitoring setup complete', 'success');
                } catch (error) {
                    addLog('system', 'Failed to setup WebSocket monitoring: ' + error.message, 'error');
                    errorsCount++;
                    updateCounter('errors-count', errorsCount);
                }
            } else {
                addLog('system', 'WebSocket channel not available for monitoring', 'warning');
                // Retry after a delay
                setTimeout(setupWebSocketMonitoring, 3000);
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            addLog('system', 'Debug page initialized', 'info');
            addLog('system', 'All functions loaded successfully', 'success');
            
            // Start uptime counter
            startUptimeCounter();
            
            // Load initial information
            setTimeout(() => {
                loadEnvironmentConfig();
                loadSystemInfo();
                checkInitialConnection();
            }, 1000);
            
            // Setup WebSocket monitoring if available
            setTimeout(setupWebSocketMonitoring, 2000);
            
            // Test that functions are available
            const functionsTest = [
                'testBasicConnection',
                'testBroadcast', 
                'testWebSocketDebug',
                'testLivewire',
                'testEnvironment',
                'clearAllData'
            ];
            
            functionsTest.forEach(funcName => {
                if (typeof window[funcName] === 'function') {
                    addLog('system', `✅ ${funcName} function ready`, 'success');
                } else {
                    addLog('system', `❌ ${funcName} function not found`, 'error');
                }
            });
        });
    </script>
</body>
</html>