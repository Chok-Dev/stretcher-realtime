{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'ศูนย์เปล - Dashboard')

@section('content')
    @include('sweetalert::alert')

    <!-- Hidden audio element for notifications -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('storage/sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('storage/sounds/notification.wav') }}" type="audio/wav">
    </audio>

    <div class="container-fluid">
        @if (!session()->has('name'))
            <script>
                window.location = "{{ route('login.form') }}";
            </script>
        @endif

        <livewire:stretcher-manager />
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🚀 Dashboard Real-time System Initializing...');

                // ===================================================================
                // 🎯 Livewire v3 Compatible Event Handling
                // ===================================================================

                // Initialize Laravel Echo for real-time updates
                if (window.Echo) {
                    console.log('📡 Setting up Echo listeners...');

                    // Subscribe to stretcher updates channel
                    window.Echo.channel('stretcher-updates')
                        .listen('.stretcher.updated', (e) => {
                            console.log('🔄 Stretcher updated:', e);

                            // Highlight updated item
                            highlightStretcherItem(e.stretcher_id, '#28a745');
                            
                            // Use Livewire v3 syntax
                            refreshLivewireComponent();
                            
                            // Show toast notification
                            showToast('อัปเดตสถานะ', `รายการ ID: ${e.stretcher_id} ได้รับการอัปเดต`, 'info');
                        })
                        .listen('.new.request', (e) => {
                            console.log('🔔 New request:', e);
                            
                            // Use Livewire v3 syntax
                            refreshLivewireComponent();
                            
                            // Play notification sound
                            playNotificationSound();

                            // Show notification
                            Swal.fire({
                                title: 'มีรายการขอเปลใหม่!',
                                html: `
                        <strong>HN:</strong> ${e.request.hn}<br>
                        <strong>ชื่อ-นามสกุล:</strong> ${e.request.pname}${e.request.fname} ${e.request.lname}<br>
                        <strong>ความเร่งด่วน:</strong> ${e.request.stretcher_priority_name}
                    `,
                                icon: 'info',
                                confirmButtonText: 'รับทราบ',
                                timer: 8000,
                                timerProgressBar: true,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            });
                        })
                        .listen('.status.changed', (e) => {
                            console.log('📊 Status changed:', e);

                            // Highlight item and show status change
                            highlightStretcherItem(e.stretcher_id, '#17a2b8');

                            const statusNames = {
                                1: 'รอรับงาน',
                                2: 'รับงานแล้ว',
                                3: 'กำลังดำเนินการ',
                                4: 'สำเร็จ',
                                5: 'ยกเลิก'
                            };

                            showToast('เปลี่ยนสถานะ',
                                `${e.team_member || 'ระบบ'} เปลี่ยนสถานะเป็น "${statusNames[e.new_status]}"`,
                                'success');
                        });
                }

                // ===================================================================
                // 🔄 Livewire v3 Component Refresh Functions
                // ===================================================================

                function refreshLivewireComponent() {
                    try {
                        console.log('🔄 Refreshing Livewire component...');
                        
                        // Method 1: Use $wire if available
                        if (typeof $wire !== 'undefined') {
                            $wire.dispatch('loadData');
                            console.log('✅ Dispatched via $wire');
                            return;
                        }
                        
                        // Method 2: Use Livewire.all() for v3
                        if (typeof Livewire !== 'undefined' && Livewire.all && Livewire.all().length > 0) {
                            const component = Livewire.all()[0];
                            if (component && component.call) {
                                component.call('forceRefresh');
                                console.log('✅ Called via Livewire.all()');
                                return;
                            }
                        }
                        
                        // Method 3: Use window.Livewire.dispatch for v3
                        if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                            Livewire.dispatch('refreshData');
                            console.log('✅ Dispatched via Livewire.dispatch');
                            return;
                        }
                        
                        // Method 4: Use Alpine.js if available
                        if (typeof Alpine !== 'undefined') {
                            Alpine.store('refresh', true);
                            console.log('✅ Triggered via Alpine');
                            return;
                        }
                        
                        console.warn('⚠️ No Livewire refresh method available');
                        
                    } catch (error) {
                        console.error('❌ Error refreshing component:', error);
                        
                        // Fallback: Page reload after delay
                        setTimeout(() => {
                            console.log('🔄 Fallback: Reloading page...');
                            window.location.reload();
                        }, 3000);
                    }
                }

                // ===================================================================
                // 🎵 Livewire v3 Event Listeners
                // ===================================================================

                window.addEventListener('show-success', event => {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: event.detail.message,
                        icon: 'success',
                        timer: 3000,
                        timerProgressBar: true
                    });
                });

                window.addEventListener('show-error', event => {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: event.detail.message,
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                });

                window.addEventListener('stretcher-updated', event => {
                    highlightStretcherItem(event.detail.stretcher_id, '#28a745');
                    refreshLivewireComponent(); // Use new function
                });

                window.addEventListener('new-request-received', event => {
                    playNotificationSound();
                    refreshLivewireComponent(); // Use new function

                    // Add pulsing effect to new items
                    setTimeout(() => {
                        const newItems = document.querySelectorAll('.stretcher-item:first-child');
                        newItems.forEach(item => {
                            item.classList.add('pulse');
                            setTimeout(() => item.classList.remove('pulse'), 3000);
                        });
                    }, 100);
                });

                window.addEventListener('status-changed', event => {
                    highlightStretcherItem(event.detail.stretcher_id, '#17a2b8');
                    refreshLivewireComponent(); // Use new function
                });

                // Listen for custom refresh events
                window.addEventListener('force-refresh', event => {
                    console.log('🔄 Force refresh event received');
                    refreshLivewireComponent();
                });

                // ===================================================================
                // 🎨 Visual Effects Functions
                // ===================================================================

                function highlightStretcherItem(stretcherId, color) {
                    const item = document.getElementById('stretcher-item-' + stretcherId);
                    if (item) {
                        const originalBorder = item.style.border;
                        const originalBackground = item.style.backgroundColor;

                        item.style.border = `3px solid ${color}`;
                        item.style.backgroundColor = color + '20'; // Add transparency

                        setTimeout(() => {
                            item.style.border = originalBorder;
                            item.style.backgroundColor = originalBackground;
                        }, 3000);
                    } else {
                        console.warn(`⚠️ Stretcher item ${stretcherId} not found`);
                    }
                }

                function playNotificationSound() {
                    const audio = document.getElementById('notification-sound');
                    if (audio) {
                        audio.currentTime = 0; // Reset to beginning
                        audio.play().catch(e => console.log('Could not play notification sound:', e));
                    }
                }

                function showToast(title, message, type = 'info') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
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

                // ===================================================================
                // 🌐 Global Functions
                // ===================================================================

                // Make refresh function globally available
                window.refreshStretcherData = refreshLivewireComponent;
                
                // Debug function
                window.debugLivewire = function() {
                    console.log('=== 🔍 Livewire Debug Info ===');
                    console.log('Livewire available:', typeof Livewire !== 'undefined');
                    console.log('$wire available:', typeof $wire !== 'undefined');
                    console.log('Alpine available:', typeof Alpine !== 'undefined');
                    
                    if (typeof Livewire !== 'undefined') {
                        console.log('Livewire.all():', Livewire.all ? Livewire.all().length : 'Not available');
                        console.log('Livewire.dispatch:', typeof Livewire.dispatch);
                    }
                    
                    console.log('=========================');
                };

                // Auto-refresh connection status with Livewire v3 compatibility
                setInterval(() => {
                    if (window.Echo && window.Echo.connector.pusher.connection.state !== 'connected') {
                        console.log('🔌 Reconnecting to WebSocket...');
                        window.Echo.connector.pusher.connect();
                    }
                }, 30000); // Check every 30 seconds

                console.log('✅ Dashboard Real-time System initialized successfully!');
            });
        </script>
    @endpush
@endsection