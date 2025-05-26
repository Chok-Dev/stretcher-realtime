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