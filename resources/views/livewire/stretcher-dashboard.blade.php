<div>
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
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="logout">
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
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-1">{{ $this->totalRequests }}</h3>
                    <small class="text-muted">ขอเปลวันนี้ทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success mb-1">{{ $this->myRequests }}</h3>
                    <small class="text-muted">เรารับวันนี้ทั้งหมด</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col">
                            <strong>{{ $this->shiftStats['night'] }}</strong>
                            <br><small>ดึก (00-08)</small>
                        </div>
                        <div class="col">
                            <strong>{{ $this->shiftStats['morning'] }}</strong>
                            <br><small>เช้า (08-16)</small>
                        </div>
                        <div class="col">
                            <strong>{{ $this->shiftStats['evening'] }}</strong>
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
                        <input class="form-check-input" type="checkbox" wire:model.live="hideCompleted" id="hideCompleted">
                        <label class="form-check-label" for="hideCompleted">
                            ซ่อนรายการที่สำเร็จแล้ว
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" wire:model.live="showMyTasks" id="showMyTasks">
                        <label class="form-check-label" for="showMyTasks">
                            แสดงเฉพาะงานของฉัน
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stretcher Requests -->
    <div class="row">
        @forelse ($stretcherRequests as $request)
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="card border-0 shadow-sm stretcher-card h-100 
                    {{ in_array($request->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) ? 'priority-urgent' : 'priority-normal' }}">
                    
                    <!-- Header with Status -->
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <span class="badge 
                            @if($request->stretcher_work_status_id == 1) status-new text-white
                            @elseif($request->stretcher_work_status_id == 2) status-accepted text-dark
                            @elseif($request->stretcher_work_status_id == 3) status-sent text-white
                            @elseif($request->stretcher_work_status_id == 4) status-completed text-white
                            @endif">
                            {{ $request->stretcher_work_status_name }}
                        </span>
                        <small class="text-muted">
                            {{ \Carbon\Carbon::parse($request->stretcher_register_date . ' ' . $request->stretcher_register_time)->diffForHumans() }}
                        </small>
                    </div>
                    
                    <!-- Patient Information -->
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-4"><strong>HN:</strong></div>
                            <div class="col-8">{{ $request->hn }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><strong>ชื่อ:</strong></div>
                            <div class="col-8">{{ $request->pname }}{{ $request->fname }} {{ $request->lname }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><strong>เปล:</strong></div>
                            <div class="col-8">{{ $request->stretcher_type_name }}</div>
                        </div>
                        @if($request->stretcher_o2tube_type_name)
                        <div class="row mb-2">
                            <div class="col-4"><strong>ออกซิเจน:</strong></div>
                            <div class="col-8">{{ $request->stretcher_o2tube_type_name }}</div>
                        </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-4"><strong>ความเร่งด่วน:</strong></div>
                            <div class="col-8">
                                <span class="{{ in_array($request->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) ? 'text-danger fw-bold' : '' }}">
                                    {{ $request->stretcher_priority_name }}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><strong>ผู้ขอ:</strong></div>
                            <div class="col-8">{{ $request->dname }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><strong>จาก:</strong></div>
                            <div class="col-8">{{ $request->department }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4"><strong>ไป:</strong></div>
                            <div class="col-8">{{ $request->department2 }}</div>
                        </div>
                        
                        @if($request->from_note)
                        <div class="alert alert-warning py-2 mt-2">
                            <small><strong>หมายเหตุ (1):</strong> {{ $request->from_note }}</small>
                        </div>
                        @endif
                        
                        @if($request->send_note)
                        <div class="alert alert-info py-2 mt-2">
                            <small><strong>หมายเหตุ (2):</strong> {{ $request->send_note }}</small>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card-footer border-0 bg-transparent">
                        @if(empty($request->stretcher_team_list_id) && $request->stretcher_work_status_id == 1)
                            @if($this->userPendingTasks <= 0)
                                <button wire:click="accept({{ $request->stretcher_register_id }})" 
                                        class="btn btn-primary w-100"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="accept({{ $request->stretcher_register_id }})">
                                        <i class="fas fa-hand-paper me-1"></i> รับงาน
                                    </span>
                                    <span wire:loading wire:target="accept({{ $request->stretcher_register_id }})">
                                        <i class="fas fa-spinner fa-spin me-1"></i> กำลังรับงาน...
                                    </span>
                                </button>
                            @else
                                <button class="btn btn-danger w-100" disabled>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    คุณมีงานค้างอยู่
                                </button>
                            @endif
                        @elseif($request->stretcher_team_list_id == session()->get('userid') && $request->stretcher_work_status_id == 2)
                            <button wire:click="send({{ $request->stretcher_register_id }})" 
                                    class="btn btn-info w-100 text-white"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="send({{ $request->stretcher_register_id }})">
                                    <i class="fas fa-walking me-1"></i> ไปรับผู้ป่วย
                                </span>
                                <span wire:loading wire:target="send({{ $request->stretcher_register_id }})">
                                    <i class="fas fa-spinner fa-spin me-1"></i> กำลังบันทึก...
                                </span>
                            </button>
                        @elseif($request->stretcher_team_list_id == session()->get('userid') && $request->stretcher_work_status_id == 3)
                            <button wire:click="complete({{ $request->stretcher_register_id }})" 
                                    class="btn btn-success w-100"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="complete({{ $request->stretcher_register_id }})">
                                    <i class="fas fa-check-circle me-1"></i> งานสำเร็จ
                                </span>
                                <span wire:loading wire:target="complete({{ $request->stretcher_register_id }})">
                                    <i class="fas fa-spinner fa-spin me-1"></i> กำลังบันทึก...
                                </span>
                            </button>
                        @else
                            <div class="text-center text-muted">
                                @if($request->name)
                                    <i class="fas fa-user-check me-1"></i>
                                    {{ $request->name }}
                                @else
                                    <i class="fas fa-clock me-1"></i>
                                    รอการดำเนินการ
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ไม่มีข้อมูลการขอเปล</h5>
                        <p class="text-muted">ระบบจะแสดงรายการใหม่โดยอัตโนมัติเมื่อมีการขอเปล</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Event Handlers -->
    <script>
        // Listen for Livewire events
        document.addEventListener('DOMContentLoaded', function() {
            // Success messages
            Livewire.on('al-success', (event) => {
                window.stretcherUtils.showToast('สำเร็จ', event.message || 'ดำเนินการสำเร็จ', 'success');
            });

            // Error messages  
            Livewire.on('al-error', (event) => {
                window.stretcherUtils.showToast('ข้อผิดพลาด', event.message || 'เกิดข้อผิดพลาด', 'error');
            });

            // New stretcher request
            Livewire.on('new-stretcher-request', (event) => {
                window.stretcherUtils.playNotificationSound();
                
                const stretcher = event.stretcher;
                
                Swal.fire({
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
            });

            // Stretcher accepted
            Livewire.on('stretcher-accepted', (event) => {
                window.stretcherUtils.showToast(
                    '✅ มีคนรับงานแล้ว',
                    `${event.teamName} รับงาน HN: ${event.stretcher.hn}`,
                    'success',
                    4000
                );
            });

            // Stretcher sent
            Livewire.on('stretcher-sent', (event) => {
                window.stretcherUtils.showToast(
                    '🚗 เริ่มปฏิบัติงาน',
                    `HN: ${event.stretcher.hn} - ไปรับผู้ป่วย`,
                    'info'
                );
            });

            // Stretcher completed
            Livewire.on('stretcher-completed', (event) => {
                window.stretcherUtils.showToast(
                    '🎉 งานสำเร็จ!',
                    `HN: ${event.stretcher.hn} - เสร็จสิ้น`,
                    'success'
                );
            });
        });
    </script>
</div>