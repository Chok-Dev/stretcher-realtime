<div wire:poll.30s="loadData">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <h2 class="mb-1">{{ $this->totalRequests }}</h2>
                    <p class="mb-0">รายการทั้งหมดวันนี้</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body text-center">
                    <h2 class="mb-1">{{ $this->pendingRequests }}</h2>
                    <p class="mb-0">รายการรอดำเนินการ</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body text-center">
                    <h2 class="mb-1">{{ $this->completedRequests }}</h2>
                    <p class="mb-0">รายการสำเร็จแล้ว</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Groups -->
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

    <div class="text-center mt-4">
        <small class="text-muted">
            <i class="fas fa-sync-alt me-1"></i>
            อัปเดตข้อมูลอัตโนมัติทุก {{ $refreshInterval }} วินาที
            | เวลาล่าสุด: {{ now()->format('H:i:s') }}
        </small>
    </div>
</div>