{{-- resources/views/livewire/stretcher-manager.blade.php --}}
<div class="stretcher-manager-container">
    <!-- Enhanced Audio System -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
    </audio>

    <!-- Enhanced Real-time Notification -->
    @if ($showNotification)
        <div class="enhanced-notification animate__animated animate__slideInRight" 
             id="realtime-notification"
             style="position: fixed; top: 20px; right: 20px; z-index: 1060; min-width: 350px;">
            <div class="notification-content alert alert-{{ $notificationType === 'error' ? 'danger' : ($notificationType === 'success' ? 'success' : ($notificationType === 'warning' ? 'warning' : 'info')) }} alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        @if ($notificationType === 'success')
                            <i class="fas fa-check-circle fa-lg"></i>
                        @elseif($notificationType === 'error')
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        @elseif($notificationType === 'warning')
                            <i class="fas fa-sync-alt fa-lg"></i>
                        @else
                            <i class="fas fa-bell fa-lg"></i>
                        @endif
                    </div>
                    <div class="notification-body flex-grow-1">
                        <strong class="notification-title">{{ $notificationMessage }}</strong>
                        <div class="notification-timestamp">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>{{ now()->format('H:i:s') }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" wire:click="hideNotification" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <!-- Enhanced User Section -->
    @if (session()->has('name'))
        <section class="user-section animate__animated animate__fadeInDown">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="user-card">
                            <div class="user-card-content">
                                <div class="user-avatar-section">
                                    <div class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="user-info">
                                        <h5 class="user-name">{{ session()->get('name') }}</h5>
                                        <div class="user-badges">
                                            @if ($userType === 'team_member')
                                                <span class="badge badge-primary">
                                                    <i class="fas fa-user-md me-1"></i>ทีมเปล
                                                </span>
                                            @else
                                                <span class="badge badge-admin">
                                                    <i class="fas fa-user-cog me-1"></i>ผู้ดูแลระบบ
                                                </span>
                                            @endif
                                            <span class="badge badge-online">
                                                <i class="fas fa-circle me-1"></i>ออนไลน์
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <button type="button" class="btn btn-outline-danger" wire:click="logout">
                                        <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Enhanced Statistics Section -->
    <section class="statistics-section animate__animated animate__fadeInUp">
        <div class="container">
            <div class="section-header text-center mb-4">
                <h2 class="section-title">
                    <i class="fas fa-chart-bar me-2"></i>สถิติการใช้งานวันนี้
                </h2>
                <p class="section-subtitle">ข้อมูลการขอเปลแบบเรียลไทม์</p>
            </div>

            <div class="stats-grid">
                <!-- Total Requests Today -->
             {{--    <div class="stat-card main-stat animate__animated animate__zoomIn">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $stats['total_today'] }}</div>
                        <div class="stat-label">ขอเปลวันนี้ทั้งหมด</div>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up text-success"></i>
                            <span class="text-success">+12%</span>
                        </div>
                    </div>
                </div> --}}

                @if ($currentUserId)
                    <!-- My Accepted Requests -->
                    <div class="stat-card success-stat animate__animated animate__zoomIn" style="animation-delay: 0.1s">
                        <div class="stat-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">{{ $stats['my_accepted'] }}</div>
                            <div class="stat-label">ที่คุณรับวันนี้</div>
                            <div class="stat-trend">
                                <i class="fas fa-trophy text-warning"></i>
                                <span class="text-muted">งานที่สำเร็จ</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Night Shift -->
                <div class="stat-card info-stat animate__animated animate__zoomIn" style="animation-delay: 0.2s">
                    <div class="stat-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $stats['night_shift'] }}</div>
                        <div class="stat-label">ดึก</div>
                        <div class="stat-time">00:00-07:59</div>
                    </div>
                </div>

                <!-- Morning Shift -->
                <div class="stat-card warning-stat animate__animated animate__zoomIn" style="animation-delay: 0.3s">
                    <div class="stat-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $stats['morning_shift'] }}</div>
                        <div class="stat-label">เช้า</div>
                        <div class="stat-time">08:00-16:00</div>
                    </div>
                </div>

                <!-- Afternoon Shift -->
                <div class="stat-card secondary-stat animate__animated animate__zoomIn" style="animation-delay: 0.4s">
                    <div class="stat-icon">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">{{ $stats['afternoon_shift'] }}</div>
                        <div class="stat-label">บ่าย</div>
                        <div class="stat-time">16:00-23:59</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Filter Section -->
  {{--   <section class="filter-section animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
        <div class="container">
            <div class="filter-card">
                <div class="filter-header">
                    <h4 class="filter-title">
                        <i class="fas fa-filter me-2"></i>ตัวกรองข้อมูล
                    </h4>
                </div>
                <div class="filter-body">
                    <div class="filter-controls">
                        <div class="filter-left">
                            <div class="form-check-enhanced">
                                <input class="form-check-input" type="checkbox" wire:model.live="hideCompleted" id="hideCompleted">
                                <label class="form-check-label" for="hideCompleted">
                                    <i class="fas fa-eye-slash me-2"></i>ซ่อนรายการที่สำเร็จแล้ว
                                </label>
                            </div>

                            @if ($currentUserId)
                                <div class="form-check-enhanced">
                                    <input class="form-check-input" type="checkbox" wire:model.live="showMyOnly" id="showMyOnly">
                                    <label class="form-check-label" for="showMyOnly">
                                        <i class="fas fa-user me-2"></i>แสดงเฉพาะของคุณ
                                    </label>
                                </div>
                            @endif
                        </div>

                        <div class="filter-right">
                            <div class="filter-actions">
                                <button type="button" class="btn btn-refresh" wire:click="loadData" 
                                        wire:loading.attr="disabled" wire:target="loadData">
                                    <span wire:loading.remove wire:target="loadData">
                                        <i class="fas fa-sync-alt me-2"></i>รีเฟรช
                                    </span>
                                    <span wire:loading wire:target="loadData">
                                        <i class="fas fa-spinner fa-spin me-2"></i>กำลังโหลด...
                                    </span>
                                </button>
                                <div class="realtime-status">
                                    <i class="fas fa-wifi me-1 text-success"></i>
                                    <span class="status-text">Real-time</span>
                                    <div class="status-divider">|</div>
                                    <span class="last-update">
                                        <i class="fas fa-clock me-1"></i>
                                        <span id="last-update">{{ now()->format('H:i:s') }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
 --}}
    <!-- Enhanced Stretcher Requests Grid -->
    <section class="stretcher-requests-section">
        <div class="container">
            <div class="section-header text-center mb-4">
                <h2 class="section-title">
                    <i class="fas fa-bed me-2"></i>รายการขอเปล
                </h2>
                <p class="section-subtitle">
                    รายการทั้งหมด {{ count($data) }} รายการ | 
                    อัปเดตแบบเรียลไทม์
                </p>
            </div>

            <div class="stretcher-grid" id="stretcher-requests-grid">
                @forelse($data as $index => $request)
                    <div class="stretcher-card-wrapper animate__animated animate__fadeInUp" 
                         style="animation-delay: {{ $index * 0.1 }}s">
                        <div class="stretcher-card {{ $this->getPriorityClass($request['stretcher_priority_name']) }} {{ $this->getUrgencyClass($request) }}" 
                             id="stretcher-item-{{ $request['stretcher_register_id'] }}"
                             data-request-id="{{ $request['stretcher_register_id'] }}"
                             data-status="{{ $request['stretcher_work_status_id'] }}"
                             data-team="{{ $request['stretcher_team_list_id'] ?? '' }}">

                            <!-- Enhanced Card Header -->
                            <div class="stretcher-card-header">
                                <div class="header-left">
                                    @php
                                        $statusConfig = [
                                            1 => ['class' => 'waiting', 'icon' => 'clock', 'text' => 'รอรับงาน'],
                                            2 => ['class' => 'accepted', 'icon' => 'hand-paper', 'text' => 'รับงานแล้ว'],
                                            3 => ['class' => 'progress', 'icon' => 'running', 'text' => 'กำลังดำเนินการ'],
                                            4 => ['class' => 'completed', 'icon' => 'check-circle', 'text' => 'สำเร็จ'],
                                            5 => ['class' => 'cancelled', 'icon' => 'times-circle', 'text' => 'อื่นๆ'],
                                        ];
                                        $status = $statusConfig[$request['stretcher_work_status_id']] ?? $statusConfig[1];
                                    @endphp

                                    <span class="status-badge status-{{ $status['class'] }}">
                                        <i class="fas fa-{{ $status['icon'] }}"></i>
                                        <span>{{ $status['text'] }}</span>
                                    </span>

                                    @if (in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']))
                                        <span class="priority-indicator urgent">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                    @endif
                                </div>

                                <div class="header-right">
                                    <span class="request-id">
                                        <i class="fas fa-hashtag"></i>{{ $request['stretcher_register_id'] }}
                                    </span>
                                </div>
                            </div>

                            <!-- Enhanced Card Body -->
                            <div class="stretcher-card-body">
                                <div class="patient-section">
                                    <div class="patient-header">
                                        <h5 class="patient-name">
                                            <i class="fas fa-user me-2"></i>
                                            {{ $request['pname'] }}{{ $request['fname'] }} {{ $request['lname'] }}
                                        </h5>
                                        <span class="patient-hn">HN: {{ $request['hn'] }}</span>
                                    </div>

                                    <div class="patient-info-grid">
                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-bed"></i>ประเภทเปล
                                            </span>
                                            <span class="info-value text-end">{{ $request['stretcher_type_name'] }}</span>
                                        </div>

                                        @if (!empty($request['stretcher_o2tube_type_name']))
                                            <div class="info-item">
                                                <span class="info-label">
                                                    <i class="fas fa-lungs"></i>ออกซิเจน
                                                </span>
                                                <span class="info-value text-end">{{ $request['stretcher_o2tube_type_name'] }}</span>
                                            </div>
                                        @endif

                                        @if (!empty($request['stretcher_emergency_name']))
                                            <div class="info-item emergency">
                                                <span class="info-label">
                                                    <i class="fas fa-exclamation-triangle"></i>ฉุกเฉิน
                                                </span>
                                                <span class="info-value text-end">{{ $request['stretcher_emergency_name'] }}</span>
                                            </div>
                                        @endif

                                        <div class="info-item {{ in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']) ? 'priority-urgent' : '' }}">
                                            <span class="info-label">
                                                <i class="fas fa-tachometer-alt"></i>ความเร่งด่วน
                                            </span>
                                            <span class="info-value text-end">
                                                {{ $request['stretcher_priority_name'] }}
                                                @if (in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']))
                                                    <i class="fas fa-fire ms-1"></i>
                                                @endif
                                            </span>
                                        </div>

                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-user-md"></i>ผู้ขอเปล
                                            </span>
                                            <span class="info-value text-end">{{ $request['dname'] }}</span>
                                        </div>

                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-map-marker-alt"></i>จากแผนก
                                            </span>
                                            <span class="info-value text-end">{{ $request['department'] }}</span>
                                        </div>

                                        <div class="info-item">
                                            <span class="info-label">
                                                <i class="fas fa-arrow-right"></i>ไปแผนก
                                            </span>
                                            <span class="info-value text-end">{{ $request['department2'] }}</span>
                                        </div>

                                        @if (!empty($request['from_note']))
                                            <div class="info-item note">
                                                <span class="info-label">
                                                    <i class="fas fa-sticky-note"></i>หมายเหตุ (1)
                                                </span>
                                                <span class="info-value text-end">{{ $request['from_note'] }}</span>
                                            </div>
                                        @endif

                                        @if (!empty($request['send_note']))
                                            <div class="info-item note">
                                                <span class="info-label">
                                                    <i class="fas fa-comment"></i>หมายเหตุ (2)
                                                </span>
                                                <span class="info-value text-end">{{ $request['send_note'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Card Footer -->
                            <div class="stretcher-card-footer">
                                <div class="footer-info">
                                    <div class="time-info">
                                        <i class="fas fa-clock"></i>
                                        <span class="time-text">
                                            {{ \Carbon\Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])->diffForHumans() }}
                                        </span>
                                    </div>

                                    @if (!empty($request['name']))
                                        <div class="team-info">
                                            <i class="fas fa-user-check"></i>
                                            <span class="team-name">{{ $request['name'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Enhanced Action Buttons -->
                                @if ($currentUserId && $userType === 'team_member')
                                    <div class="action-buttons">
                                        @if (empty($request['stretcher_team_list_id']) && $request['stretcher_work_status_id'] == 1)
                                            @if ($pendingWorkCount <= 0)
                                                <button type="button" class="btn btn-action btn-accept"
                                                        wire:click="acceptRequest({{ $request['stretcher_register_id'] }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="acceptRequest({{ $request['stretcher_register_id'] }})"
                                                        data-request-id="{{ $request['stretcher_register_id'] }}">
                                                    <span class="btn-content" wire:loading.remove wire:target="acceptRequest({{ $request['stretcher_register_id'] }})">
                                                        <i class="fas fa-hand-paper"></i>
                                                        <span>รับงาน</span>
                                                    </span>
                                                    <span class="btn-loading" wire:loading wire:target="acceptRequest({{ $request['stretcher_register_id'] }})">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                        <span>กำลังรับงาน...</span>
                                                    </span>
                                                </button>
                                            @else
                                                <button class="btn btn-action btn-disabled" disabled>
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    <span>คุณมีงานค้างอยู่</span>
                                                </button>
                                            @endif
                                        @elseif($request['stretcher_team_list_id'] == $currentUserId && $request['stretcher_work_status_id'] == 2)
                                            <button type="button" class="btn btn-action btn-progress"
                                                    wire:click="sendToPatient({{ $request['stretcher_register_id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="sendToPatient({{ $request['stretcher_register_id'] }})">
                                                <span class="btn-content" wire:loading.remove wire:target="sendToPatient({{ $request['stretcher_register_id'] }})">
                                                    <i class="fas fa-running"></i>
                                                    <span>ไปรับผู้ป่วย</span>
                                                </span>
                                                <span class="btn-loading" wire:loading wire:target="sendToPatient({{ $request['stretcher_register_id'] }})">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                    <span>กำลังอัปเดต...</span>
                                                </span>
                                            </button>
                                        @elseif($request['stretcher_team_list_id'] == $currentUserId && $request['stretcher_work_status_id'] == 3)
                                            <button type="button" class="btn btn-action btn-complete"
                                                    wire:click="completeTask({{ $request['stretcher_register_id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="completeTask({{ $request['stretcher_register_id'] }})">
                                                <span class="btn-content" wire:loading.remove wire:target="completeTask({{ $request['stretcher_register_id'] }})">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span>งานสำเร็จ</span>
                                                </span>
                                                <span class="btn-loading" wire:loading wire:target="completeTask({{ $request['stretcher_register_id'] }})">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                    <span>กำลังบันทึก...</span>
                                                </span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state animate__animated animate__fadeIn">
                        <div class="empty-state-content">
                            <div class="empty-state-icon">
                                <i class="fas fa-bed"></i>
                            </div>
                            <h4 class="empty-state-title">ไม่มีข้อมูลการขอเปล</h4>
                            <p class="empty-state-description">ยังไม่มีรายการขอเปลสำหรับวันนี้</p>
                            <button class="btn btn-primary" wire:click="loadData">
                                <i class="fas fa-refresh me-2"></i>รีเฟรชข้อมูล
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Enhanced Loading Indicator -->
            <div class="loading-section" wire:loading.flex wire:target="loadData">
                <div class="loading-content">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <p class="loading-text">กำลังโหลดข้อมูล...</p>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
    <script>
        // Enhanced Real-time Stretcher Management System with improved error handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Enhanced Stretcher Manager initializing...');

            // Helper method to get priority class
            window.getPriorityClass = function(priority) {
                const priorityClasses = {
                    'ด่วนที่สุด': 'priority-critical',
                    'ด่วน': 'priority-urgent', 
                    'ปกติ': 'priority-normal'
                };
                return priorityClasses[priority] || 'priority-normal';
            };

            // Helper method to get urgency class  
            window.getUrgencyClass = function(request) {
                if (request.stretcher_emergency_name || 
                    ['ด่วนที่สุด', 'ด่วน'].includes(request.stretcher_priority_name)) {
                    return 'urgent-request';
                }
                return '';
            };

            console.log('✅ Enhanced Stretcher Manager initialized successfully!');
        });
    </script>
@endpush

@push('styles')
    <style>
        /* ===================================================================
           🎨 Enhanced Stretcher Manager Styles
           =================================================================== */

        .stretcher-manager-container {
            min-height: 100vh;
        }

        /* Enhanced Notification Styles */
        .enhanced-notification {
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
        }

        .notification-content {
            border: none;
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            margin: 0;
        }

        .notification-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }

        .notification-body {
            flex: 1;
        }

        .notification-title {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .notification-timestamp {
            margin-top: 0.25rem;
        }

        /* Enhanced User Section */
        .user-section {
            padding: 2rem 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
        }

        .user-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .user-card-content {
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-avatar-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: var(--shadow-md);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .user-badges {
            display: flex;
            gap: 0.75rem;
        }

        .badge-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .badge-admin {
            background: var(--gradient-warning);
            color: white;
        }

        .badge-online {
            background: var(--gradient-success);
            color: white;
        }

        /* Enhanced Statistics Section */
        .statistics-section {
            padding: 3rem 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .section-header {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
        }

        .stat-card.main-stat::before {
            background: var(--gradient-primary);
        }

        .stat-card.success-stat::before {
            background: var(--gradient-success);
        }

        .stat-card.info-stat::before {
            background: var(--gradient-info);
        }

        .stat-card.warning-stat::before {
            background: var(--gradient-warning);
        }

        .stat-card.secondary-stat::before {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            opacity: 0.8;
            color: var(--primary-color);
        }

        .success-stat .stat-icon {
            color: var(--success-color);
        }

        .info-stat .stat-icon {
            color: var(--info-color);
        }

        .warning-stat .stat-icon {
            color: var(--warning-color);
        }

        .secondary-stat .stat-icon {
            color: #6b7280;
        }

        .stat-content {
            position: relative;
            z-index: 1;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        .stat-time {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Enhanced Filter Section */
        .filter-section {
            padding: 2rem 0;
        }

        .filter-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .filter-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .filter-body {
            padding: 2rem;
        }

        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .filter-left {
            display: flex;
            gap: 2rem;
        }

        .form-check-enhanced {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-check-enhanced .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 0.375rem;
        }

        .form-check-enhanced .form-check-label {
            font-weight: 500;
            color: var(--text-primary);
            margin: 0;
            cursor: pointer;
        }

        .filter-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .btn-refresh {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .realtime-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-divider {
            color: var(--text-muted);
            margin: 0 0.25rem;
        }

        /* Enhanced Stretcher Requests Section */
        .stretcher-requests-section {
            padding: 2rem 0 4rem;
        }

        .stretcher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .stretcher-card-wrapper {
            position: relative;
        }

        /* Enhanced Stretcher Card Styles */
        .stretcher-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all var(--transition-normal);
            overflow: hidden;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .stretcher-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-info);
            z-index: 1;
        }

        .stretcher-card.priority-critical::before {
            background: var(--gradient-danger);
            height: 6px;
            animation: pulse 2s infinite;
        }

        .stretcher-card.priority-urgent::before {
            background: var(--gradient-warning);
            height: 5px;
        }

        .stretcher-card.priority-normal::before {
            background: var(--gradient-info);
        }

        .stretcher-card.urgent-request {
            border: 2px solid var(--danger-color);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2), var(--shadow-lg);
        }

        .stretcher-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        /* Enhanced Card Header */
        .stretcher-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-2xl);
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-waiting {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }

        .status-accepted {
            background: var(--gradient-warning);
            color: white;
        }

        .status-progress {
            background: var(--gradient-info);
            color: white;
        }

        .status-completed {
            background: var(--gradient-success);
            color: white;
        }

        .status-cancelled {
            background: var(--gradient-danger);
            color: white;
        }

        .priority-indicator {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .priority-indicator.urgent {
            background: var(--gradient-danger);
            color: white;
            animation: pulse 2s infinite;
        }

        .request-id {
            background: rgba(0,0,0,0.05);
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Enhanced Card Body */
        .stretcher-card-body {
            padding: 2rem;
            flex: 1;
        }

        .patient-section {
            margin-bottom: 1.5rem;
        }

        .patient-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .patient-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .patient-hn {
            background: var(--gradient-primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-2xl);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .patient-info-grid {
            display: grid;
            gap: 1rem;
            
        }

        .info-item {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 0.75rem;
            align-items: start;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: var(--radius-md);
            border-left: 3px solid var(--primary-color);
        }

        .info-item.emergency {
            background: #fef2f2;
            border-left-color: var(--danger-color);
        }

        .info-item.priority-urgent {
            background: #fefce8;
            border-left-color: var(--warning-color);
        }

        .info-item.note {
            background: #f0f9ff;
            border-left-color: var(--info-color);
        }

        .info-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value {
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .emergency .info-value,
        .priority-urgent .info-value {
            color: var(--danger-color);
            font-weight: 700;
            display: flex;
            justify-content: end;
            align-items: center;
            gap: 0.25rem;
        }

        /* Enhanced Card Footer */
        .stretcher-card-footer {
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .time-info,
        .team-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .time-info {
            color: var(--text-secondary);
        }

        .team-info {
            color: var(--info-color);
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 48px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-accept {
            background: var(--gradient-success);
            color: white;
        }

        .btn-progress {
            background: var(--gradient-info);
            color: white;
        }

        .btn-complete {
            background: var(--gradient-success);
            color: white;
        }

        .btn-disabled {
            background: #6b7280;
            color: white;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .btn-content,
        .btn-loading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Enhanced Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .empty-state-content {
            max-width: 400px;
            margin: 0 auto;
        }

        .empty-state-icon {
            font-size: 5rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .empty-state-description {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Enhanced Loading Section */
        .loading-section {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-content {
            text-align: center;
            background: var(--card-bg);
            padding: 3rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
        }

        .loading-spinner {
            margin-bottom: 1.5rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .loading-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .stretcher-grid {
                grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .section-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .stretcher-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filter-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .filter-left {
                flex-direction: column;
                gap: 1rem;
            }

            .user-card-content {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .info-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .footer-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .stretcher-card-header,
            .stretcher-card-body,
            .stretcher-card-footer {
                padding: 1rem;
            }

            .patient-name {
                font-size: 1.1rem;
            }

            .status-badge {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .btn-action {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
@endpush