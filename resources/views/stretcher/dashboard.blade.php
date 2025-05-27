{{-- resources/views/stretcher/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'แดชบอร์ด - ศูนย์เปล')

@section('content')
<div class="container">
    <div id="dashboard-container">
        <!-- Header -->
        <div class="row align-items-center mb-4">
            <div class="col">
                <h1 class="text-primary mb-0">
                    <i class="fas fa-bed me-2"></i>
                    ศูนย์เปล
                </h1>
                <a href="https://lookerstudio.google.com/reporting/65943f17-df0d-4aab-89d4-e11ed86171b6" 
                   target="_blank" 
                   class="text-decoration-none small">
                    <i class="fas fa-chart-line me-1"></i>
                    ดูสถิติรายงาน
                </a>
            </div>
            <div class="col-auto">
                @if (session()->has('name'))
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ session()->get('name') }}
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="logout()">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            ออกจากระบบ
                        </button>
                    </div>
                @else
                    <script>window.location = "/login";</script>
                @endif
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4" id="stats-section">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1" id="total-requests">{{ $totalRequests }}</h3>
                        <small class="text-muted">ขอเปลวันนี้ทั้งหมด</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1" id="my-requests">{{ $myRequests }}</h3>
                        <small class="text-muted">เรารับวันนี้ทั้งหมด</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row text-center" id="shift-stats">
                            <div class="col">
                                <strong id="night-count">{{ $shiftStats['night'] }}</strong>
                                <br><small>ดึก (00-08)</small>
                            </div>
                            <div class="col">
                                <strong id="morning-count">{{ $shiftStats['morning'] }}</strong>
                                <br><small>เช้า (08-16)</small>
                            </div>
                            <div class="col">
                                <strong id="evening-count">{{ $shiftStats['evening'] }}</strong>
                                <br><small>บ่าย (16-24)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="hideCompleted" onchange="updateFilters()">
                            <label class="form-check-label" for="hideCompleted">
                                ซ่อนรายการที่สำเร็จแล้ว
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showMyTasks" onchange="updateFilters()">
                            <label class="form-check-label" for="showMyTasks">
                                แสดงเฉพาะงานของฉัน
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loading-indicator" class="text-center py-3" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Stretcher Requests -->
        <div class="row" id="stretcher-requests">
            @include('stretcher.partials.request-cards', ['stretcherRequests' => $stretcherRequests, 'userPendingTasks' => $userPendingTasks])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentFilters = {
    hideCompleted: false,
    showMyTasks: false
};

let isLoading = false;
let refreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🏗️ Dashboard page loaded');
    
    // Setup automatic refresh
    startAutoRefresh();
    
    // Setup WebSocket listeners
    setupWebSocketListeners();
    
    console.log('✅ Dashboard initialized');
});

function setupWebSocketListeners() {
    if (!window.Echo) {
        console.log('❌ Echo not available');
        return;
    }

    console.log('📡 Setting up WebSocket listeners for dashboard...');
    
    // Listen for stretcher updates
    window.Echo.channel('stretcher-updates')
        .listen('StretcherUpdated', (event) => {
            console.log('📨 Dashboard received WebSocket event:', event);
            
            // Handle the event
            handleDashboardWebSocketEvent(event);
        })
        .error((error) => {
            console.error('❌ Dashboard WebSocket error:', error);
        });
    
    console.log('✅ Dashboard WebSocket listeners setup complete');
}

function handleDashboardWebSocketEvent(event) {
    const { action, stretcher, team_name } = event;
    
    console.log(`🔔 Dashboard handling: ${action} for HN: ${stretcher.hn}`);
    
    // Play notification sound
    if (window.stretcherUtils) {
        window.stretcherUtils.playNotificationSound();
    }
    
    // Update UI immediately
    updateDashboardFromWebSocket(event);
    
    // Show notification
    showDashboardNotification(event);
}

