<div>
    <!-- Statistics Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h2 class="text-primary mb-1">{{ $this->totalRequests }}</h2>
                    <small class="text-muted">ขอเปลวันนี้ทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h2 class="text-warning mb-1">{{ $this->pendingRequests }}</h2>
                    <small class="text-muted">รอดำเนินการ</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h2 class="text-success mb-1">{{ $this->completedRequests }}</h2>
                    <small class="text-muted">เสร็จสิ้นแล้ว</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto Refresh Indicator -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">รายการขอเปลประจำวัน</h5>
        <div class="d-flex align-items-center">
            <span class="badge bg-primary me-2" id="realtime-indicator">
                🟢 Real-time
            </span>
            <small class="text-muted" id="last-update">
                อัพเดทล่าสุด: {{ now()->format('H:i:s') }}
            </small>
        </div>
    </div>

    <!-- Requests by Status -->
    @if($stretcherRequests->isNotEmpty())
        @foreach($stretcherRequests as $statusName => $requests)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <span class="badge 
                            @if($statusName == 'รอรับงาน') bg-secondary
                            @elseif($statusName == 'ได้รับงานแล้ว') bg-warning
                            @elseif($statusName == 'ไปรับผู้ป่วย') bg-info
                            @elseif($statusName == 'งานสำเร็จ') bg-success
                            @else bg-dark
                            @endif me-2">
                            {{ $requests->count() }}
                        </span>
                        {{ $statusName }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($requests as $request)
                            <div class="col-lg-6 col-xl-4 mb-3">
                                <div class="card border h-100 
                                    {{ in_array($request->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) ? 'border-danger' : 'border-light' }}"
                                    data-request-id="{{ $request->stretcher_register_id }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-1">HN: {{ $request->hn }}</h6>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($request->stretcher_register_time)->format('H:i') }}
                                            </small>
                                        </div>
                                        
                                        <p class="card-text mb-2">
                                            <strong>{{ $request->pname }}{{ $request->fname }} {{ $request->lname }}</strong>
                                        </p>
                                        
                                        <div class="small text-muted">
                                            <div class="mb-1">
                                                <i class="fas fa-arrow-right me-1"></i>
                                                {{ $request->department }} → {{ $request->department2 }}
                                            </div>
                                            
                                            @if($request->stretcher_priority_name)
                                            <div class="mb-1">
                                                <i class="fas fa-exclamation-circle me-1 
                                                    {{ in_array($request->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) ? 'text-danger' : '' }}"></i>
                                                <span class="{{ in_array($request->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) ? 'text-danger fw-bold' : '' }}">
                                                    {{ $request->stretcher_priority_name }}
                                                </span>
                                            </div>
                                            @endif
                                            
                                            @if($request->stretcher_type_name)
                                            <div class="mb-1">
                                                <i class="fas fa-bed me-1"></i>
                                                {{ $request->stretcher_type_name }}
                                            </div>
                                            @endif
                                            
                                            @if($request->name)
                                            <div class="mb-1">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $request->name }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">ไม่มีข้อมูลการขอเปลวันนี้</h5>
                <p class="text-muted">รายการจะแสดงขึ้นเมื่อมีการขอเปล</p>
            </div>
        </div>
    @endif
</div>

{{-- ✅ ใช้ @script directive ตามเอกสาร Livewire --}}
@script
<script>
    console.log('🎯 PublicStretcherView @script loaded');
    
    // Global function สำหรับรับ updates จาก PHP
    window.handlePublicViewUpdate = function(event) {
        console.log('📨 Public view received update:', event);
        
        // Update last update time
        const lastUpdateEl = document.getElementById('last-update');
        if (lastUpdateEl) {
            lastUpdateEl.textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
        }
        
        // Update indicator
        const indicator = document.getElementById('realtime-indicator');
        if (indicator) {
            indicator.innerHTML = '🔄 กำลังอัพเดท...';
            indicator.className = 'badge bg-warning me-2';
            
            setTimeout(() => {
                indicator.innerHTML = '🟢 Real-time';
                indicator.className = 'badge bg-success me-2';
            }, 1000);
        }
        
        // Highlight updated card if it exists
        if (event.stretcher?.stretcher_register_id) {
            const card = document.querySelector(`[data-request-id="${event.stretcher.stretcher_register_id}"]`);
            if (card) {
                card.classList.add('pulse');
                setTimeout(() => card.classList.remove('pulse'), 3000);
            }
        }
        
        // Show notification based on action
        switch(event.action) {
            case 'new':
                showPublicNotification('🔔 มีรายการขอเปลใหม่', `HN: ${event.stretcher?.hn || 'N/A'}`, 'info');
                break;
            case 'accepted':
                showPublicNotification('✅ มีคนรับงานแล้ว', `HN: ${event.stretcher?.hn || 'N/A'}`, 'success');
                break;
            case 'sent':
                showPublicNotification('🚗 เริ่มปฏิบัติงาน', `HN: ${event.stretcher?.hn || 'N/A'}`, 'info');
                break;
            case 'completed':
                showPublicNotification('🎉 งานสำเร็จ', `HN: ${event.stretcher?.hn || 'N/A'}`, 'success');
                break;
        }
        
        // Refresh the component
        $wire.$refresh();
    };

    function showPublicNotification(title, text, icon = 'info') {
        // Show a small toast notification
        if (window.Swal) {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }
        
        // Play sound if available
        const audio = document.getElementById('notification-sound');
        if (audio) {
            audio.play().catch(e => console.log('Audio play failed:', e));
        }
    }

    // Auto refresh every 30 seconds (backup method)
    setInterval(() => {
        console.log('🔄 Auto refresh triggered');
        $wire.$refresh();
    }, 30000);

    // Listen for Livewire events (backup method)
    $wire.on('refreshData', () => {
        console.log('📤 Received refreshData event');
        $wire.$refresh();
    });

    // Initialize last update time
    const lastUpdateEl = document.getElementById('last-update');
    if (lastUpdateEl) {
        lastUpdateEl.textContent = `อัพเดทล่าสุด: ${new Date().toLocaleTimeString('th-TH')}`;
    }

    // Debug functions
    window.debugPublicView = function() {
        console.log('=== Public View Debug ===');
        console.log('$wire:', $wire);
        console.log('Component element:', $wire.$el);
        console.log('Component ID:', $wire.$id);
        
        // Test refresh
        $wire.$refresh();
        console.log('✅ Public view refreshed');
    };

    console.log('✅ PublicStretcherView @script setup complete');
    console.log('💡 Available functions: debugPublicView()');
</script>
@endscript