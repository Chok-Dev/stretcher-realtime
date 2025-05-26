@extends('layouts.app')

@section('title', 'แดชบอร์ด - ศูนย์เปล')

@section('content')
<div class="container">
    <livewire:stretcher-dashboard />
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🏗️ Dashboard page loaded');
        
        // Listen for Livewire events from the component
        if (window.Livewire) {
            // Success/Error messages
            window.Livewire.on('al-success', (event) => {
                console.log('Success event:', event);
                if (window.stretcherUtils) {
                    window.stretcherUtils.showToast('สำเร็จ', event.message || 'ดำเนินการสำเร็จ', 'success');
                }
            });

            window.Livewire.on('al-error', (event) => {
                console.log('Error event:', event);
                if (window.stretcherUtils) {
                    window.stretcherUtils.showToast('ข้อผิดพลาด', event.message || 'เกิดข้อผิดพลาด', 'error');
                }
            });

            // ฟัง events ที่ได้รับจาก Livewire Echo
            window.Livewire.on('stretcher-data-updated', (event) => {
                console.log('📨 Stretcher data updated via Livewire Echo:', event);
                
                // Handle notifications ตาม action
                if (window.handleStretcherNotification) {
                    window.handleStretcherNotification(event);
                }
            });

            // Specific dashboard events
            window.Livewire.on('new-stretcher-request', (event) => {
                console.log('🔔 New stretcher request:', event);
                
                if (window.stretcherUtils) {
                    window.stretcherUtils.playNotificationSound();
                    
                    if (window.Swal && event.stretcher) {
                        window.Swal.fire({
                            title: '🔔 มีรายการขอเปลใหม่!',
                            html: `
                                <div class="text-start">
                                    <strong>HN:</strong> ${event.stretcher.hn}<br>
                                    <strong>ชื่อ:</strong> ${event.stretcher.pname}${event.stretcher.fname} ${event.stretcher.lname}<br>
                                    <strong>จาก:</strong> ${event.stretcher.department}<br>
                                    <strong>ไป:</strong> ${event.stretcher.department2}<br>
                                    <strong>ความเร่งด่วน:</strong> <span class="text-danger">${event.stretcher.stretcher_priority_name}</span>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonText: 'รับทราบ',
                            timer: 15000,
                            timerProgressBar: true,
                            showClass: {
                                popup: 'animate__animated animate__bounceIn'
                            }
                        });
                    }
                }
            });

            window.Livewire.on('stretcher-accepted', (event) => {
                console.log('✅ Stretcher accepted:', event);
                if (window.stretcherUtils && event.team_name && event.stretcher) {
                    window.stretcherUtils.showToast(
                        '✅ มีคนรับงานแล้ว',
                        `${event.team_name} รับงาน HN: ${event.stretcher.hn}`,
                        'success',
                        4000
                    );
                }
            });

            window.Livewire.on('stretcher-sent', (event) => {
                console.log('🚗 Stretcher sent:', event);
                if (window.stretcherUtils && event.stretcher) {
                    window.stretcherUtils.showToast(
                        '🚗 เริ่มปฏิบัติงาน',
                        `HN: ${event.stretcher.hn} - ไปรับผู้ป่วย`,
                        'info'
                    );
                }
            });

            window.Livewire.on('stretcher-completed', (event) => {
                console.log('🎉 Stretcher completed:', event);
                if (window.stretcherUtils && event.stretcher) {
                    window.stretcherUtils.showToast(
                        '🎉 งานสำเร็จ!',
                        `HN: ${event.stretcher.hn} - เสร็จสิ้น`,
                        'success'
                    );
                }
            });
        }

        // Debug function for this page
        window.debugDashboard = function() {
            console.log('=== Dashboard Debug ===');
            console.log('Livewire available:', !!window.Livewire);
            console.log('Echo available:', !!window.Echo);
            console.log('Utils available:', !!window.stretcherUtils);
            
            if (window.Echo) {
                console.log('Echo connection state:', window.Echo.connector?.pusher?.connection?.state);
            }
            
            if (window.Livewire) {
                console.log('Testing manual refresh...');
                window.Livewire.dispatch('refreshData');
            }
        };

        console.log('✅ Dashboard event listeners setup. Use debugDashboard() for testing.');
    });
</script>
@endpush