function updateDashboardFromWebSocket(event) {
    const { action, stretcher } = event;
    const stretcherId = stretcher.stretcher_register_id;
    
    // Find the card for this stretcher
    const card = document.querySelector(`[data-stretcher-id="${stretcherId}"]`);
    
    if (card) {
        // Update existing card
        console.log(`📝 Updating existing card for ID: ${stretcherId}`);
        updateCardWithWebSocketData(card, stretcher, action);
        
        // Add highlight effect
        card.classList.add('highlight');
        setTimeout(() => card.classList.remove('highlight'), 3000);
    } else if (action === 'new') {
        // Add new card for new requests
        console.log(`📝 Adding new card for ID: ${stretcherId}`);
        addNewCardToContainer(stretcher);
    } else {
        // Card not found, refresh entire dashboard
        console.log(`📝 Card not found for ID: ${stretcherId}, refreshing dashboard`);
        loadDashboardData(false);
    }
    
    // Update statistics
    updateDashboardStatistics();
}

function addNewCardToContainer(stretcher) {
    const container = document.getElementById('stretcher-requests');
    if (!container) return;
    
    // Check if we currently have "no data" message
    const noDataCard = container.querySelector('.col-12');
    if (noDataCard && noDataCard.innerHTML.includes('ไม่มีข้อมูลการขอเปล')) {
        noDataCard.remove();
    }
    
    // Create new card HTML
    const cardHtml = createRequestCard(stretcher, 0);
    
    // Add to beginning of container (most recent first)
    container.insertAdjacentHTML('afterbegin', cardHtml);
    
    // Animate the new card
    const newCard = container.firstElementChild?.querySelector('.stretcher-card');
    if (newCard) {
        newCard.style.opacity = '0';
        newCard.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            newCard.style.transition = 'all 0.3s ease';
            newCard.style.opacity = '1';
            newCard.style.transform = 'scale(1)';
        }, 100);
    }
}

function updateDashboardStatistics() {
    // Re-count cards for statistics
    const allCards = document.querySelectorAll('[data-stretcher-id]');
    const userId = {{ session()->get('userid', 'null') }};
    
    let totalCount = allCards.length;
    let myCount = 0;
    let nightCount = 0, morningCount = 0, eveningCount = 0;
    
    allCards.forEach(card => {
        const cardData = extractCardData(card);
        
        // Count my tasks
        if (cardData.team_id == userId) {
            myCount++;
        }
        
        // Count by shift (simplified)
        const time = cardData.time;
        if (time) {
            const hour = parseInt(time.split(':')[0]);
            if (hour >= 0 && hour < 8) nightCount++;
            else if (hour >= 8 && hour < 16) morningCount++;
            else eveningCount++;
        }
    });
    
    // Update statistics display
    const totalEl = document.getElementById('total-requests');
    const myEl = document.getElementById('my-requests');
    const nightEl = document.getElementById('night-count');
    const morningEl = document.getElementById('morning-count');
    const eveningEl = document.getElementById('evening-count');
    
    if (totalEl) totalEl.textContent = totalCount;
    if (myEl) myEl.textContent = myCount;
    if (nightEl) nightEl.textContent = nightCount;
    if (morningEl) morningEl.textContent = morningCount;
    if (eveningEl) eveningEl.textContent = eveningCount;
}

function extractCardData(card) {
    // Extract data from card DOM elements
    const timeEl = card.querySelector('.card-header small');
    const teamEl = card.querySelector('.card-footer .text-muted');
    
    return {
        id: card.dataset.stretcherId,
        time: timeEl ? timeEl.textContent : null,
        team_id: teamEl && teamEl.textContent.includes('ทีม') ? 2 : null, // Simplified
    };
}

