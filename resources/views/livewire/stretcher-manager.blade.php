{{-- resources/views/livewire/stretcher-manager.blade.php --}}
<div>
    <!-- Audio element for notifications -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('storage/sounds/notification.wav') }}" type="audio/wav">
    </audio>

    <!-- Real-time Notification -->
    @if ($showNotification)
        <div class="alert alert-{{ $notificationType === 'error' ? 'danger' : ($notificationType === 'success' ? 'success' : ($notificationType === 'warning' ? 'warning' : 'info')) }} alert-dismissible fade show position-fixed"
            style="top: 20px; right: 20px; z-index: 1060; min-width: 300px;" id="realtime-notification">
            <div class="d-flex align-items-center">
                @if ($notificationType === 'success')
                    <i class="fas fa-check-circle me-2"></i>
                @elseif($notificationType === 'error')
                    <i class="fas fa-exclamation-triangle me-2"></i>
                @elseif($notificationType === 'warning')
                    <i class="fas fa-sync-alt me-2"></i>
                @else
                    <i class="fas fa-bell me-2"></i>
                @endif
                <div class="flex-grow-1">
                    <strong>{{ $notificationMessage }}</strong>
                </div>
                <button type="button" class="btn-close" wire:click="hideNotification" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-primary mb-3">
            <i class="fas fa-bed me-2"></i>
            ศูนย์เปล - โรงพยาบาลหนองหาน
            <span class="badge bg-success">Real-time</span>
        </h1>

        @if (session()->has('name'))
            <div class="d-flex justify-content-center align-items-center mb-3">
                <div class="card bg-light">
                    <div class="card-body py-2 px-3">
                        <span class="text-muted">ผู้ใช้งาน:</span>
                        <strong class="text-primary">{{ session()->get('name') }}</strong>
                        @if ($userType === 'team_member')
                            <span class="badge bg-primary ms-2">ทีมเปล</span>
                        @else
                            <span class="badge bg-success ms-2">ผู้ดูแลระบบ</span>
                        @endif

                        <button type="button" class="btn btn-outline-danger btn-sm ms-3" wire:click="logout">
                            <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Connection Status Indicator -->
        <div class="mb-3">
            <div id="connection-status-container">
                <span id="connection-status" class="badge bg-secondary">
                    <i class="fas fa-circle me-1"></i>กำลังเชื่อมต่อ...
                </span>
                <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="window.reconnectWebSocket()"
                    title="เชื่อมต่อใหม่">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button type="button" class="btn btn-outline-info btn-sm ms-1" onclick="window.debugEchoStatus()"
                    title="ตรวจสอบสถานะ">
                    <i class="fas fa-bug"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white stats-card">
                <div class="card-body text-center">
                    <h5 class="card-title">
                        <i class="fas fa-list me-2"></i><span id="stat-total">{{ $stats['total_today'] }}</span>
                    </h5>
                    <p class="card-text mb-0">ขอเปลวันนี้ทั้งหมด</p>
                </div>
            </div>
        </div>

        @if ($currentUserId)
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="fas fa-check me-2"></i><span id="stat-accepted">{{ $stats['my_accepted'] }}</span>
                        </h5>
                        <p class="card-text mb-0">ที่คุณรับวันนี้</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-md-2 col-4 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center py-2">
                    <h6 class="card-title mb-1">
                        <i class="fas fa-moon me-1"></i>{{ $stats['night_shift'] }}
                    </h6>
                    <small class="card-text">ดึก (00:00-07:59)</small>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-4 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center py-2">
                    <h6 class="card-title mb-1">
                        <i class="fas fa-sun me-1"></i>{{ $stats['morning_shift'] }}
                    </h6>
                    <small class="card-text">เช้า (08:00-16:00)</small>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-4 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center py-2">
                    <h6 class="card-title mb-1">
                        <i class="fas fa-cloud-sun me-1"></i>{{ $stats['afternoon_shift'] }}
                    </h6>
                    <small class="card-text">บ่าย (16:00-23:59)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" wire:model.live="hideCompleted"
                            id="hideCompleted">
                        <label class="form-check-label" for="hideCompleted">
                            <i class="fas fa-eye-slash me-1"></i>ซ่อนรายการที่สำเร็จแล้ว
                        </label>
                    </div>

                    @if ($currentUserId)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" wire:model.live="showMyOnly"
                                id="showMyOnly">
                            <label class="form-check-label" for="showMyOnly">
                                <i class="fas fa-user me-1"></i>แสดงเฉพาะของคุณ
                            </label>
                        </div>
                    @endif
                </div>

                <div class="col-md-6 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <button type="button" class="btn btn-outline-primary btn-sm me-2" wire:click="loadData">
                            <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                        </button>
                        <small class="text-muted">
                            <i class="fas fa-wifi me-1"></i>
                            Real-time |
                            <span id="last-update">{{ now()->format('H:i:s') }}</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stretcher Requests List -->
    <div class="row" id="stretcher-requests-grid">
        @forelse($data as $request)
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="card stretcher-item h-100" id="stretcher-item-{{ $request['stretcher_register_id'] }}"
                    data-request-id="{{ $request['stretcher_register_id'] }}"
                    data-status="{{ $request['stretcher_work_status_id'] }}"
                    data-team="{{ $request['stretcher_team_list_id'] ?? '' }}">

                    <!-- Card Header with Status -->
                    <div class="card-header d-flex justify-content-between align-items-center p-2">
                        <div>
                            @php
                                $statusConfig = [
                                    1 => ['class' => 'secondary', 'icon' => 'clock', 'text' => 'รอรับงาน'],
                                    2 => ['class' => 'warning', 'icon' => 'hand-paper', 'text' => 'รับงานแล้ว'],
                                    3 => ['class' => 'info', 'icon' => 'running', 'text' => 'กำลังดำเนินการ'],
                                    4 => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'สำเร็จ'],
                                    5 => ['class' => 'dark', 'icon' => 'times-circle', 'text' => 'ยกเลิก'],
                                ];
                                $status = $statusConfig[$request['stretcher_work_status_id']] ?? $statusConfig[1];
                            @endphp

                            <span class="badge bg-{{ $status['class'] }} status-badge">
                                <i class="fas fa-{{ $status['icon'] }} me-1"></i>
                                {{ $status['text'] }}
                            </span>
                        </div>

                        <small class="text-muted">
                            ID: <strong>{{ $request['stretcher_register_id'] }}</strong>
                        </small>
                    </div>

                    <!-- Card Body with Patient Info -->
                    <div class="card-body stretcher-card p-3">
                        <div class="row g-2">
                            <div class="col-4"><strong class="text-primary">HN:</strong></div>
                            <div class="col-8">{{ $request['hn'] }}</div>

                            <div class="col-4"><strong class="text-primary">ชื่อ-นามสกุล:</strong></div>
                            <div class="col-8">{{ $request['pname'] }}{{ $request['fname'] }}
                                {{ $request['lname'] }}</div>

                            <div class="col-4"><strong class="text-primary">ประเภทเปล:</strong></div>
                            <div class="col-8">{{ $request['stretcher_type_name'] }}</div>

                            @if (!empty($request['stretcher_o2tube_type_name']))
                                <div class="col-4"><strong class="text-primary">ออกซิเจน:</strong></div>
                                <div class="col-8">{{ $request['stretcher_o2tube_type_name'] }}</div>
                            @endif

                            @if (!empty($request['stretcher_emergency_name']))
                                <div class="col-4"><strong class="text-danger">ฉุกเฉิน:</strong></div>
                                <div class="col-8 text-danger">{{ $request['stretcher_emergency_name'] }}</div>
                            @endif

                            <div class="col-4"><strong class="text-primary">ความเร่งด่วน:</strong></div>
                            <div
                                class="col-8 {{ in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']) ? 'urgent-text fw-bold' : '' }}">
                                {{ $request['stretcher_priority_name'] }}
                                @if (in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']))
                                    <i class="fas fa-exclamation-triangle ms-1 text-danger"></i>
                                @endif
                            </div>

                            <div class="col-4"><strong class="text-primary">ผู้ขอเปล:</strong></div>
                            <div class="col-8">{{ $request['dname'] }}</div>

                            <div class="col-4"><strong class="text-primary">จากแผนก:</strong></div>
                            <div class="col-8">{{ $request['department'] }}</div>

                            <div class="col-4"><strong class="text-primary">ไปแผนก:</strong></div>
                            <div class="col-8">{{ $request['department2'] }}</div>

                            @if (!empty($request['from_note']))
                                <div class="col-4"><strong class="text-danger">หมายเหตุ (1):</strong></div>
                                <div class="col-8">{{ $request['from_note'] }}</div>
                            @endif

                            @if (!empty($request['send_note']))
                                <div class="col-4"><strong class="text-danger">หมายเหตุ (2):</strong></div>
                                <div class="col-8">{{ $request['send_note'] }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Footer with Actions and Time -->
                    <div class="card-footer p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted time-display">
                                <i class="fas fa-clock me-1"></i>
                                {{ \Carbon\Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])->diffForHumans() }}
                            </small>

                            @if (!empty($request['name']))
                                <small class="text-info team-member">
                                    <i class="fas fa-user me-1"></i>{{ $request['name'] }}
                                </small>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        @if ($currentUserId && $userType === 'team_member')
                            @if (empty($request['stretcher_team_list_id']) && $request['stretcher_work_status_id'] == 1)
                                @if ($pendingWorkCount <= 0)
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-primary btn-sm accept-btn"
                                            wire:click="acceptRequest({{ $request['stretcher_register_id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="acceptRequest({{ $request['stretcher_register_id'] }})"
                                            data-request-id="{{ $request['stretcher_register_id'] }}">
                                            <span wire:loading.remove
                                                wire:target="acceptRequest({{ $request['stretcher_register_id'] }})">
                                                <i class="fas fa-hand-paper me-1"></i>รับงาน
                                            </span>
                                            <span wire:loading
                                                wire:target="acceptRequest({{ $request['stretcher_register_id'] }})">
                                                <i class="fas fa-spinner fa-spin me-1"></i>กำลังรับงาน...
                                            </span>
                                        </button>
                                    </div>
                                @else
                                    <div class="d-grid">
                                        <button class="btn btn-danger btn-sm" disabled>
                                            <i class="fas fa-exclamation-triangle me-1"></i>คุณมีงานค้างอยู่
                                        </button>
                                    </div>
                                @endif
                            @elseif($request['stretcher_team_list_id'] == $currentUserId && $request['stretcher_work_status_id'] == 2)
                                <div class="d-grid">
                                    <button type="button" class="btn btn-info btn-sm text-white"
                                        wire:click="sendToPatient({{ $request['stretcher_register_id'] }})"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove>
                                            <i class="fas fa-running me-1"></i>ไปรับผู้ป่วย
                                        </span>
                                        <span wire:loading>
                                            <i class="fas fa-spinner fa-spin me-1"></i>กำลังอัปเดต...
                                        </span>
                                    </button>
                                </div>
                            @elseif($request['stretcher_team_list_id'] == $currentUserId && $request['stretcher_work_status_id'] == 3)
                                <div class="d-grid">
                                    <button type="button" class="btn btn-success btn-sm"
                                        wire:click="completeTask({{ $request['stretcher_register_id'] }})"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove>
                                            <i class="fas fa-check-circle me-1"></i>งานสำเร็จ
                                        </span>
                                        <span wire:loading>
                                            <i class="fas fa-spinner fa-spin me-1"></i>กำลังบันทึก...
                                        </span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-bed fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">ไม่มีข้อมูลการขอเปล</h5>
                    <p class="text-muted">ยังไม่มีรายการขอเปลสำหรับวันนี้</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Loading Indicator -->
    <div wire:loading.flex wire:target="loadData" class="justify-content-center py-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // ===================================================================
        // 🎯 Enhanced Real-time Stretcher Management System
        // Laravel Echo + Livewire Integration with Better Refresh
        // ===================================================================

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Initializing Enhanced Real-time Stretcher System...');

            let isConnected = false;
            let reconnectAttempts = 0;
            const maxReconnectAttempts = 5;
            let refreshTimeout;

            // ===================================================================
            // 🔌 Enhanced Echo Connection Management
            // ===================================================================

            function initializeEchoConnection() {
                if (typeof window.Echo === 'undefined') {
                    console.warn('⚠️ Laravel Echo not available');
                    updateConnectionStatus('no-echo');
                    return;
                }

                console.log('📡 Laravel Echo available, checking connection...');

                if (window.Echo.connector && window.Echo.connector.pusher) {
                    const pusher = window.Echo.connector.pusher;

                    pusher.connection.bind('connected', () => {
                        console.log('✅ WebSocket connected successfully');
                        updateConnectionStatus('connected');
                        isConnected = true;
                        reconnectAttempts = 0;
                    });

                    pusher.connection.bind('disconnected', () => {
                        console.log('❌ WebSocket disconnected');
                        updateConnectionStatus('disconnected');
                        isConnected = false;
                        attemptReconnection();
                    });

                    pusher.connection.bind('error', (error) => {
                        console.error('❌ WebSocket error:', error);
                        updateConnectionStatus('error');
                        isConnected = false;
                        attemptReconnection();
                    });

                    pusher.connection.bind('unavailable', () => {
                        console.warn('⚠️ WebSocket unavailable');
                        updateConnectionStatus('unavailable');
                        isConnected = false;
                        attemptReconnection();
                    });

                    const state = pusher.connection.state;
                    console.log('Initial connection state:', state);
                    updateConnectionStatus(state);
                    isConnected = (state === 'connected');
                }
            }

            function attemptReconnection() {
                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    const delay = Math.pow(2, reconnectAttempts) * 1000;

                    console.log(`🔄 Attempting reconnection #${reconnectAttempts} in ${delay}ms...`);
                    updateConnectionStatus('reconnecting');

                    setTimeout(() => {
                        if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                            window.Echo.connector.pusher.connect();
                        }
                    }, delay);
                } else {
                    console.error('❌ Max reconnection attempts reached');
                    updateConnectionStatus('failed');
                }
            }

            function updateConnectionStatus(status) {
                const statusElement = document.getElementById('connection-status');
                if (!statusElement) return;

                const statusConfig = {
                    'connected': {
                        class: 'bg-success',
                        text: 'เชื่อมต่อแล้ว',
                        icon: 'wifi'
                    },
                    'connecting': {
                        class: 'bg-warning text-dark',
                        text: 'กำลังเชื่อมต่อ',
                        icon: 'spinner fa-spin'
                    },
                    'disconnected': {
                        class: 'bg-danger',
                        text: 'ขาดการเชื่อมต่อ',
                        icon: 'wifi-slash'
                    },
                    'reconnecting': {
                        class: 'bg-warning text-dark',
                        text: 'กำลังเชื่อมต่อใหม่',
                        icon: 'sync fa-spin'
                    },
                    'error': {
                        class: 'bg-danger',
                        text: 'เกิดข้อผิดพลาด',
                        icon: 'exclamation-triangle'
                    },
                    'unavailable': {
                        class: 'bg-secondary',
                        text: 'ไม่พร้อมใช้งาน',
                        icon: 'cloud-slash'
                    },
                    'failed': {
                        class: 'bg-dark',
                        text: 'เชื่อมต่อไม่ได้',
                        icon: 'times-circle'
                    },
                    'no-echo': {
                        class: 'bg-info',
                        text: 'โหมดพื้นฐาน',
                        icon: 'info-circle'
                    }
                };

                const config = statusConfig[status] || statusConfig['error'];
                statusElement.className = `badge ${config.class}`;
                statusElement.innerHTML = `<i class="fas fa-${config.icon} me-1"></i>${config.text}`;
            }

            // ===================================================================
            // 🔄 Enhanced Refresh Functions
            // ===================================================================

            function forceRefreshData() {
                console.log('🔄 Force refresh data triggered');

                // Clear any existing timeout
                if (refreshTimeout) {
                    clearTimeout(refreshTimeout);
                }

                // Call Livewire method multiple ways to ensure it works (Livewire v3 compatible)
                try {
                    let refreshed = false;

                    // Method 1: Using $wire if available (Livewire v3 preferred)
                    if (typeof $wire !== 'undefined') {
                        $wire.call('forceRefresh');
                        console.log('✅ Called forceRefresh via $wire');
                        refreshed = true;
                    }

                    // Method 2: Direct method call via Livewire.all()
                    if (!refreshed && typeof Livewire !== 'undefined' && Livewire.all && Livewire.all().length >
                        0) {
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
                    if (!refreshed && typeof window.Livewire !== 'undefined' && typeof window.Livewire.dispatch ===
                        'function') {
                        window.Livewire.dispatch('refreshData');
                        console.log('✅ Dispatched via window.Livewire.dispatch');
                        refreshed = true;
                    }

                    if (!refreshed) {
                        console.warn('⚠️ No Livewire refresh method worked, trying alternative...');

                        // Try to find component by wire:id
                        const wireElements = document.querySelectorAll('[wire\\:id]');
                        if (wireElements.length > 0) {
                            const wireId = wireElements[0].getAttribute('wire:id');
                            if (wireId && typeof Livewire !== 'undefined' && Livewire.find) {
                                const component = Livewire.find(wireId);
                                if (component && component.call) {
                                    component.call('forceRefresh');
                                    console.log('✅ Called via Livewire.find()');
                                    refreshed = true;
                                }
                            }
                        }
                    }

                    if (!refreshed) {
                        throw new Error('All refresh methods failed');
                    }

                } catch (error) {
                    console.error('❌ Error calling refresh:', error);

                    // Fallback: Page reload if all else fails
                    console.log('🔄 Fallback: Reloading page in 3 seconds...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }

                updateLastUpdateTime();
            }

            function smartRefresh() {
                // Prevent too frequent refreshes
                if (refreshTimeout) {
                    clearTimeout(refreshTimeout);
                }

                refreshTimeout = setTimeout(() => {
                    forceRefreshData();
                }, 500); // Debounce 500ms
            }

            // ===================================================================
            // 🎵 Audio and Visual Effects
            // ===================================================================

            function playNotificationSound() {
                const audio = document.getElementById('notification-sound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => {
                        console.log('Could not play notification sound:', e.message);
                    });
                }
            }

            function highlightStretcherItem(stretcherId, color = '#28a745', duration = 3000) {
                const item = document.getElementById(`stretcher-item-${stretcherId}`);
                if (!item) {
                    console.warn(`⚠️ Stretcher item ${stretcherId} not found for highlighting`);
                    return;
                }

                const originalStyle = {
                    border: item.style.border,
                    backgroundColor: item.style.backgroundColor,
                    boxShadow: item.style.boxShadow,
                    transform: item.style.transform
                };

                // Apply highlight with animation
                item.style.border = `3px solid ${color}`;
                item.style.backgroundColor = `${color}20`;
                item.style.boxShadow = `0 0 20px ${color}40`;
                item.style.transform = 'scale(1.02)';
                item.style.transition = 'all 0.3s ease';

                // Scroll to item
                item.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Remove highlight after duration
                setTimeout(() => {
                    Object.keys(originalStyle).forEach(key => {
                        item.style[key] = originalStyle[key];
                    });
                }, duration);
            }

            function updateAcceptButtons(requestId, isAccepted = true) {
                const buttons = document.querySelectorAll(`[data-request-id="${requestId}"] .accept-btn`);
                buttons.forEach(btn => {
                    if (isAccepted) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-check me-1"></i>มีคนรับแล้ว';
                        btn.className = 'btn btn-secondary btn-sm';
                    }
                });
            }

            function showToast(title, message, type = 'info', duration = 3000) {
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: duration,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: type,
                        title: title,
                        text: message
                    });
                }
            }

            // ===================================================================
            // 🎯 Enhanced Livewire Event Listeners
            // ===================================================================

            // Job accepted successfully
            window.addEventListener('job-accepted-successfully', (e) => {
                console.log('✅ Job accepted successfully:', e.detail);

                const {
                    requestId,
                    teamMember,
                    timestamp
                } = e.detail;

                // Visual feedback
                highlightStretcherItem(requestId, '#28a745');
                updateAcceptButtons(requestId, true);

                // Show success notification
                showToast('รับงานสำเร็จ', `${teamMember} รับงาน ID: ${requestId}`, 'success');

                // Play sound
                playNotificationSound();

                // Force refresh after a short delay
                setTimeout(() => {
                    forceRefreshData();
                }, 1000);
            });

            // Data refreshed event
            window.addEventListener('data-refreshed', (e) => {
                console.log('🔄 Data refreshed:', e.detail);

                const {
                    timestamp,
                    count
                } = e.detail;

                // Update last update time
                updateLastUpdateTime();

                // Show subtle notification
                console.log(`✅ Data refreshed at ${timestamp}, ${count} items loaded`);
            });

            // Delayed refresh event
            window.addEventListener('delayed-refresh', (e) => {
                console.log('⏰ Delayed refresh triggered:', e.detail);

                const delay = e.detail.delay || 1000;

                setTimeout(() => {
                    forceRefreshData();
                }, delay);
            });

            // Stretcher item updated
            window.addEventListener('stretcher-item-updated', (e) => {
                console.log('🔄 Stretcher item updated:', e.detail);

                const {
                    stretcherId,
                    action,
                    teamMember
                } = e.detail;

                const colors = {
                    'accepted': '#28a745', // green
                    'sent': '#17a2b8', // blue
                    'completed': '#6f42c1' // purple
                };

                const actionTexts = {
                    'accepted': 'รับงาน',
                    'sent': 'ไปรับผู้ป่วย',
                    'completed': 'งานสำเร็จ'
                };

                const color = colors[action] || '#ffc107';

                highlightStretcherItem(stretcherId, color);
                showToast('อัปเดตสถานะ',
                    `${teamMember} ${actionTexts[action]} รายการ ${stretcherId}`,
                    'info');

                // Refresh data after visual effect
                setTimeout(() => {
                    smartRefresh();
                }, 1500);
            });

            // New request arrived
            window.addEventListener('new-request-arrived', (e) => {
                console.log('🔔 New request arrived:', e.detail);

                const {
                    request
                } = e.detail;

                // Play notification sound
                playNotificationSound();

                // Show detailed notification
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'มีรายการขอเปลใหม่!',
                        html: `
                    <div class="text-start">
                        <strong>HN:</strong> ${request.hn}<br>
                        <strong>ชื่อ:</strong> ${request.pname}${request.fname} ${request.lname}<br>
                        <strong>ความเร่งด่วน:</strong> <span class="text-danger">${request.stretcher_priority_name}</span><br>
                        <strong>จากแผนก:</strong> ${request.department}<br>
                        <strong>ไปแผนก:</strong> ${request.department2}
                    </div>
                `,
                        icon: 'info',
                        confirmButtonText: 'รับทราบ',
                        timer: 8000,
                        timerProgressBar: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        }
                    });
                }

                // Refresh data
                smartRefresh();
            });

            // Status change detected
            window.addEventListener('status-change-detected', (e) => {
                console.log('📊 Status change detected:', e.detail);

                const {
                    stretcherId,
                    newStatus,
                    teamMember
                } = e.detail;

                const colors = {
                    1: '#6c757d', // waiting - gray
                    2: '#ffc107', // accepted - yellow  
                    3: '#17a2b8', // in progress - blue
                    4: '#28a745', // completed - green
                    5: '#dc3545' // cancelled - red
                };

                const color = colors[newStatus] || '#6c757d';

                highlightStretcherItem(stretcherId, color);

                showToast('เปลี่ยนสถานะ',
                    `${teamMember} เปลี่ยนสถานะรายการ ${stretcherId}`,
                    'warning');

                // Refresh data after visual effect
                setTimeout(() => {
                    smartRefresh();
                }, 1500);
            });

            // Auto-hide notification
            window.addEventListener('auto-hide-notification', (e) => {
                const delay = e.detail.delay || 5000;
                setTimeout(() => {
                    // Use Livewire v3 compatible method
                    try {
                        if (typeof $wire !== 'undefined') {
                            $wire.call('hideNotification');
                        } else if (typeof Livewire !== 'undefined' && Livewire.all && Livewire.all()
                            .length > 0) {
                            const component = Livewire.all()[0];
                            if (component && component.call) {
                                component.call('hideNotification');
                            }
                        }
                    } catch (error) {
                        console.warn('Could not hide notification:', error.message);
                    }
                }, delay);
            });

            // ===================================================================
            // 🕒 Time Management
            // ===================================================================

            function updateLastUpdateTime() {
                const timeElement = document.getElementById('last-update');
                if (timeElement) {
                    timeElement.textContent = new Date().toLocaleTimeString('th-TH');
                }
            }

            // Update time every 30 seconds
            setInterval(updateLastUpdateTime, 30000);

            // ===================================================================
            // 🌐 Enhanced Global Functions
            // ===================================================================

            // Manual WebSocket reconnection
            window.reconnectWebSocket = function() {
                if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                    console.log('🔌 Manual reconnection triggered');
                    reconnectAttempts = 0;
                    window.Echo.connector.pusher.connect();
                    updateConnectionStatus('connecting');
                } else {
                    console.error('❌ Echo not available for reconnection');
                }
            };

            // Debug Echo status
            window.debugEchoStatus = function() {
                console.log('=== 🔍 Enhanced Echo Debug Info ===');
                console.log('Echo available:', typeof window.Echo !== 'undefined');
                console.log('Livewire available:', typeof Livewire !== 'undefined');
                console.log('$wire available:', typeof $wire !== 'undefined');

                if (window.Echo) {
                    console.log('Connector available:', typeof window.Echo.connector !== 'undefined');

                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        const pusher = window.Echo.connector.pusher;
                        console.log('Connection state:', pusher.connection.state);
                        console.log('Socket ID:', pusher.connection.socket_id);
                        console.log('Is connected:', isConnected);
                        console.log('Reconnect attempts:', reconnectAttempts);

                        try {
                            console.log('Active channels:', Object.keys(pusher.channels.channels));
                        } catch (e) {
                            console.warn('Could not get channel info:', e.message);
                        }
                    }
                }

                if (typeof Livewire !== 'undefined') {
                    console.log('Livewire components:', Livewire.all().length);
                }

                console.log('======================');

                // Show alert with debug info
                if (typeof Swal !== 'undefined') {
                    const echoAvailable = typeof window.Echo !== 'undefined';
                    const livewireAvailable = typeof Livewire !== 'undefined';
                    const connectionState = window.Echo?.connector?.pusher?.connection?.state || 'N/A';

                    Swal.fire({
                        title: 'Enhanced Debug Info',
                        html: `
                    <div class="text-start">
                        <strong>Echo Available:</strong> ${echoAvailable ? 'Yes' : 'No'}<br>
                        <strong>Livewire Available:</strong> ${livewireAvailable ? 'Yes' : 'No'}<br>
                        <strong>Connection State:</strong> ${connectionState}<br>
                        <strong>Is Connected:</strong> ${isConnected ? 'Yes' : 'No'}<br>
                        <strong>Reconnect Attempts:</strong> ${reconnectAttempts}/${maxReconnectAttempts}<br>
                        <strong>Components:</strong> ${livewireAvailable ? Livewire.all().length : 'N/A'}
                    </div>
                `,
                        icon: 'info',
                        confirmButtonText: 'ตกลง'
                    });
                }
            };

            // Enhanced force refresh
            window.forceRefresh = function() {
                console.log('🔄 Enhanced manual refresh triggered');
                forceRefreshData();
            };

            // Test refresh function
            window.testRefresh = function() {
                console.log('🧪 Testing refresh methods...');

                try {
                    forceRefreshData();
                    showToast('Test Refresh', 'Refresh test completed', 'info');
                } catch (error) {
                    console.error('❌ Test refresh failed:', error);
                    showToast('Test Failed', 'Refresh test failed', 'error');
                }
            };

            // ===================================================================
            // 🚀 Enhanced Initialize Everything
            // ===================================================================

            // Initialize Echo connection
            initializeEchoConnection();

            // Set initial update time
            updateLastUpdateTime();

            // Enhanced connection monitoring
            setInterval(() => {
                if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                    const currentState = window.Echo.connector.pusher.connection.state;
                    const wasConnected = isConnected;
                    isConnected = (currentState === 'connected');

                    if (wasConnected !== isConnected) {
                        updateConnectionStatus(currentState);

                        // If reconnected, refresh data
                        if (isConnected && !wasConnected) {
                            console.log('🔄 Reconnected - refreshing data...');
                            setTimeout(() => {
                                forceRefreshData();
                            }, 1000);
                        }
                    }
                }
            }, 5000);

            // Periodic refresh every 2 minutes as fallback
            setInterval(() => {
                console.log('🕐 Periodic refresh (fallback)');
                smartRefresh();
            }, 120000);

            console.log('✅ Enhanced Real-time Stretcher System initialized successfully!');
        });

        // ===================================================================
        // 🔧 Enhanced Utility Functions
        // ===================================================================

        function isWebSocketConnected() {
            return window.Echo &&
                window.Echo.connector &&
                window.Echo.connector.pusher &&
                window.Echo.connector.pusher.connection.state === 'connected';
        }

        // Global debug function
        window.stretcherDebug = function() {
            console.log('=== 🏥 Stretcher System Debug ===');
            console.log('WebSocket Connected:', isWebSocketConnected());
            console.log('Last Update:', document.getElementById('last-update')?.textContent);
            console.log('Notification Sound Available:', !!document.getElementById('notification-sound'));
            console.log('================================');
        };

        console.log('📱 Enhanced Stretcher Manager Real-time View loaded successfully!');
    </script>
