<?php
// app/Events/NewStretcherRequest.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewStretcherRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function broadcastOn()
    {
        return new Channel('stretcher-updates');
    }

    public function broadcastAs()
    {
        return 'new.request';
    }

    public function broadcastWith()
    {
        return [
            'request' => $this->request,
            'timestamp' => now()->toISOString()
        ];
    }
}