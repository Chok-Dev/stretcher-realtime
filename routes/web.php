<?php
// routes/web.php

use App\Events\StretcherUpdated;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StretcherController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [StretcherController::class, 'logout'])->name('logout');

Route::middleware('auth.stretcher')->group(function () {
    Route::get('/', [StretcherController::class, 'dashboard'])->name('dashboard');
});

Route::get('/show', [StretcherController::class, 'publicView'])->name('public.view');

// API Routes for AJAX calls
Route::prefix('api')->group(function () {
    // Dashboard API
    Route::middleware('auth.stretcher')->group(function () {
        Route::get('/dashboard-data', [StretcherController::class, 'getDashboardData'])->name('api.dashboard.data');
        Route::post('/stretcher/accept/{id}', [StretcherController::class, 'acceptRequest'])->name('api.stretcher.accept');
        Route::post('/stretcher/send/{id}', [StretcherController::class, 'sendRequest'])->name('api.stretcher.send');
        Route::post('/stretcher/complete/{id}', [StretcherController::class, 'completeRequest'])->name('api.stretcher.complete');
    });
    
    // Public view API (no auth required)
    Route::get('/public-view-data', [StretcherController::class, 'getPublicViewData'])->name('api.public.data');
});

// Debug routes
Route::post('/debug/test-broadcast', function() {
    try {
        $testData = [
            'hn' => 'TEST001',
            'pname' => 'นาย',
            'fname' => 'ทดสอบ',
            'lname' => 'ระบบใหม่',
            'department' => 'IT แผนก',
            'department2' => 'ห้องพักผู้ป่วย',
            'stretcher_priority_name' => 'ด่วน',
            'stretcher_type_name' => 'เปลธรรมดา',
            'stretcher_o2tube_type_name' => 'ไม่มี',
            'stretcher_register_id' => 99999,
            'stretcher_register_time' => now()->format('H:i:s'),
            'stretcher_register_date' => now()->format('Y-m-d'),
            'stretcher_work_status_id' => 1,
            'stretcher_work_status_name' => 'รอรับงาน'
        ];

        $event = new StretcherUpdated(
            action: 'new',
            stretcher: $testData,
            metadata: ['source' => 'debug_route', 'timestamp' => now()]
        );

        broadcast($event);

        return response()->json([
            'success' => true,
            'message' => 'Test broadcast sent successfully',
            'data' => $testData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send broadcast: ' . $e->getMessage(),
            'error' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug/websocket', function() {
    return view('debug.websocket');
});