function showDashboardNotification(event) {
    const { action, stretcher, team_name } = event;
    
    switch(action) {
        case 'new':
            if (window.Swal) {
                window.Swal.fire({
                    title: '🔔 มีรายการขอเปลใหม่!',
                    html: `
                        <div class="text-start">
                            <strong>HN:</strong> ${stretcher.hn}<br>
                            <strong>ชื่อ:</strong> ${stretcher.pname}${stretcher.fname} ${stretcher.lname}<br>
                            <strong>จาก:</strong> ${stretcher.department}<br>
                            <strong>ไป:</strong> ${stretcher.department2}<br>
                            <strong>ความเร่งด่วน:</strong> <span class="text-danger">${stretcher.stretcher_priority_name}</span>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'รับทราบ',
                    timer: 10000,
                    timerProgressBar: true,
                    position: 'top-end',
                    showClass: {
                        popup: 'animate__animated animate__slideInRight'
                    }
                });
            }
            break;
            
        case 'accepted':
            if (window.stretcherUtils && team_name) {
                window.stretcherUtils.showToast(
                    '✅ มีคนรับงานแล้ว',
                    `${team_name} รับงาน HN: ${stretcher.hn}`,
                    'success',
                    4000
                );
            }
            break;
            
        case 'sent':
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast(
                    '🚗 เริ่มปฏิบัติงาน',
                    `HN: ${stretcher.hn} - ไปรับผู้ป่วย`,
                    'info'
                );
            }
            break;
            
        case 'completed':
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast(
                    '🎉 งานสำเร็จ!',
                    `HN: ${stretcher.hn} - เสร็จสิ้น`,
                    'success'
                );
            }
            break;
    }
}

function handleStretcherNotification(event) {
    const { action, stretcher, team_name } = event;
    
    console.log('🔔 Handling notification:', action);
    
    // Play notification sound
    if (window.stretcherUtils) {
        window.stretcherUtils.playNotificationSound();
    }
    
    switch(action) {
        case 'new':
            showNewRequestNotification(stretcher);
            break;
        case 'accepted':
            showAcceptedNotification(stretcher, team_name);
            break;
        case 'sent':
            showSentNotification(stretcher);
            break;
        case 'completed':
            showCompletedNotification(stretcher);
            break;
    }
}

function showNewRequestNotification(stretcher) {
    if (window.Swal && stretcher) {
        window.Swal.fire({
            title: '🔔 มีรายการขอเปลใหม่!',
            html: `
                <div class="text-start">
                    <strong>HN:</strong> ${stretcher.hn}<br>
                    <strong>ชื่อ:</strong> ${stretcher.pname}${stretcher.fname} ${stretcher.lname}<br>
                    <strong>จาก:</strong> ${stretcher.department}<br>
                    <strong>ไป:</strong> ${stretcher.department2}<br>
                    <strong>ความเร่งด่วน:</strong> <span class="text-danger">${stretcher.stretcher_priority_name}</span>
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

function showAcceptedNotification(stretcher, teamName) {
    if (window.stretcherUtils && teamName && stretcher) {
        window.stretcherUtils.showToast(
            '✅ มีคนรับงานแล้ว',
            `${teamName} รับงาน HN: ${stretcher.hn}`,
            'success',
            4000
        );
    }
}

function showSentNotification(stretcher) {
    if (window.stretcherUtils && stretcher) {
        window.stretcherUtils.showToast(
            '🚗 เริ่มปฏิบัติงาน',
            `HN: ${stretcher.hn} - ไปรับผู้ป่วย`,
            'info'
        );
    }
}

function showCompletedNotification(stretcher) {
    if (window.stretcherUtils && stretcher) {
        window.stretcherUtils.showToast(
            '🎉 งานสำเร็จ!',
            `HN: ${stretcher.hn} - เสร็จสิ้น`,
            'success'
        );
    }
}

function startAutoRefresh() {
    // Refresh every 30 seconds
    refreshInterval = setInterval(() => {
        loadDashboardData(false);
    }, 30000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
}

function updateFilters() {
    currentFilters.hideCompleted = document.getElementById('hideCompleted').checked;
    currentFilters.showMyTasks = document.getElementById('showMyTasks').checked;
    
    loadDashboardData(true);
}

function loadDashboardData(showLoading = true) {
    if (isLoading) return;
    
    isLoading = true;
    
    if (showLoading) {
        document.getElementById('loading-indicator').style.display = 'block';
    }
    
    const params = new URLSearchParams({
        hideCompleted: currentFilters.hideCompleted ? '1' : '0',
        showMyTasks: currentFilters.showMyTasks ? '1' : '0'
    });

    fetch(`/api/dashboard-data?${params}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        updateDashboard(data);
        console.log('✅ Dashboard data updated');
    })
    .catch(error => {
        console.error('❌ Failed to load dashboard data:', error);
        if (window.stretcherUtils) {
            window.stretcherUtils.showToast('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    })
    .finally(() => {
        isLoading = false;
        document.getElementById('loading-indicator').style.display = 'none';
    });
}

function updateDashboard(data) {
    // Update statistics
    document.getElementById('total-requests').textContent = data.totalRequests;
    document.getElementById('my-requests').textContent = data.myRequests;
    document.getElementById('night-count').textContent = data.shiftStats.night;
    document.getElementById('morning-count').textContent = data.shiftStats.morning;
    document.getElementById('evening-count').textContent = data.shiftStats.evening;
    
    // Update requests cards
    updateRequestCards(data.stretcherRequests, data.userPendingTasks);
}

function updateRequestCards(requests, userPendingTasks) {
    const container = document.getElementById('stretcher-requests');
    
    if (requests.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ไม่มีข้อมูลการขอเปล</h5>
                        <p class="text-muted">ระบบจะแสดงรายการใหม่โดยอัตโนมัติเมื่อมีการขอเปล</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = requests.map(request => createRequestCard(request, userPendingTasks)).join('');
}

function createRequestCard(request, userPendingTasks) {
    const userId = {{ session()->get('userid', 'null') }};
    const priorityClass = ['ด่วนที่สุด', 'ด่วน'].includes(request.stretcher_priority_name) ? 'priority-urgent' : 'priority-normal';
    
    let statusClass = '';
    switch(request.stretcher_work_status_id) {
        case 1: statusClass = 'status-new text-white'; break;
        case 2: statusClass = 'status-accepted text-dark'; break;
        case 3: statusClass = 'status-sent text-white'; break;
        case 4: statusClass = 'status-completed text-white'; break;
    }
    
    let actionButtons = generateActionButtons(request.stretcher_register_id, request);
    
    return `
        <div class="col-lg-6 col-xl-4 mb-3">
            <div class="card border-0 shadow-sm stretcher-card h-100 ${priorityClass}" 
                 data-stretcher-id="${request.stretcher_register_id}">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <span class="badge ${statusClass}">
                        ${request.stretcher_work_status_name}
                    </span>
                    <small class="text-muted">
                        ${formatTimeAgo(request.stretcher_register_date + ' ' + request.stretcher_register_time)}
                    </small>
                </div>
                
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-4"><strong>HN:</strong></div>
                        <div class="col-8">${request.hn}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>ชื่อ:</strong></div>
                        <div class="col-8">${request.pname}${request.fname} ${request.lname}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>เปล:</strong></div>
                        <div class="col-8">${request.stretcher_type_name}</div>
                    </div>
                    ${request.stretcher_o2tube_type_name ? `
                    <div class="row mb-2">
                        <div class="col-4"><strong>ออกซิเจน:</strong></div>
                        <div class="col-8">${request.stretcher_o2tube_type_name}</div>
                    </div>
                    ` : ''}
                    <div class="row mb-2">
                        <div class="col-4"><strong>ความเร่งด่วน:</strong></div>
                        <div class="col-8">
                            <span class="${['ด่วนที่สุด', 'ด่วน'].includes(request.stretcher_priority_name) ? 'text-danger fw-bold' : ''}">
                                ${request.stretcher_priority_name}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>ผู้ขอ:</strong></div>
                        <div class="col-8">${request.dname || ''}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>จาก:</strong></div>
                        <div class="col-8">${request.department}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>ไป:</strong></div>
                        <div class="col-8">${request.department2}</div>
                    </div>
                    
                    ${request.from_note ? `
                    <div class="alert alert-warning py-2 mt-2">
                        <small><strong>หมายเหตุ (1):</strong> ${request.from_note}</small>
                    </div>
                    ` : ''}
                    
                    ${request.send_note ? `
                    <div class="alert alert-info py-2 mt-2">
                        <small><strong>หมายเหตุ (2):</strong> ${request.send_note}</small>
                    </div>
                    ` : ''}
                </div>
                
                <div class="card-footer border-0 bg-transparent">
                    ${actionButtons}
                </div>
            </div>
        </div>
    `;
}

function formatTimeAgo(datetime) {
    const now = new Date();
    const then = new Date(datetime);
    const diffMs = now - then;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'เพิ่งเสร็จ';
    if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`;
    
    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} วันที่แล้ว`;
}

function acceptRequest(stretcherId) {
    performAction('accept', stretcherId, 'กำลังรับงาน...');
}

function sendRequest(stretcherId) {
    performAction('send', stretcherId, 'กำลังบันทึก...');
}

function completeRequest(stretcherId) {
    performAction('complete', stretcherId, 'กำลังบันทึก...');
}

function performAction(action, stretcherId, loadingText) {
    const button = document.querySelector(`[data-id="${stretcherId}"]`);
    const originalHtml = button.innerHTML;
    
    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> ${loadingText}`;
    
    // Optimistic update - update UI immediately
    const optimisticUpdate = createOptimisticUpdate(action, stretcherId);
    if (optimisticUpdate) {
        updateCardOptimistically(stretcherId, optimisticUpdate);
    }
    
    fetch(`/api/stretcher/${action}/${stretcherId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast('สำเร็จ', data.message, 'success');
            }
            
            // Update with real data from server
            if (data.data) {
                updateCardWithRealData(stretcherId, data.data);
            }
            
            // The real-time broadcast will also update other users' screens
            console.log('✅ Action completed:', action, stretcherId);
        } else {
            if (window.stretcherUtils) {
                window.stretcherUtils.showToast('ข้อผิดพลาด', data.message, 'error');
            }
            
            // Revert optimistic update on error
            revertOptimisticUpdate(stretcherId);
        }
    })
    .catch(error => {
        console.error('Action failed:', error);
        if (window.stretcherUtils) {
            window.stretcherUtils.showToast('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการดำเนินการ', 'error');
        }
        
        // Revert optimistic update on error
        revertOptimisticUpdate(stretcherId);
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

function createOptimisticUpdate(action, stretcherId) {
    const userId = {{ session()->get('userid', 'null') }};
    const userName = '{{ session()->get('name', '') }}';
    
    switch(action) {
        case 'accept':
            return {
                stretcher_work_status_id: 2,
                stretcher_work_status_name: 'รับงานแล้ว',
                stretcher_team_list_id: userId,
                name: userName
            };
        case 'send':
            return {
                stretcher_work_status_id: 3,
                stretcher_work_status_name: 'กำลังปฏิบัติงาน',
                stretcher_register_send_time: new Date().toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit', second: '2-digit'})
            };
        case 'complete':
            return {
                stretcher_work_status_id: 4,
                stretcher_work_status_name: 'เสร็จสิ้น',
                stretcher_register_return_time: new Date().toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit', second: '2-digit'})
            };
        default:
            return null;
    }
}

function updateCardOptimistically(stretcherId, updates) {
    const card = document.querySelector(`[data-stretcher-id="${stretcherId}"]`);
    if (!card) return;
    
    // Store original state for potential revert
    if (!card.dataset.originalState) {
        card.dataset.originalState = JSON.stringify({
            status: card.querySelector('.badge')?.textContent,
            statusClass: card.querySelector('.badge')?.className,
            buttons: card.querySelector('.card-footer')?.innerHTML
        });
    }
    
    // Update status badge
    const badge = card.querySelector('.badge');
    if (badge && updates.stretcher_work_status_name) {
        badge.textContent = updates.stretcher_work_status_name;
        
        // Update badge class
        badge.className = 'badge ';
        switch(updates.stretcher_work_status_id) {
            case 1: badge.className += 'status-new text-white'; break;
            case 2: badge.className += 'status-accepted text-dark'; break;
            case 3: badge.className += 'status-sent text-white'; break;
            case 4: badge.className += 'status-completed text-white'; break;
        }
    }
    
    // Update action buttons
    const footer = card.querySelector('.card-footer');
    if (footer) {
        footer.innerHTML = generateActionButtons(stretcherId, updates, true);
    }
    
    // Add processing indicator
    card.classList.add('processing');
    setTimeout(() => card.classList.remove('processing'), 2000);
}

function updateCardWithRealData(stretcherId, data) {
    const card = document.querySelector(`[data-stretcher-id="${stretcherId}"]`);
    if (!card) return;
    
    // Clear original state since this is confirmed
    delete card.dataset.originalState;
    
    // Update with real server data
    updateCardOptimistically(stretcherId, data);
    
    // Remove processing state
    card.classList.remove('processing');
    card.classList.add('confirmed');
    setTimeout(() => card.classList.remove('confirmed'), 1000);
}

function revertOptimisticUpdate(stretcherId) {
    const card = document.querySelector(`[data-stretcher-id="${stretcherId}"]`);
    if (!card || !card.dataset.originalState) return;
    
    try {
        const original = JSON.parse(card.dataset.originalState);
        
        // Restore status badge
        const badge = card.querySelector('.badge');
        if (badge && original.status) {
            badge.textContent = original.status;
            badge.className = original.statusClass;
        }
        
        // Restore buttons
        const footer = card.querySelector('.card-footer');
        if (footer && original.buttons) {
            footer.innerHTML = original.buttons;
        }
        
        // Clear original state
        delete card.dataset.originalState;
        
        // Add error indicator
        card.classList.add('error');
        setTimeout(() => card.classList.remove('error'), 2000);
        
    } catch (error) {
        console.error('Failed to revert optimistic update:', error);
    }
}

function generateActionButtons(stretcherId, data, isOptimistic = false) {
    const userId = {{ session()->get('userid', 'null') }};
    const userPendingTasks = 0; // You might want to track this
    
    const disabled = isOptimistic ? 'disabled' : '';
    const processingText = isOptimistic ? ' (กำลังประมวลผล...)' : '';
    
    if (!data.stretcher_team_list_id && data.stretcher_work_status_id == 1) {
        if (userPendingTasks <= 0) {
            return `
                <button onclick="acceptRequest(${stretcherId})" 
                        class="btn btn-primary w-100 action-btn" ${disabled}
                        data-id="${stretcherId}">
                    <i class="fas fa-hand-paper me-1"></i> รับงาน${processingText}
                </button>
            `;
        } else {
            return `
                <button class="btn btn-danger w-100" disabled>
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    คุณมีงานค้างอยู่
                </button>
            `;
        }
    } else if (data.stretcher_team_list_id == userId && data.stretcher_work_status_id == 2) {
        return `
            <button onclick="sendRequest(${stretcherId})" 
                    class="btn btn-info w-100 text-white action-btn" ${disabled}
                    data-id="${stretcherId}">
                <i class="fas fa-walking me-1"></i> ไปรับผู้ป่วย${processingText}
            </button>
        `;
    } else if (data.stretcher_team_list_id == userId && data.stretcher_work_status_id == 3) {
        return `
            <button onclick="completeRequest(${stretcherId})" 
                    class="btn btn-success w-100 action-btn" ${disabled}
                    data-id="${stretcherId}">
                <i class="fas fa-check-circle me-1"></i> งานสำเร็จ${processingText}
            </button>
        `;
    } else {
        return `
            <div class="text-center text-muted">
                ${data.name ? `<i class="fas fa-user-check me-1"></i> ${data.name}` : '<i class="fas fa-clock me-1"></i> รอการดำเนินการ'}
            </div>
        `;
    }
}

function logout() {
    if (confirm('ต้องการออกจากระบบหรือไม่?')) {
        window.location.href = '/logout';
    }
}

// Manual refresh function
window.refreshDashboard = function() {
    console.log('🔄 Manual refresh triggered');
    loadDashboardData(true);
};

// Debug function
window.debugDashboard = function() {
    console.log('=== Dashboard Debug ===');
    console.log('Current filters:', currentFilters);
    console.log('Is loading:', isLoading);
    console.log('Echo available:', !!window.Echo);
    console.log('Utils available:', !!window.stretcherUtils);
};

console.log('✅ Dashboard JavaScript loaded. Use refreshDashboard() or debugDashboard() for testing.');
</script>
@endpush