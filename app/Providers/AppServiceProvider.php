<?php

namespace App\Providers;

use App\Models\StretcherRegister;
use App\Observers\StretcherRegisterObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // ลงทะเบียน Observer สำหรับ StretcherRegister
            StretcherRegister::observe(StretcherRegisterObserver::class);
            
            Log::info('StretcherRegisterObserver registered successfully');
            
            // ตรวจสอบว่า Observer ถูกลงทะเบียนจริงหรือไม่
            $instance = new StretcherRegister();
            $observers = $instance->getObservableEvents();
            Log::info('Observable events for StretcherRegister', ['events' => $observers]);
            
        } catch (\Exception $e) {
            Log::error('Failed to register StretcherRegisterObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}