@endpush

@push('styles')
    <style>
        /* ===================================================================
           🎨 Real-time UI Styles
           =================================================================== */

        .stretcher-item {
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .stretcher-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stretcher-item.updated {
            border: 2px solid #28a745 !important;
            background-color: rgba(40, 167, 69, 0.05) !important;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.3) !important;
        }

        .urgent-text {
            color: #dc3545 !important;
            font-weight: 600;
        }

        .urgent-text::after {
            content: " 🚨";
            font-size: 0.8em;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }

        .stats-card {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            transition: transform 0.2s ease;
            border-radius: 10px;
        }

        .stats-card:hover {
            transform: scale(1.02);
        }

        .accept-btn:disabled {
            cursor: not-allowed !important;
        }

        .stretcher-card {
            font-size: 0.875rem;
        }

        .time-display,
        .team-member {
            font-size: 0.75rem;
        }

        /* Connection status */
        #connection-status-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        #connection-status {
            animation: fadeIn 0.3s ease-in;
        }

        /* Notification positioning */
        #realtime-notification {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Loading spinner */
        .spinner-border {
            animation-duration: 0.75s;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .stretcher-card {
                font-size: 0.8rem;
            }

            .status-badge {
                font-size: 0.7rem;
            }

            #connection-status-container {
                flex-direction: column;
                gap: 0.25rem;
            }

            #realtime-notification {
                position: relative !important;
                top: auto !important;
                right: auto !important;
                margin: 1rem;
                min-width: auto !important;
            }
        }

        /* Print styles */
        @media print {

            .btn,
            .card-footer,
            #connection-status-container,
            #realtime-notification {
                display: none !important;
            }

            .stretcher-item {
                break-inside: avoid;
                margin-bottom: 1rem;
                border: 1px solid #000 !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
@endpush
