<?php
// routes/web.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StretcherController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth.stretcher')->group(function () {
    Route::get('/', [StretcherController::class, 'dashboard'])->name('dashboard');
});

Route::get('/show', [StretcherController::class, 'publicView'])->name('public.view');

Route::get('/debug/websocket', function () {
    return view('debug.websocket');
});

Route::post('/debug/test-broadcast', function () {
    try {
        $testData = [
            'hn' => 'TEST001',
            'pname' => 'ทดสอบ',
            'fname' => 'ระบบ',
            'lname' => 'แจ้งเตือน',
            'department' => 'IT',
            'department2' => 'Testing',
            'stretcher_priority_name' => 'ปกติ',
            'stretcher_type_name' => 'เปลธรรมดา',
            'stretcher_o2tube_type_name' => 'ไม่มี',
            'stretcher_register_id' => 99999,
            'stretcher_register_time' => now()->format('H:i:s'),
            'stretcher_register_date' => now()->format('Y-m-d')
        ];

        broadcast(new \App\Events\StretcherUpdated(
            action: 'new',
            stretcher: $testData,
            metadata: ['source' => 'debug', 'timestamp' => now()]
        ));

        return response()->json(['success' => true, 'message' => 'Test broadcast sent']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
});
