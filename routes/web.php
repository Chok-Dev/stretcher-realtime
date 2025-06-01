<?php
// routes/web.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard Routes
Route::middleware(['checklogin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/show', [DashboardController::class, 'show'])->name('dashboard.show');
});

// Maintenance Route
Route::get('/maintenance', function () {
    return view('errors.503');
})->name('maintenance');

// Fallback Route
Route::fallback(function () {
    return redirect()->route('login.form');
});