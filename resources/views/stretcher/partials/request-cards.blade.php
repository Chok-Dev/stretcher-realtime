{{-- resources/views/stretcher/partials/request-cards.blade.php --}}
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
                    @if($userPendingTasks <= 0)
                        <button onclick="acceptRequest({{ $request->stretcher_register_id }})" 
                                class="btn btn-primary w-100 action-btn"
                                data-id="{{ $request->stretcher_register_id }}">
                            <i class="fas fa-hand-paper me-1"></i> รับงาน
                        </button>
                    @else
                        <button class="btn btn-danger w-100" disabled>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            คุณมีงานค้างอยู่
                        </button>
                    @endif
                @elseif($request->stretcher_team_list_id == session()->get('userid') && $request->stretcher_work_status_id == 2)
                    <button onclick="sendRequest({{ $request->stretcher_register_id }})" 
                            class="btn btn-info w-100 text-white action-btn"
                            data-id="{{ $request->stretcher_register_id }}">
                        <i class="fas fa-walking me-1"></i> ไปรับผู้ป่วย
                    </button>
                @elseif($request->stretcher_team_list_id == session()->get('userid') && $request->stretcher_work_status_id == 3)
                    <button onclick="completeRequest({{ $request->stretcher_register_id }})" 
                            class="btn btn-success w-100 action-btn"
                            data-id="{{ $request->stretcher_register_id }}">
                        <i class="fas fa-check-circle me-1"></i> งานสำเร็จ
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