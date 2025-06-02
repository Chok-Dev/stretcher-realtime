/**
 * Enhanced Stretcher Management System JavaScript
 * ================================================
 * Advanced real-time features with beautiful animations and smooth UX
 */

class EnhancedStretcherManager {
    constructor() {
        this.connectionState = {
            isConnected: false,
            reconnectAttempts: 0,
            maxReconnectAttempts: 5,
            lastHeartbeat: Date.now(),
            connectionId: null
        };

        this.uiState = {
            currentFilter: 'all',
            sortBy: 'time',
            viewMode: 'grid',
            autoRefreshEnabled: true,
            soundEnabled: true,
            darkMode: false
        };

        this.animationQueues = {
            notifications: [],
            highlights: [],
            updates: []
        };

        this.init();
    }

    /**
     * Initialize the Enhanced Stretcher Manager
     */
    init() {
        console.log('🚀 Initializing Enhanced Stretcher Manager...');
        
        this.setupEventListeners();
        this.initializeEcho();
        this.setupAutoRefresh();
        this.loadUserPreferences();
        this.setupKeyboardShortcuts();
        this.initializeTooltips();
        this.setupIntersectionObserver();
        
        console.log('✅ Enhanced Stretcher Manager initialized successfully!');
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // DOM Content Loaded Events
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeUI();
          /*   this.setupCustomEvents(); */
        });

        // Window Events
        window.addEventListener('online', () => this.handleConnectionChange(true));
        window.addEventListener('offline', () => this.handleConnectionChange(false));
        window.addEventListener('beforeunload', () => this.cleanup());
        window.addEventListener('visibilitychange', () => this.handleVisibilityChange());

        // Custom Stretcher Events
        this.setupStretcherEvents();
    }

    /**
     * Setup custom stretcher-specific events
     */
    setupStretcherEvents() {
        // Enhanced Success Events
        window.addEventListener('stretcher-request-accepted', (e) => {
            this.handleRequestAccepted(e.detail);
        });

        window.addEventListener('stretcher-status-updated', (e) => {
            this.handleStatusUpdate(e.detail);
        });

        window.addEventListener('new-urgent-request', (e) => {
            this.handleUrgentRequest(e.detail);
        });

        // Data Events
        window.addEventListener('data-loaded', (e) => {
            this.handleDataLoaded(e.detail);
        });

        window.addEventListener('filter-changed', (e) => {
            this.handleFilterChange(e.detail);
        });
    }

    /**
     * Initialize Laravel Echo with enhanced error handling
     */
    initializeEcho() {
        if (typeof window.Echo === 'undefined') {
            console.warn('⚠️ Laravel Echo not available');
            this.updateConnectionStatus('no-echo');
            return;
        }

        console.log('📡 Setting up Laravel Echo connection...');
        this.updateConnectionStatus('connecting');

        try {
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;

                // Enhanced connection handlers
                pusher.connection.bind('connected', () => {
                    console.log('✅ Echo connected successfully');
                    this.connectionState.isConnected = true;
                    this.connectionState.lastHeartbeat = Date.now();
                    this.connectionState.reconnectAttempts = 0;
                    this.connectionState.connectionId = pusher.connection.socket_id;
                    this.updateConnectionStatus('connected');
                    this.showConnectionToast('เชื่อมต่อสำเร็จ', 'ระบบพร้อมใช้งาน', 'success');
                });

                pusher.connection.bind('disconnected', () => {
                    console.log('❌ Echo disconnected');
                    this.connectionState.isConnected = false;
                    this.updateConnectionStatus('disconnected');
                    this.showConnectionToast('ขาดการเชื่อมต่อ', 'กำลังพยายามเชื่อมต่อใหม่', 'warning');
                    this.attemptReconnection();
                });

                pusher.connection.bind('error', (error) => {
                    console.error('❌ Echo connection error:', error);
                    this.connectionState.isConnected = false;
                    this.updateConnectionStatus('error');
                    this.showConnectionToast('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อได้', 'error');
                });

                // Subscribe to channels
                this.subscribeToChannels();
            }
        } catch (error) {
            console.error('❌ Failed to initialize Echo:', error);
            this.updateConnectionStatus('error');
        }
    }

    /**
     * Subscribe to all required channels
     */
    subscribeToChannels() {
        try {
            // Main stretcher updates channel
            window.Echo.channel('stretcher-updates')
                .listen('.stretcher.updated', (e) => this.handleStretcherUpdate(e))
                .listen('.new.request', (e) => this.handleNewRequest(e))
                .listen('.status.changed', (e) => this.handleStatusChange(e))
                .listen('.team.assigned', (e) => this.handleTeamAssignment(e))
                .listen('.request.cancelled', (e) => this.handleRequestCancellation(e));

            // User-specific channel
            if (this.getCurrentUserId()) {
                window.Echo.private(`user.${this.getCurrentUserId()}`)
                    .listen('.notification.personal', (e) => this.handlePersonalNotification(e))
                    .listen('.workload.updated', (e) => this.handleWorkloadUpdate(e));
            }

            console.log('✅ Successfully subscribed to all channels');
        } catch (error) {
            console.error('❌ Failed to subscribe to channels:', error);
        }
    }

    /**
     * Handle stretcher update events
     */
    handleStretcherUpdate(event) {
        console.log('🔄 Stretcher updated:', event);
        
        const { stretcher_id, action, team_member, timestamp } = event;
        
        // Visual highlight
        this.highlightStretcherItem(stretcher_id, '#10b981', 'update');
        
        // Show notification
        this.showFloatingNotification(
            'อัปเดตสถานะ', 
            `รายการ ${stretcher_id} ได้รับการอัปเดต`, 
            'info'
        );
        
        // Refresh data
     /*    this.scheduleDataRefresh(1000); */
        
        // Update statistics
        this.updateStatistics();
    }

    /**
     * Handle new request events
     */
    handleNewRequest(event) {
        console.log('🔔 New request received:', event);
        
        const { request } = event;
        
        // Play notification sound
        this.playNotificationSound('new-request');
        
        // Show prominent notification for urgent requests
        if (['ด่วนที่สุด', 'ด่วน'].includes(request.stretcher_priority_name)) {
            this.showUrgentRequestModal(request);
        } else {
            this.showNewRequestNotification(request);
        }
        
        // Add pulsing effect to new items
      /*   this.scheduleDataRefresh(500, () => {
            this.addPulseEffectToNewItems();
        }); */
        
        // Vibrate device if supported
        this.vibrateDevice([200, 100, 200]);
    }

    /**
     * Handle status change events
     */
    handleStatusChange(event) {
        console.log('📊 Status changed:', event);
        
        const { stretcher_id, new_status, old_status, team_member } = event;
        
        const statusColors = {
            1: '#6b7280', // waiting
            2: '#f59e0b', // accepted
            3: '#06b6d4', // in progress
            4: '#10b981', // completed
            5: '#ef4444'  // cancelled
        };
        
        const statusNames = {
            1: 'รอรับงาน',
            2: 'รับงานแล้ว',
            3: 'กำลังดำเนินการ',
            4: 'สำเร็จ',
            5: 'อื่นๆ'
        };
        
        // Highlight with appropriate color
        this.highlightStretcherItem(stretcher_id, statusColors[new_status], 'status-change');
        
        // Show status change notification
        this.showStatusChangeNotification(
            stretcher_id,
            statusNames[old_status],
            statusNames[new_status],
            team_member
        );
        
        // Play appropriate sound
        if (new_status === 4) { // completed
            this.playNotificationSound('success');
        } else if (new_status === 5) { // cancelled
            this.playNotificationSound('error');
        } else {
            this.playNotificationSound('status-change');
        }
        
      /*   this.scheduleDataRefresh(1500); */
    }

    /**
     * Enhanced highlight function with animations
     */
    highlightStretcherItem(stretcherId, color = '#10b981', type = 'default', duration = 4000) {
        const item = document.getElementById(`stretcher-item-${stretcherId}`);
        if (!item) {
            console.warn(`⚠️ Stretcher item ${stretcherId} not found`);
            return;
        }

        // Store original styles
        const originalStyle = {
            border: item.style.border,
            backgroundColor: item.style.backgroundColor,
            boxShadow: item.style.boxShadow,
            transform: item.style.transform
        };

        // Animation classes based on type
        const animationClasses = {
            'update': 'animate__animated animate__pulse',
            'status-change': 'animate__animated animate__bounceIn',
            'new-request': 'animate__animated animate__jackInTheBox',
            'urgent': 'animate__animated animate__flash animate__infinite',
            'default': 'animate__animated animate__pulse'
        };

        // Apply highlight with enhanced effects
        item.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        item.style.border = `3px solid ${color}`;
        item.style.backgroundColor = `${color}15`;
        item.style.boxShadow = `
            0 0 30px ${color}40,
            0 8px 16px rgba(0,0,0,0.1),
            inset 0 1px 0 rgba(255,255,255,0.2)
        `;
        item.style.transform = 'translateY(-6px) scale(1.02)';

        // Add animation class
        const animationClass = animationClasses[type] || animationClasses['default'];
        item.className += ` ${animationClass}`;

        // Add glow effect for urgent items
        if (type === 'urgent') {
            item.style.animation = 'glow 2s ease-in-out infinite alternate';
        }

        // Smooth scroll to item
        this.smoothScrollToElement(item);

        // Schedule highlight removal
        setTimeout(() => {
            this.removeHighlight(item, originalStyle, animationClass);
        }, duration);

        // Add to animation queue for coordination
        this.animationQueues.highlights.push({
            element: item,
            startTime: Date.now(),
            duration: duration,
            type: type
        });
    }

    /**
     * Remove highlight with smooth transition
     */
    removeHighlight(item, originalStyle, animationClass) {
        // Remove animation classes
        item.className = item.className.replace(animationClass, '').trim();
        
        // Restore original styles with smooth transition
        Object.keys(originalStyle).forEach(key => {
            item.style[key] = originalStyle[key];
        });
        
        // Remove any custom animations
        item.style.animation = '';
        
        // Remove transition after animation
        setTimeout(() => {
            item.style.transition = '';
        }, 300);
    }

    /**
     * Enhanced notification sound system
     */
    playNotificationSound(type = 'default') {
        if (!this.uiState.soundEnabled) return;

        const audio = document.getElementById('notification-sound');
        if (!audio) return;

        // Sound variations based on type
        const soundConfig = {
            'new-request': { volume: 0.8, playbackRate: 1.0 },
            'urgent': { volume: 1.0, playbackRate: 1.2 },
            'success': { volume: 0.6, playbackRate: 0.8 },
            'error': { volume: 0.9, playbackRate: 1.1 },
            'status-change': { volume: 0.7, playbackRate: 0.9 },
            'default': { volume: 0.7, playbackRate: 1.0 }
        };

        const config = soundConfig[type] || soundConfig['default'];

        audio.currentTime = 0;
        audio.volume = config.volume;
        audio.playbackRate = config.playbackRate;

        const playPromise = audio.play();
        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    console.log(`🔊 Played ${type} notification sound`);
                })
                .catch(error => {
                    console.log('🔇 Could not play notification sound:', error.message);
                    this.requestAudioPermission();
                });
        }
    }

    /**
     * Request audio permission on user interaction
     */
    requestAudioPermission() {
        document.addEventListener('click', this.enableAudio.bind(this), { once: true });
        document.addEventListener('keydown', this.enableAudio.bind(this), { once: true });
    }

    /**
     * Enable audio for future notifications
     */
    enableAudio() {
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

    /**
     * Enhanced toast notification system
     */
    showFloatingNotification(title, message, type = 'info', duration = 4000) {
        if (typeof Swal === 'undefined') {
            this.showFallbackNotification(title, message, type);
            return;
        }

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
                toast.addEventListener('click', () => Swal.close());
            },
            customClass: {
                popup: 'enhanced-toast-popup',
                title: 'enhanced-toast-title',
                content: 'enhanced-toast-content'
            }
        });

        Toast.fire({
            icon: iconMap[type] || 'info',
            title: title,
            text: message,
            background: '#ffffff',
            color: '#1e293b',
            iconColor: this.getIconColor(type)
        });
    }

    /**
     * Show urgent request modal
     */
    showUrgentRequestModal(request) {
        if (typeof Swal === 'undefined') return;

        // Play urgent sound repeatedly
        this.playNotificationSound('urgent');
        setTimeout(() => this.playNotificationSound('urgent'), 1000);
        setTimeout(() => this.playNotificationSound('urgent'), 2000);

        Swal.fire({
            title: '🚨 รายการขอเปลด่วน!',
            html: this.buildRequestModalContent(request),
            icon: 'warning',
            confirmButtonText: '🫡 รับทราบและดำเนินการ',
            cancelButtonText: '👀 รับทราบ',
            showCancelButton: true,
            timer: 15000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showClass: {
                popup: 'animate__animated animate__bounceIn animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            },
            customClass: {
                popup: 'urgent-request-modal',
                title: 'urgent-modal-title',
                content: 'urgent-modal-content',
                confirmButton: 'btn btn-danger btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            backdrop: `
                rgba(220, 38, 38, 0.4)
                url("/images/spinner.gif")
                left top
                no-repeat
            `
        }).then((result) => {
            if (result.isConfirmed) {
                this.scrollToStretcherItem(request.stretcher_register_id);
                this.highlightStretcherItem(request.stretcher_register_id, '#ef4444', 'urgent', 8000);
            }
        });

        // Vibrate device for urgent requests
        this.vibrateDevice([300, 200, 300, 200, 300]);
    }

    /**
     * Build request modal content
     */
    buildRequestModalContent(request) {
        return `
            <div class="urgent-request-content">
                <div class="patient-info-section">
                    <div class="info-row urgent">
                        <span class="label">🏥 HN:</span>
                        <span class="value">${request.hn}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">👤 ชื่อ-นามสกุล:</span>
                        <span class="value">${request.pname}${request.fname} ${request.lname}</span>
                    </div>
                    <div class="info-row critical">
                        <span class="label">⚡ ความเร่งด่วน:</span>
                        <span class="value">${request.stretcher_priority_name}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">🏠 จากแผนก:</span>
                        <span class="value">${request.department || 'ไม่ระบุ'}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">🎯 ไปแผนก:</span>
                        <span class="value">${request.department2 || 'ไม่ระบุ'}</span>
                    </div>
                    ${request.from_note ? `
                    <div class="info-row note">
                        <span class="label">📝 หมายเหตุ:</span>
                        <span class="value">${request.from_note}</span>
                    </div>
                    ` : ''}
                </div>
                <div class="urgency-indicator">
                    <div class="pulse-ring"></div>
                    <div class="pulse-ring delay-1"></div>
                    <div class="pulse-ring delay-2"></div>
                </div>
            </div>
        `;
    }

    /**
     * Enhanced data refresh with smart scheduling
     */
    /* scheduleDataRefresh(delay = 1000, callback = null) {
        // Clear any existing refresh timeout
        if (this.refreshTimeout) {
            clearTimeout(this.refreshTimeout);
        }

        this.refreshTimeout = setTimeout(() => {
            this.refreshLivewireComponent();
            if (callback) callback();
        }, delay);
    }
 */
    /**
     * Enhanced Livewire component refresh
     */
    refreshLivewireComponent() {
        console.log('🔄 Enhanced Livewire component refresh...');

        try {
            let refreshed = false;

            // Try different Livewire refresh methods (v3 compatible)
            const methods = [
                () => {
                    if (typeof $wire !== 'undefined') {
                        $wire.dispatch('loadData');
                        return true;
                    }
                    return false;
                },
                () => {
                    if (typeof Livewire !== 'undefined' && Livewire.all && Livewire.all().length > 0) {
                        const component = Livewire.all()[0];
                        if (component && component.call) {
                            component.call('forceRefresh');
                            return true;
                        }
                    }
                    return false;
                },
                () => {
                    if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                        Livewire.dispatch('refreshData');
                        return true;
                    }
                    return false;
                }
            ];

            for (const method of methods) {
                if (method()) {
                    refreshed = true;
                    break;
                }
            }

            if (refreshed) {
                this.updateLastUpdateTime();
                this.showFloatingNotification('อัปเดตข้อมูล', 'ข้อมูลได้รับการอัปเดตแล้ว', 'info', 2000);
            } else {
                throw new Error('ไม่สามารถเรียกใช้ฟังก์ชัน refresh ได้');
            }

        } catch (error) {
            console.error('❌ Error refreshing component:', error);
            this.handleRefreshError();
        }
    }

    /**
     * Handle refresh errors gracefully
     */
    handleRefreshError() {
        this.showFloatingNotification('ข้อผิดพลาด', 'ไม่สามารถอัปเดตข้อมูลได้', 'error');

        // Show user-friendly error dialog
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถอัปเดตข้อมูลได้ ต้องการรีโหลดหน้าเว็บไหม?',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'รีโหลด',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        }
    }

    /**
     * Update last update time with animation
     */
    updateLastUpdateTime() {
        const timeElement = document.getElementById('last-update');
        if (timeElement) {
            const now = new Date();
            timeElement.textContent = now.toLocaleTimeString('th-TH');
            
            // Add update animation
            timeElement.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                timeElement.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        }
    }

    /**
     * Smooth scroll to element
     */
    smoothScrollToElement(element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest'
        });
    }

    /**
     * Scroll to specific stretcher item
     */
    scrollToStretcherItem(stretcherId) {
        const item = document.getElementById(`stretcher-item-${stretcherId}`);
        if (item) {
            this.smoothScrollToElement(item);
        }
    }

    /**
     * Device vibration support
     */
    vibrateDevice(pattern) {
        if ('vibrate' in navigator) {
            navigator.vibrate(pattern);
        }
    }

    /**
     * Get icon color based on type
     */
    getIconColor(type) {
        const colors = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#06b6d4'
        };
        return colors[type] || colors['info'];
    }

    /**
     * Update connection status indicator
     */
    updateConnectionStatus(status) {
        const indicator = document.getElementById('connection-indicator');
        if (!indicator) return;

        const icon = indicator.querySelector('.connection-icon');
        const text = indicator.querySelector('.connection-text');

        // Remove all status classes
        indicator.classList.remove('status-connected', 'status-connecting', 'status-disconnected', 'status-error');

        const statusConfig = {
            'connected': {
                class: 'status-connected',
                icon: 'fas fa-wifi',
                text: 'เชื่อมต่อสำเร็จ'
            },
            'connecting': {
                class: 'status-connecting',
                icon: 'fas fa-spinner fa-spin',
                text: 'กำลังเชื่อมต่อ...'
            },
            'disconnected': {
                class: 'status-disconnected',
                icon: 'fas fa-wifi-slash',
                text: 'ขาดการเชื่อมต่อ'
            },
            'error': {
                class: 'status-error',
                icon: 'fas fa-exclamation-triangle',
                text: 'เกิดข้อผิดพลาด'
            },
            'no-echo': {
                class: 'status-error',
                icon: 'fas fa-times-circle',
                text: 'ไม่พบ Echo'
            }
        };

        const config = statusConfig[status] || statusConfig['error'];

        indicator.classList.add(config.class);
        if (icon) icon.className = config.icon + ' connection-icon';
        if (text) text.textContent = config.text;
    }

    /**
     * Show connection toast
     */
    showConnectionToast(title, message, type) {
        // Only show if this is a significant change
        if (this.lastConnectionStatus !== type) {
            this.showFloatingNotification(title, message, type, 3000);
            this.lastConnectionStatus = type;
        }
    }

    /**
     * Get current user ID
     */
    getCurrentUserId() {
        // This should be implemented based on your session/authentication system
        return window.currentUserId || null;
    }

    /**
     * Load user preferences from localStorage
     */
    loadUserPreferences() {
        const saved = localStorage.getItem('stretcher-preferences');
        if (saved) {
            try {
                const preferences = JSON.parse(saved);
                this.uiState = { ...this.uiState, ...preferences };
            } catch (error) {
                console.warn('Could not load user preferences:', error);
            }
        }
    }

    /**
     * Save user preferences to localStorage
     */
    saveUserPreferences() {
        try {
            localStorage.setItem('stretcher-preferences', JSON.stringify(this.uiState));
        } catch (error) {
            console.warn('Could not save user preferences:', error);
        }
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R: Refresh data
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.refreshLivewireComponent();
            }

            // Ctrl/Cmd + F: Focus filter
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const filterInput = document.querySelector('input[type="search"]');
                if (filterInput) filterInput.focus();
            }

            // Escape: Close modals/notifications
            if (e.key === 'Escape') {
                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                    Swal.close();
                }
            }
        });
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        // Initialize Bootstrap tooltips if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    /**
     * Setup intersection observer for animations
     */
    setupIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        // Observe all stretcher cards
        document.querySelectorAll('.stretcher-card').forEach((card) => {
            observer.observe(card);
        });
    }

    /**
     * Setup auto-refresh
     */
    setupAutoRefresh() {
        if (this.uiState.autoRefreshEnabled) {
            setInterval(() => {
                if (document.visibilityState === 'visible' && this.connectionState.isConnected) {
                    console.log('🕐 Auto-refresh triggered');
                    this.refreshLivewireComponent();
                }
            }, 120000); // Every 2 minutes
        }
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            // Page became visible, refresh data
           /*  this.scheduleDataRefresh(1000); */
        }
    }

    /**
     * Initialize UI components
     */
    initializeUI() {
        this.updateLastUpdateTime();
        this.setupIntersectionObserver();
        this.initializeTooltips();
    }

    /**
     * Cleanup resources
     */
    cleanup() {
        if (this.refreshTimeout) {
            clearTimeout(this.refreshTimeout);
        }
        this.saveUserPreferences();
    }
}

// Initialize the Enhanced Stretcher Manager
window.enhancedStretcherManager = new EnhancedStretcherManager();

// Expose useful functions globally
window.refreshStretcherData = () => window.enhancedStretcherManager.refreshLivewireComponent();
window.toggleSound = () => {
    window.enhancedStretcherManager.uiState.soundEnabled = !window.enhancedStretcherManager.uiState.soundEnabled;
    window.enhancedStretcherManager.saveUserPreferences();
};

// Legacy compatibility
window.reconnectWebSocket = () => window.enhancedStretcherManager.initializeEcho();
window.debugLivewire = () => console.log('Enhanced Stretcher Manager Debug:', window.enhancedStretcherManager);