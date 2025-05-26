<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Models\StretcherRegister;
use App\Observers\StretcherRegisterObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Events and listeners
    ];

    public function boot(): void
    {
        StretcherRegister::observe(StretcherRegisterObserver::class);
    }
}