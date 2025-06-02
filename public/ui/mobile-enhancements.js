/**
 * Mobile Enhancement JavaScript for Stretcher Manager
 * ==================================================
 * เพิ่มไฟล์นี้ใน public/js/mobile-enhancements.js
 */

class MobileEnhancementManager {
    constructor() {
        this.isMobile = false;
        this.isTablet = false;
        this.touchStartY = 0;
        this.notificationQueue = [];
        this.init();
    }

    /**
     * Initialize mobile enhancements
     */
    init() {
        console.log('📱 Initializing Mobile Enhancements...');

        this.detectDevice();
        this.setupEventListeners();
        this.initializeMobileUI();
        this.setupTouchHandlers();
        this.initializeMobileNotifications();

        console.log('✅ Mobile Enhancements initialized successfully!');
    }

    /**
     * Detect if user is on mobile/tablet device
     */
    detectDevice() {
        const userAgent = navigator.userAgent.toLowerCase();
        const screenWidth = window.innerWidth;

        // Device detection
        this.isMobile = /mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i.test(userAgent) || screenWidth <= 768;
        this.isTablet = /tablet|ipad|android(?!.*mobile)/i.test(userAgent) || (screenWidth > 768 && screenWidth <= 1024);

        // Add classes to body
        document.body.classList.toggle('is-mobile', this.isMobile);
        document.body.classList.toggle('is-tablet', this.isTablet);
        document.body.classList.toggle('is-touch', 'ontouchstart' in window);

        console.log('📱 Device Detection:', {
            isMobile: this.isMobile,
            isTablet: this.isTablet,
            screenWidth: screenWidth,
            userAgent: userAgent
        });
    }

    /**
     * Setup event listeners for mobile
     */
    setupEventListeners() {
        // Window events
        window.addEventListener('resize', this.handleResize.bind(this));
        window.addEventListener('orientationchange', this.handleOrientationChange.bind(this));

        // Document events
        document.addEventListener('DOMContentLoaded', this.onDOMReady.bind(this));
      /*   document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this)); */

        // Prevent zoom on double tap
        this.preventDoubleTabZoom();

        // Handle form inputs
        this.setupFormHandlers();
    }

    /**
     * Initialize mobile UI enhancements
     */
    initializeMobileUI() {
        if (!this.isMobile) return;

        // Add mobile header
        this.addMobileHeader();

        // Transform existing UI elements
        this.transformFilters();
        this.optimizeCards();
        this.enhanceButtons();

        // Add mobile navigation
        this.addMobileNavigation();
    }

    /**
     * Add mobile header to existing layout
     */
    addMobileHeader() {
        const existingHeader = document.querySelector('.user-section, .hero-section');
        if (!existingHeader) return;

        const userName = document.querySelector('.user-name, .hero-title');
        const userRole = document.querySelector('.user-badges, .hero-subtitle');

        if (!userName) return;
        const rr = document.querySelector('.mobile-header');
        if (rr) return;

        const mobileHeader = document.createElement('div');
        mobileHeader.className = 'mobile-header mobile-only';
        mobileHeader.innerHTML = `
            <div class="mobile-header-content">
                <div class="mobile-user-info">
                    <h3>${userName.textContent.trim()}</h3>
                    <span class="mobile-user-role">
                        <i class="fas fa-user-md"></i>
                        ${userRole ? userRole.textContent.trim() : 'ทีมเปล'}
                    </span>
                </div>
                <button class="mobile-logout" onclick="handleMobileLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        `;

        // Insert at the beginning of body
        document.body.insertBefore(mobileHeader, document.body.firstChild);

        console.log('📱 Mobile header added');
    }

    /**
     * Transform filter controls for mobile
     */
    transformFilters() {
        if (!this.isMobile) return;

        const filterControls = document.querySelectorAll('.form-check-enhanced');
        filterControls.forEach(control => {
            this.transformToggleSwitch(control);
        });

        console.log('📱 Filter controls transformed');
    }

