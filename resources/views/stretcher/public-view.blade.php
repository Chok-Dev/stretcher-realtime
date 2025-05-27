{{-- resources/views/stretcher/public-view.blade.php --}}
@extends('layouts.app')

@section('title', 'สถานะการขอเปล')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">
            <i class="fas fa-chart-bar me-2"></i>
            สถานะการขอเปล
        </h1>
        @if(session()->has('name'))
            <div>
                <span class="me-3">{{ session()->get('name') }}</span>
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                    กลับหน้าเข้าสู่ระบบ
                </a>
            </div>
        @endif
    </div>

    <div id="public-view-container">
        <!-- Summary Cards -->
        <div class="row mb-4" id="summary-cards">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-1" id="total-requests-public">{{ $totalRequests }}</h2>
                        <p class="mb-0">รายการทั้งหมดวันนี้</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-warning text-dark">
                    <div class="card-body text-center">
                        <h2 class="mb-1" id="pending-requests-public">{{ $pendingRequests }}</h2>
                        <p class="mb-0">รายการรอดำเนินการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-1" id="completed-requests-public">{{ $completedRequests }}</h2>
                        <p class="mb-0">รายการสำเร็จแล้ว</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loading-indicator-public" class="text-center py-3" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Status Groups -->
        <div id="status-groups">
            @foreach($stretcherRequests as $status => $requests)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            {{ $status }} 
                            <span class="badge bg-secondary">{{ $requests->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($requests as $request)
                                <div class="col-lg-6 col-xl-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">HN: {{ $request->hn }}</h6>
                                            <p class="card-text">
                                                <strong>{{ $request->pname }}{{ $request->fname }} {{ $request->lname }}</strong><br>
                                                <small class="text-muted">
                                                    {{ $request->department }} → {{ $request->department2 }}<br>
                                                    {{ \Carbon\Carbon::parse($request->stretcher_register_time)->format('H:i') }}
                                                    @if($request->name)
                                                        | {{ $request->name }}
                                                    @endif
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-sync-alt me-1"></i>
                อัปเดตข้อมูลอัตโนมัติทุก 30 วินาที
                | เวลาล่าสุด: <span id="last-update-time">{{ now()->format('H:i:s') }}</span>
            </small>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let isLoadingPublic = false;
let refreshIntervalPublic;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🏗️ Public view page loaded');
    
    // Setup automatic refresh
    startAutoRefreshPublic();
    
    // Setup WebSocket listeners
    setupWebSocketListenersPublic();
    
    console.log('✅ Public view initialized');
});

function setupWebSocketListenersPublic() {
    if (!window.Echo) {
        console.log('❌ Echo not available');
        return;
    }

    // Listen for stretcher updates
    window.Echo.channel('stretcher-updates')
        .listen('StretcherUpdated', (e) => {
            console.log('📨 Stretcher update received in public view:', e);
            
            // Refresh data
            loadPublicViewData(false);
        });
}

function startAutoRefreshPublic() {
    // Refresh every 30 seconds
    refreshIntervalPublic = setInterval(() => {
        loadPublicViewData(false);
    }, 30000);
}

function stopAutoRefreshPublic() {
    if (refreshIntervalPublic) {
        clearInterval(refreshIntervalPublic);
    }
}

function loadPublicViewData(showLoading = true) {
    if (isLoadingPublic) return;
    
    isLoadingPublic = true;
    
    if (showLoading) {
        document.getElementById('loading-indicator-public').style.display = 'block';
    }

    fetch('/api/public-view-data', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        updatePublicView(data);
        console.log('✅ Public view data updated');
    })
    .catch(error => {
        console.error('❌ Failed to load public view data:', error);
        if (window.stretcherUtils) {
            window.stretcherUtils.showToast('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    })
    .finally(() => {
        isLoadingPublic = false;
        document.getElementById('loading-indicator-public').style.display = 'none';
        document.getElementById('last-update-time').textContent = new Date().toLocaleTimeString('th-TH');
    });
}

function updatePublicView(data) {
    // Update summary cards
    document.getElementById('total-requests-public').textContent = data.totalRequests;
    document.getElementById('pending-requests-public').textContent = data.pendingRequests;
    document.getElementById('completed-requests-public').textContent = data.completedRequests;
    
    // Update status groups
    updateStatusGroups(data.stretcherRequests);
}

function updateStatusGroups(stretcherRequests) {
    const container = document.getElementById('status-groups');
    
    if (Object.keys(stretcherRequests).length === 0) {
        container.innerHTML = `
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">ไม่มีข้อมูลการขอเปล</h5>
                    <p class="text-muted">ระบบจะแสดงรายการใหม่โดยอัตโนมัติเมื่อมีการขอเปล</p>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    for (const [status, requests] of Object.entries(stretcherRequests)) {
        html += createStatusGroup(status, requests);
    }
    container.innerHTML = html;
}

function createStatusGroup(status, requests) {
    let requestsHtml = '';
    requests.forEach(request => {
        requestsHtml += `
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">HN: ${request.hn}</h6>
                        <p class="card-text">
                            <strong>${request.pname}${request.fname} ${request.lname}</strong><br>
                            <small class="text-muted">
                                ${request.department} → ${request.department2}<br>
                                ${formatTime(request.stretcher_register_time)}
                                ${request.name ? ` | ${request.name}` : ''}
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        `;
    });
    
    return `
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    ${status} 
                    <span class="badge bg-secondary">${requests.length}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    ${requestsHtml}
                </div>
            </div>
        </div>
    `;
}

function formatTime(time) {
    return new Date('2000-01-01 ' + time).toLocaleTimeString('th-TH', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Manual refresh function
window.refreshPublicView = function() {
    console.log('🔄 Manual refresh triggered');
    loadPublicViewData(true);
};

// Debug function
window.debugPublicView = function() {
    console.log('=== Public View Debug ===');
    console.log('Is loading:', isLoadingPublic);
    console.log('Echo available:', !!window.Echo);
    console.log('Utils available:', !!window.stretcherUtils);
};

console.log('✅ Public view JavaScript loaded. Use refreshPublicView() or debugPublicView() for testing.');
</script>
@endpush