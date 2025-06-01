{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'ศูนย์เปล - Dashboard')

@section('content')
    @include('sweetalert::alert')

    <!-- Enhanced Audio System -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('storage/sounds/notification.wav') }}" type="audio/wav">
        <source src="{{ asset('storage/sounds/notification.ogg') }}" type="audio/ogg">
    </audio>

    <!-- Enhanced Header Section -->
  {{--   <header class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="hero-content">
                        <h1 class="hero-title animate__animated animate__fadeInDown">
                            <i class="fas fa-bed me-3"></i>
                            ศูนย์เปล - โรงพยาบาลหนองหาน
                            <span class="realtime-badge ms-3 animate__animated animate__pulse animate__infinite">
                                <i class="fas fa-wifi me-1"></i>Real-time
                            </span>
                        </h1>
                        <p class="hero-subtitle animate__animated animate__fadeInUp animate__delay-1s">
                            ระบบจัดการเปลผู้ป่วยแบบเรียลไทม์ พร้อมการแจ้งเตือนทันที
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="hero-visual animate__animated animate__fadeInRight animate__delay-2s">
                        <div class="status-dashboard">
                            <div class="status-item">
                                <i class="fas fa-heartbeat text-danger"></i>
                                <span>ระบบทำงานปกติ</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-users text-success"></i>
                                <span>{{ session()->has('name') ? 'เข้าสู่ระบบแล้ว' : 'รอเข้าสู่ระบบ' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header> --}}

    <div class="container-fluid px-3 px-md-4">
        @if (!session()->has('name'))
            <script>
                window.location = "{{ route('login.form') }}";
            </script>
        @endif

        <!-- Enhanced Connection Status Bar -->
        <div class="connection-status-bar animate__animated animate__slideInDown">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="connection-info">
                            <div id="connection-indicator" class="connection-indicator status-connecting">
                                <i class="fas fa-wifi connection-icon"></i>
                                <span class="connection-text">กำลังเชื่อมต่อ...</span>
                                <div class="connection-pulse"></div>
                            </div>
                            <small class="connection-details ms-3">
                                อัปเดตล่าสุด: <span id="last-update-time">{{ now()->format('H:i:s') }}</span>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="connection-actions">
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" 
                                    onclick="window.reconnectWebSocket()" title="เชื่อมต่อใหม่">
                                <i class="fas fa-sync-alt me-1"></i>เชื่อมต่อใหม่
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" 
                                    onclick="window.debugLivewire()" title="ตรวจสอบระบบ">
                                <i class="fas fa-bug me-1"></i>Debug
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <livewire:stretcher-manager />
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🚀 Enhanced Dashboard Real-time System Initializing...');

                // ===================================================================
                // 🎯 Enhanced UI State Management
                // ===================================================================
                
                let connectionState = {
                    isConnected: false,
                    reconnectAttempts: 0,
                    maxReconnectAttempts: 5,
                    lastHeartbeat: Date.now()
                };

                // ===================================================================
                // 🎨 Enhanced Visual Effects & Animations
                // ===================================================================

                function updateConnectionStatus(status, details = '') {
                    const indicator = document.getElementById('connection-indicator');
                    const icon = indicator.querySelector('.connection-icon');
                    const text = indicator.querySelector('.connection-text');
                    
                    // Remove all status classes
                    indicator.classList.remove('status-connected', 'status-connecting', 'status-disconnected', 'status-error');
                    
                    const statusConfig = {
                        'connected': {
                            class: 'status-connected',
                            icon: 'fas fa-wifi',
                            text: 'เชื่อมต่อสำเร็จ',
                            color: '#10b981'
                        },
                        'connecting': {
                            class: 'status-connecting', 
                            icon: 'fas fa-spinner fa-spin',
                            text: 'กำลังเชื่อมต่อ...',
                            color: '#f59e0b'
                        },
                        'disconnected': {
                            class: 'status-disconnected',
                            icon: 'fas fa-wifi-slash',
                            text: 'ขาดการเชื่อมต่อ',
                            color: '#ef4444'
                        },
                        'error': {
                            class: 'status-error',
                            icon: 'fas fa-exclamation-triangle',
                            text: 'เกิดข้อผิดพลาด',
                            color: '#dc2626'
                        },
                        'reconnecting': {
                            class: 'status-connecting',
                            icon: 'fas fa-sync fa-spin',
                            text: `กำลังเชื่อมต่อใหม่ (${connectionState.reconnectAttempts}/${connectionState.maxReconnectAttempts})`,
                            color: '#f59e0b'
                        }
                    };

                    const config = statusConfig[status] || statusConfig['error'];
                    
                    // Apply new status
                    indicator.classList.add(config.class);
                    icon.className = config.icon + ' connection-icon';
                    text.textContent = config.text;
                    
                    // Update connection state
                    connectionState.isConnected = (status === 'connected');
                    
                    // Show notification for connection changes
                    if (status === 'connected' && connectionState.reconnectAttempts > 0) {
                        showEnhancedToast('เชื่อมต่อสำเร็จ', 'ระบบพร้อมใช้งานแล้ว', 'success');
                        connectionState.reconnectAttempts = 0;
                    } else if (status === 'disconnected') {
                        showEnhancedToast('ขาดการเชื่อมต่อ', 'กำลังพยายามเชื่อมต่อใหม่...', 'warning');
                    }
                }

                function updateLastUpdateTime() {
                    const timeElement = document.getElementById('last-update-time');
                    if (timeElement) {
                        const now = new Date();
                        timeElement.textContent = now.toLocaleTimeString('th-TH');
                        timeElement.classList.add('animate__animated', 'animate__pulse');
                        setTimeout(() => {
                            timeElement.classList.remove('animate__animated', 'animate__pulse');
                        }, 1000);
                    }
                }

                function showEnhancedToast(title, message, type = 'info', duration = 4000) {
                    if (typeof Swal === 'undefined') return;

                    const iconMap = {
                        'success': 'success',
                        'error': 'error', 
                        'warning': 'warning',
                        'info': 'info'
                    };

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: duration,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__slideInRight animate__faster'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__slideOutRight animate__faster'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });

                    Toast.fire({
                        icon: iconMap[type] || 'info',
                        title: title,
                        text: message,
                        background: '#ffffff',
                        color: '#1e293b'
                    });
                }

                function highlightStretcherItem(stretcherId, color = '#10b981', duration = 4000) {
                    const item = document.getElementById('stretcher-item-' + stretcherId);
                    if (!item) {
                        console.warn(`⚠️ Stretcher item ${stretcherId} not found for highlighting`);
                        return;
                    }

                    // Store original styles
                    const originalStyle = {
                        border: item.style.border,
                        backgroundColor: item.style.backgroundColor,
                        boxShadow: item.style.boxShadow,
                        transform: item.style.transform
                    };

                    // Apply highlight effect with enhanced animation
                    item.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    item.style.border = `3px solid ${color}`;
                    item.style.backgroundColor = `${color}15`;
                    item.style.boxShadow = `0 0 30px ${color}40, 0 8px 16px rgba(0,0,0,0.1)`;
                    item.style.transform = 'translateY(-4px) scale(1.02)';

                    // Add pulse animation class
                    item.classList.add('animate__animated', 'animate__pulse');

                    // Smooth scroll to item
                    item.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                        inline: 'nearest'
                    });

                    // Remove highlight after duration
                    setTimeout(() => {
                        // Restore original styles with smooth transition
                        Object.keys(originalStyle).forEach(key => {
                            item.style[key] = originalStyle[key];
                        });
                        
                        item.classList.remove('animate__animated', 'animate__pulse');
                        
                        // Remove transition after animation
                        setTimeout(() => {
                            item.style.transition = '';
                        }, 300);
                    }, duration);
                }

                function playEnhancedNotificationSound() {
                    const audio = document.getElementById('notification-sound');
                    if (audio) {
                        audio.currentTime = 0;
                        audio.volume = 0.7;
                        
                        const playPromise = audio.play();
                        if (playPromise !== undefined) {
                            playPromise
                                .then(() => {
                                    console.log('🔊 Notification sound played successfully');
                                })
                                .catch(error => {
                                    console.log('🔇 Could not play notification sound:', error.message);
                                    // Fallback: Try to enable audio on next user interaction
                                    document.addEventListener('click', enableAudio, { once: true });
                                });
                        }
                    }
                }

                function enableAudio() {
                    const audio = document.getElementById('notification-sound');
                    if (audio) {
                        audio.volume = 0.1;
                        audio.play().then(() => {
                            audio.pause();
                            audio.currentTime = 0;
                            audio.volume = 0.7;
                            console.log('🔊 Audio enabled for future notifications');
                        }).catch(() => {
                            console.log('🔇 Audio could not be enabled');
                        });
                    }
                }

                // ===================================================================
                // 🔄 Enhanced Livewire v3 Component Refresh Functions  
                // ===================================================================

                function refreshLivewireComponent() {
                    console.log('🔄 Enhanced Livewire component refresh...');
                    
                    try {
                        let refreshed = false;

                        // Method 1: Use $wire if available (Livewire v3 preferred)
                        if (typeof $wire !== 'undefined') {
                            $wire.dispatch('loadData');
                            console.log('✅ Dispatched via $wire');
                            refreshed = true;
                        }

                        // Method 2: Direct method call via Livewire.all()
                        if (!refreshed && typeof Livewire !== 'undefined' && Livewire.all && Livewire.all().length > 0) {
                            const component = Livewire.all()[0];
                            if (component && component.call) {
                                component.call('forceRefresh');
                                console.log('✅ Called forceRefresh via Livewire.all()');
                                refreshed = true;
                            }
                        }

                        // Method 3: Using Livewire.dispatch for v3
                        if (!refreshed && typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                            Livewire.dispatch('refreshData');
                            console.log('✅ Dispatched refreshData via Livewire.dispatch');
                            refreshed = true;
                        }

                        // Method 4: Try window.Livewire.dispatch
                        if (!refreshed && typeof window.Livewire !== 'undefined' && typeof window.Livewire.dispatch === 'function') {
                            window.Livewire.dispatch('refreshData');
                            console.log('✅ Dispatched via window.Livewire.dispatch');
                            refreshed = true;
                        }

                        if (refreshed) {
                            updateLastUpdateTime();
                            showEnhancedToast('อัปเดตข้อมูล', 'ข้อมูลได้รับการอัปเดตแล้ว', 'info', 2000);
                        } else {
                            throw new Error('ไม่สามารถเรียกใช้ฟังก์ชัน refresh ได้');
                        }

                    } catch (error) {
                        console.error('❌ Error refreshing component:', error);
                        showEnhancedToast('ข้อผิดพลาด', 'ไม่สามารถอัปเดตข้อมูลได้', 'error');
                        
                        // Fallback: Page reload after delay
                        setTimeout(() => {
                            console.log('🔄 Fallback: Reloading page...');
                            if (confirm('ระบบมีปัญหา ต้องการรีโหลดหน้าเว็บไหม?')) {
                                window.location.reload();
                            }
                        }, 3000);
                    }
                }

                // ===================================================================
                // 🎯 Enhanced Laravel Echo Integration
                // ===================================================================

                function initializeEchoConnection() {
                    if (typeof window.Echo === 'undefined') {
                        console.warn('⚠️ Laravel Echo not available');
                        updateConnectionStatus('error');
                        return;
                    }

                    console.log('📡 Initializing Laravel Echo connection...');
                    updateConnectionStatus('connecting');

                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        const pusher = window.Echo.connector.pusher;

                        // Enhanced connection event handlers
                        pusher.connection.bind('connected', () => {
                            console.log('✅ Echo connected successfully');
                            updateConnectionStatus('connected');
                            connectionState.lastHeartbeat = Date.now();
                        });

                        pusher.connection.bind('disconnected', () => {
                            console.log('❌ Echo disconnected');
                            updateConnectionStatus('disconnected');
                        });

                        pusher.connection.bind('error', (error) => {
                            console.error('❌ Echo connection error:', error);
                            updateConnectionStatus('error');
                        });

                        // Channel subscriptions with enhanced error handling
                        try {
                            window.Echo.channel('stretcher-updates')
                                .listen('.stretcher.updated', (e) => {
                                    console.log('🔄 Stretcher updated:', e);
                                    handleStretcherUpdate(e);
                                })
                                .listen('.new.request', (e) => {
                                    console.log('🔔 New request:', e);
                                    handleNewRequest(e);
                                })
                                .listen('.status.changed', (e) => {
                                    console.log('📊 Status changed:', e);
                                    handleStatusChange(e);
                                });

                            console.log('✅ Echo channels subscribed successfully');
                        } catch (error) {
                            console.error('❌ Failed to subscribe to channels:', error);
                            updateConnectionStatus('error');
                        }
                    }
                }

                // ===================================================================
                // 🎪 Enhanced Event Handlers
                // ===================================================================

                function handleStretcherUpdate(e) {
                    highlightStretcherItem(e.stretcher_id, '#10b981');
                    refreshLivewireComponent();
                    showEnhancedToast('อัปเดตสถานะ', `รายการ ID: ${e.stretcher_id} ได้รับการอัปเดต`, 'info');
                }

                function handleNewRequest(e) {
                    playEnhancedNotificationSound();
                    refreshLivewireComponent();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '🚨 มีรายการขอเปลใหม่!',
                            html: `
                                <div class="text-start">
                                    <div class="patient-info-popup">
                                        <p><strong>HN:</strong> <span class="text-primary">${e.request.hn}</span></p>
                                        <p><strong>ชื่อ-นามสกุล:</strong> ${e.request.pname}${e.request.fname} ${e.request.lname}</p>
                                        <p><strong>ความเร่งด่วน:</strong> <span class="text-danger fw-bold">${e.request.stretcher_priority_name}</span></p>
                                        <p><strong>จากแผนก:</strong> ${e.request.department || 'ไม่ระบุ'}</p>
                                        <p><strong>ไปแผนก:</strong> ${e.request.department2 || 'ไม่ระบุ'}</p>
                                    </div>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonText: '🫡 รับทราบ',
                            timer: 10000,
                            timerProgressBar: true,
                            showClass: {
                                popup: 'animate__animated animate__bounceIn'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            },
                            customClass: {
                                popup: 'new-request-popup',
                                title: 'text-danger',
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    }
                }

                function handleStatusChange(e) {
                    const statusColors = {
                        1: '#6b7280', // waiting - gray
                        2: '#f59e0b', // accepted - yellow  
                        3: '#06b6d4', // in progress - blue
                        4: '#10b981', // completed - green
                        5: '#ef4444'  // cancelled - red
                    };

                    const statusNames = {
                        1: 'รอรับงาน',
                        2: 'รับงานแล้ว',
                        3: 'กำลังดำเนินการ',
                        4: 'สำเร็จ',
                        5: 'ยกเลิก'
                    };

                    const color = statusColors[e.new_status] || '#6b7280';
                    
                    highlightStretcherItem(e.stretcher_id, color);
                    showEnhancedToast(
                        'เปลี่ยนสถานะ',
                        `${e.team_member || 'ระบบ'} เปลี่ยนสถานะเป็น "${statusNames[e.new_status]}"`,
                        'success'
                    );
                    
                    setTimeout(() => refreshLivewireComponent(), 1500);
                }

                // ===================================================================
                // 🎵 Enhanced Livewire Event Listeners
                // ===================================================================

                window.addEventListener('show-success', event => {
                    showEnhancedToast('สำเร็จ!', event.detail.message, 'success');
                });

                window.addEventListener('show-error', event => {
                    showEnhancedToast('เกิดข้อผิดพลาด!', event.detail.message, 'error');
                });

                window.addEventListener('stretcher-updated', event => {
                    handleStretcherUpdate(event.detail);
                });

                window.addEventListener('new-request-received', event => {
                    handleNewRequest(event.detail);
                });

                window.addEventListener('status-changed', event => {
                    handleStatusChange(event.detail);
                });

                window.addEventListener('force-refresh', event => {
                    console.log('🔄 Force refresh event received');
                    refreshLivewireComponent();
                });

                // ===================================================================
                // 🌐 Enhanced Global Functions
                // ===================================================================

                window.refreshStretcherData = refreshLivewireComponent;
                
                window.reconnectWebSocket = function() {
                    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                        console.log('🔌 Manual reconnection triggered');
                        connectionState.reconnectAttempts++;
                        updateConnectionStatus('reconnecting');
                        window.Echo.connector.pusher.connect();
                    } else {
                        console.error('❌ Echo not available for reconnection');
                        showEnhancedToast('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error');
                    }
                };

                window.debugLivewire = function() {
                    console.log('=== 🔍 Enhanced Debug Info ===');
                    console.log('Echo available:', typeof window.Echo !== 'undefined');
                    console.log('Livewire available:', typeof Livewire !== 'undefined');
                    console.log('$wire available:', typeof $wire !== 'undefined');
                    console.log('Connection state:', connectionState);
                    
                    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                        console.log('Pusher state:', window.Echo.connector.pusher.connection.state);
                    }
                    
                    showEnhancedToast('Debug Info', 'ตรวจสอบ Console สำหรับข้อมูลระบบ', 'info');
                };

                // ===================================================================
                // 🚀 Enhanced System Initialization
                // ===================================================================

                // Initialize Echo connection
                initializeEchoConnection();

                // Enhanced connection monitoring
                setInterval(() => {
                    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                        const currentState = window.Echo.connector.pusher.connection.state;
                        const wasConnected = connectionState.isConnected;
                        connectionState.isConnected = (currentState === 'connected');

                        if (connectionState.isConnected) {
                            connectionState.lastHeartbeat = Date.now();
                            if (!wasConnected) {
                                updateConnectionStatus('connected');
                                // Refresh data on reconnection
                                setTimeout(() => refreshLivewireComponent(), 1000);
                            }
                        } else if (wasConnected) {
                            updateConnectionStatus('disconnected');
                        }
                    }
                }, 5000);

                // Heartbeat monitoring
                setInterval(() => {
                    const timeSinceLastHeartbeat = Date.now() - connectionState.lastHeartbeat;
                    if (timeSinceLastHeartbeat > 60000 && connectionState.isConnected) {
                        console.warn('⚠️ No heartbeat for 60 seconds, checking connection...');
                        if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                            const state = window.Echo.connector.pusher.connection.state;
                            if (state !== 'connected') {
                                updateConnectionStatus('disconnected');
                            }
                        }
                    }
                }, 30000);

                // Periodic refresh as fallback
                setInterval(() => {
                    console.log('🕐 Periodic refresh (fallback)');
                    refreshLivewireComponent();
                }, 120000); // Every 2 minutes

                // Initialize time updates
                updateLastUpdateTime();
                setInterval(updateLastUpdateTime, 30000);

                // Try to enable audio on first user interaction
                document.addEventListener('click', enableAudio, { once: true });

                console.log('✅ Enhanced Dashboard Real-time System initialized successfully!');
            });
        </script>
    @endpush

    @push('styles')
        <style>
            /* ===================================================================
               🎨 Enhanced Dashboard Styles
               =================================================================== */

            .hero-section {
                background: var(--gradient-primary);
                color: white;
                padding: 4rem 0 3rem;
                position: relative;
                overflow: hidden;
            }

            .hero-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="40%"><stop offset="0%" stop-opacity=".1"/><stop offset="100%" stop-opacity="0"/></radialGradient></defs><rect width="100" height="20" fill="url(%23a)"/></svg>');
                opacity: 0.3;
            }

            .hero-content {
                position: relative;
                z-index: 2;
            }

            .hero-title {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
                text-shadow: 0 4px 6px rgba(0,0,0,0.1);
                line-height: 1.2;
            }

            .hero-subtitle {
                font-size: 1.25rem;
                opacity: 0.9;
                font-weight: 300;
                margin-bottom: 0;
            }

            .realtime-badge {
                background: linear-gradient(45deg, #ff6b6b, #ff8e53);
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 2rem;
                font-size: 1rem;
                font-weight: 600;
                box-shadow: var(--shadow-lg);
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .hero-visual {
                position: relative;
                z-index: 2;
            }

            .status-dashboard {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: var(--radius-xl);
                padding: 2rem;
                box-shadow: var(--shadow-xl);
            }

            .status-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
                font-weight: 500;
            }

            .status-item:last-child {
                margin-bottom: 0;
            }

            .status-item i {
                font-size: 1.5rem;
                width: 2rem;
                text-align: center;
            }

            /* Enhanced Connection Status Bar */
            .connection-status-bar {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid var(--border-color);
                padding: 1rem 0;
                box-shadow: var(--shadow-sm);
                position: sticky;
                top: 0;
                z-index: 1000;
            }

            .connection-info {
                display: flex;
                align-items: center;
            }

            .connection-indicator {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.5rem 1rem;
                border-radius: var(--radius-2xl);
                font-weight: 600;
                font-size: 0.9rem;
                position: relative;
                transition: all var(--transition-normal);
            }

            .connection-indicator.status-connected {
                background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                color: #059669;
                border: 2px solid #10b981;
            }

            .connection-indicator.status-connecting {
                background: linear-gradient(135deg, #fef3c7, #fde68a);
                color: #d97706;
                border: 2px solid #f59e0b;
            }

            .connection-indicator.status-disconnected {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                color: #dc2626;
                border: 2px solid #ef4444;
            }

            .connection-indicator.status-error {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                color: #991b1b;
                border: 2px solid #dc2626;
            }

            .connection-pulse {
                position: absolute;
                top: 50%;
                left: 1rem;
                transform: translateY(-50%);
                width: 10px;
                height: 10px;
                background: currentColor;
                border-radius: 50%;
                opacity: 0.7;
            }

            .status-connected .connection-pulse {
                animation: pulse-success 2s infinite;
            }

            .status-connecting .connection-pulse {
                animation: pulse-warning 1s infinite;
            }

            .connection-details {
                color: var(--text-secondary);
                font-weight: 500;
            }

            .connection-actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .main-content {
                padding: 2rem 0;
            }

            /* Enhanced Animations */
            @keyframes pulse-success {
                0%, 100% {
                    opacity: 0.7;
                    transform: translateY(-50%) scale(1);
                }
                50% {
                    opacity: 1;
                    transform: translateY(-50%) scale(1.2);
                }
            }

            @keyframes pulse-warning {
                0%, 100% {
                    opacity: 0.5;
                    transform: translateY(-50%) scale(1);
                }
                50% {
                    opacity: 1;
                    transform: translateY(-50%) scale(1.3);
                }
            }

            /* Enhanced SweetAlert2 Customization */
            .new-request-popup {
                border-radius: var(--radius-xl) !important;
                box-shadow: var(--shadow-xl) !important;
            }

            .patient-info-popup p {
                margin-bottom: 0.75rem;
                padding: 0.5rem;
                background: #f8fafc;
                border-radius: var(--radius-md);
                border-left: 4px solid var(--primary-color);
            }

            /* Enhanced Responsive Design */
            @media (max-width: 768px) {
                .hero-title {
                    font-size: 2rem;
                }

                .hero-subtitle {
                    font-size: 1rem;
                }

                .realtime-badge {
                    font-size: 0.875rem;
                    padding: 0.5rem 1rem;
                }

                .status-dashboard {
                    padding: 1.5rem;
                }

                .connection-status-bar {
                    padding: 0.75rem 0;
                }

                .connection-actions {
                    margin-top: 1rem;
                    justify-content: stretch;
                }

                .connection-actions .btn {
                    flex: 1;
                }
            }

            @media (max-width: 576px) {
                .hero-section {
                    padding: 2rem 0 1.5rem;
                }

                .hero-title {
                    font-size: 1.75rem;
                }

                .connection-indicator {
                    font-size: 0.8rem;
                    padding: 0.4rem 0.8rem;
                }
            }
        </style>
    @endpush
@endsection