    /**
     * Transform checkbox to mobile toggle switch
     */
    transformToggleSwitch(control) {
        const checkbox = control.querySelector('input[type="checkbox"]');
        const label = control.querySelector('.form-check-label');

        if (!checkbox || !label) return;

        // Create toggle switch
        const toggleSwitch = document.createElement('div');
        toggleSwitch.className = 'mobile-toggle-switch';
        toggleSwitch.innerHTML = `
            <div class="toggle-track ${checkbox.checked ? 'active' : ''}">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-label">${label.textContent}</span>
        `;

        // Handle toggle click
        toggleSwitch.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            toggleSwitch.querySelector('.toggle-track').classList.toggle('active', checkbox.checked);
        });

        // Replace original control
        control.appendChild(toggleSwitch);
        control.style.display = 'flex';
        control.style.alignItems = 'center';
        control.style.gap = '8px';
    }

    /**
     * Optimize cards for mobile viewing
     */
    optimizeCards() {
        if (!this.isMobile) return;

        const cards = document.querySelectorAll('.stretcher-card');
        cards.forEach(card => {
            this.optimizeSingleCard(card);
        });

        console.log('📱 Cards optimized for mobile');
    }

    /**
     * Optimize single card for mobile
     */
    optimizeSingleCard(card) {
        // Add mobile-optimized classes
        card.classList.add('mobile-optimized');

        // Optimize patient info grid
        const infoGrid = card.querySelector('.patient-info-grid');
        if (infoGrid) {
            const infoItems = infoGrid.querySelectorAll('.info-item');
            infoItems.forEach(item => {
                this.optimizeInfoItem(item);
            });
        }

        // Add quick action indicator
        const actionButtons = card.querySelector('.action-buttons');
        if (actionButtons) {
            this.addQuickActionIndicator(actionButtons);
        }
    }

    /**
     * Optimize info item for mobile display
     */
    optimizeInfoItem(item) {
        const label = item.querySelector('.info-label');
        const value = item.querySelector('.info-value');

        if (!label || !value) return;

        // Shorten labels for mobile
        const shortLabels = {
            'ประเภทเปล': 'เปล',
            'ออกซิเจน': 'O2',
            'ความเร่งด่วน': 'ด่วน',
            'ผู้ขอเปล': 'ผู้ขอ',
            'จากแผนก': 'จาก',
            'ไปแผนก': 'ไป',
            'หมายเหตุ (1)': 'หมายเหตุ',
            'หมายเหตุ (2)': 'หมายเหตุ 2'
        };

        const labelText = label.textContent.trim();
        const shortLabel = shortLabels[labelText] || labelText;

        if (shortLabel !== labelText) {
            label.innerHTML = label.innerHTML.replace(labelText, shortLabel);
        }

        // Handle long values
       /*  if (value.textContent.length > 15) {
            value.title = value.textContent;
            value.textContent = value.textContent.substring(0, 15) + '...';
        } */
    }

    /**
     * Add quick action indicator for mobile
     */
    addQuickActionIndicator(actionButtons) {
        const acceptBtn = actionButtons.querySelector('.btn-accept');
        const progressBtn = actionButtons.querySelector('.btn-progress');
        const completeBtn = actionButtons.querySelector('.btn-complete');

        if (acceptBtn) {
            this.addRippleEffect(acceptBtn);
        }
        if (progressBtn) {
            this.addRippleEffect(progressBtn);
        }
        if (completeBtn) {
            this.addRippleEffect(completeBtn);
        }
    }

    /**
     * Add ripple effect to buttons
     */
    addRippleEffect(button) {
        button.addEventListener('touchstart', (e) => {
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.touches[0].clientX - rect.left - size / 2;
            const y = e.touches[0].clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.5);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            ripple.className = 'ripple-effect';
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }

    /**
     * Enhance buttons for mobile interaction
     */
    enhanceButtons() {
        if (!this.isMobile) return;

        const buttons = document.querySelectorAll('.btn, .btn-action');
        buttons.forEach(button => {
            // Add touch feedback
            button.addEventListener('touchstart', () => {
                button.style.transform = 'scale(0.95)';
            });

            button.addEventListener('touchend', () => {
                button.style.transform = '';
            });

            // Prevent accidental double taps
            let lastTap = 0;
            button.addEventListener('touchend', (e) => {
                const currentTime = new Date().getTime();
                const tapLength = currentTime - lastTap;
                if (tapLength < 500 && tapLength > 0) {
                    e.preventDefault();
                    return false;
                }
                lastTap = currentTime;
            });
        });

        console.log('📱 Buttons enhanced for mobile');
    }

    /**
     * Add mobile navigation if needed
     */
    addMobileNavigation() {
        // Could add bottom navigation bar for mobile if needed
        const tt = document.querySelector('.mobile-navigation');
        if (tt) return;
        const navigation = document.createElement('div');
        navigation.className = 'mobile-navigation mobile-only';
        navigation.innerHTML = `
            <div class="nav-item active" data-section="requests">
                <i class="fas fa-list"></i>
                <span>รายการ</span>
            </div>
            <div class="nav-item" data-section="stats">
                <i class="fas fa-chart-bar"></i>
                <span>สถิติ</span>
            </div>
            <div class="nav-item" data-section="refresh">
                <i class="fas fa-sync-alt"></i>
                <span>รีเฟรช</span>
            </div>
        `;

        // Add to bottom of page
        document.body.appendChild(navigation);

        // Handle navigation clicks
        navigation.addEventListener('click', (e) => {
            const navItem = e.target.closest('.nav-item');
            if (!navItem) return;

            const section = navItem.dataset.section;
            this.handleMobileNavigation(section);
        });

        console.log('📱 Mobile navigation added');
    }

    /**
     * Handle mobile navigation
     */
    handleMobileNavigation(section) {
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // Add active class to clicked item
        document.querySelector(`[data-section="${section}"]`).classList.add('active');

        switch (section) {
            case 'requests':
                this.scrollToSection('.stretcher-requests-section');
                break;
            case 'stats':
                this.scrollToSection('.statistics-section');
                break;
            case 'refresh':
                this.handleMobileRefresh();
                break;
        }
    }

    /**
     * Scroll to specific section
     */
    scrollToSection(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    /**
     * Handle mobile refresh
     */
    handleMobileRefresh() {
        // Show loading indication
        this.showMobileNotification('กำลังรีเฟรช...', 'info');

        // Call existing refresh function
        if (typeof window.refreshStretcherData === 'function') {
            window.refreshStretcherData();
        } else if (typeof $wire !== 'undefined') {
            $wire.dispatch('loadData');
        }

        // Haptic feedback if available
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    }

    /**
     * Setup touch handlers
     */
    setupTouchHandlers() {
        if (!this.isMobile) return;

        // Pull to refresh
        this.setupPullToRefresh();

        // Touch scrolling optimization
        this.optimizeTouchScrolling();

        console.log('📱 Touch handlers setup');
    }

    /**
     * Setup pull to refresh functionality
     */
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let pulling = false;

        document.addEventListener('touchstart', (e) => {
            startY = e.touches[0].pageY;
        });

        document.addEventListener('touchmove', (e) => {
            currentY = e.touches[0].pageY;

            if (window.pageYOffset === 0 && currentY > startY + 50) {
                pulling = true;
                this.showPullToRefreshIndicator();
            }
        });

        document.addEventListener('touchend', () => {
            if (pulling) {
                pulling = false;
                this.hidePullToRefreshIndicator();
                this.handleMobileRefresh();
            }
        });
    }

    /**
     * Show pull to refresh indicator
     */
    showPullToRefreshIndicator() {
        let indicator = document.querySelector('.pull-to-refresh-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'pull-to-refresh-indicator';
            indicator.innerHTML = `
                <div class="pull-indicator">
                    <i class="fas fa-arrow-down"></i>
                    <span>ปล่อยเพื่อรีเฟรช</span>
                </div>
            `;
            document.body.insertBefore(indicator, document.body.firstChild);
        }
        indicator.style.display = 'block';
    }

    /**
     * Hide pull to refresh indicator
     */
    hidePullToRefreshIndicator() {
        const indicator = document.querySelector('.pull-to-refresh-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    /**
     * Optimize touch scrolling
     */
    optimizeTouchScrolling() {
        // Add momentum scrolling
        document.body.style.webkitOverflowScrolling = 'touch';

        // Prevent elastic scrolling on body
        document.body.addEventListener('touchmove', (e) => {
            if (e.target === document.body) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    /**
     * Initialize mobile notifications
     */
    initializeMobileNotifications() {
        // Listen for existing Livewire events
        window.addEventListener('show-success', (e) => {
            this.showMobileNotification(e.detail.message, 'success');
        });

        window.addEventListener('show-error', (e) => {
            this.showMobileNotification(e.detail.message, 'error');
        });

        window.addEventListener('show-warning', (e) => {
            this.showMobileNotification(e.detail.message, 'warning');
        });

        console.log('📱 Mobile notifications initialized');
    }

    /**
     * Show mobile notification
     */
    showMobileNotification(message, type = 'info', duration = 3000) {
        if (!this.isMobile) return;

        const notification = document.createElement('div');
        notification.className = `mobile-notification ${type}`;

        const iconMap = {
            success: 'check',
            error: 'times',
            warning: 'exclamation-triangle',
            info: 'info'
        };

        notification.innerHTML = `
            <i class="fas fa-${iconMap[type]}"></i>
            <span>${message}</span>
            <button class="close-btn" onclick="this.parentElement.remove()">×</button>
        `;

        document.body.appendChild(notification);

        // Show animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Auto hide
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, duration);

        console.log('📱 Mobile notification shown:', message);
    }

    /**
     * Prevent double tap zoom
     */
    preventDoubleTabZoom() {
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }

    /**
     * Setup form handlers for mobile
     */
    setupFormHandlers() {
        // Prevent iOS zoom on input focus
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type !== 'checkbox' && input.type !== 'radio') {
                input.addEventListener('focus', () => {
                    if (this.isMobile) {
                        document.querySelector('meta[name=viewport]').setAttribute(
                            'content',
                            'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no'
                        );
                    }
                });

                input.addEventListener('blur', () => {
                    if (this.isMobile) {
                        document.querySelector('meta[name=viewport]').setAttribute(
                            'content',
                            'width=device-width, initial-scale=1, user-scalable=yes'
                        );
                    }
                });
            }
        });
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Re-detect device on resize
        this.detectDevice();

        // Re-initialize UI if switched to mobile
        if (this.isMobile) {
            this.initializeMobileUI();
        }
    }

    /**
     * Handle orientation change
     */
    handleOrientationChange() {
        setTimeout(() => {
            this.handleResize();
        }, 100);
    }

    /**
     * Handle DOM ready
     */
    onDOMReady() {
        // Additional mobile setup after DOM is ready
        if (this.isMobile) {
            this.addMobileSpecificStyles();
        }
    }

    /**
     * Add mobile-specific styles dynamically
     */
    addMobileSpecificStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .mobile-toggle-switch {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 0;
                cursor: pointer;
            }
            
            .toggle-track {
                width: 40px;
                height: 20px;
                background: #d1d5db;
                border-radius: 10px;
                position: relative;
                transition: 0.3s;
            }
            
            .toggle-track.active {
                background: #3b82f6;
            }
            
            .toggle-thumb {
                width: 16px;
                height: 16px;
                background: white;
                border-radius: 50%;
                position: absolute;
                top: 2px;
                left: 2px;
                transition: 0.3s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            }
            
            .toggle-track.active .toggle-thumb {
                transform: translateX(20px);
            }
            
            .toggle-label {
                font-size: 14px;
                font-weight: 500;
                color: #374151;
            }
            
            .mobile-navigation {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-top: 1px solid #e5e7eb;
                display: flex;
                z-index: 1000;
                box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
            }
            
            .nav-item {
                flex: 1;
                padding: 8px 4px;
                text-align: center;
                cursor: pointer;
                transition: 0.3s;
                font-size: 12px;
                color: #6b7280;
            }
            
            .nav-item.active {
                color: #3b82f6;
                background: #eff6ff;
            }
            
            .nav-item i {
                display: block;
                font-size: 16px;
                margin-bottom: 2px;
            }
            
            .pull-to-refresh-indicator {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #3b82f6;
                color: white;
                padding: 8px;
                text-align: center;
                font-size: 12px;
                z-index: 1001;
                transform: translateY(-100%);
                transition: 0.3s;
                display: none;
            }
            
            .pull-indicator {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Handle visibility change
     */
    /* handleVisibilityChange() {
        if (document.visibilityState === 'visible' && this.isMobile) {
            // Refresh data when app becomes visible
            setTimeout(() => {
                this.handleMobileRefresh();
            }, 1000);
        }
    } */
}

// Global functions for mobile
window.handleMobileLogout = function () {
    if (typeof $wire !== 'undefined') {
        $wire.logout();
    } else {
        window.location.href = '/logout';
    }
};

window.showMobileToast = function (message, type = 'info') {
    if (window.mobileManager) {
        window.mobileManager.showMobileNotification(message, type);
    }
};

// Initialize mobile enhancements when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    window.mobileManager = new MobileEnhancementManager();
});

// Export for other scripts
window.MobileEnhancementManager = MobileEnhancementManager;