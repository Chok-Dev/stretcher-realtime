// app/Providers/EventServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Stretcher Events
        \App\Events\NewStretcherRequest::class => [
            // Add listeners here if needed
        ],
        
        \App\Events\StretcherUpdated::class => [
            // Add listeners here if needed
        ],
        
        \App\Events\StretcherStatusChanged::class => [
            // Add listeners here if needed
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}