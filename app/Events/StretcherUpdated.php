<?php
// app/Events/StretcherUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StretcherUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stretcherId;
    public $action;
    public $data;
    public $userId;

    public function __construct($stretcherId, $action, $data = null, $userId = null)
    {
        $this->stretcherId = $stretcherId;
        $this->action = $action;
        $this->data = $data;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel('stretcher-updates');
    }

    public function broadcastAs()
    {
        return 'stretcher.updated';
    }

    public function broadcastWith()
    {
        return [
            'stretcher_id' => $this->stretcherId,
            'action' => $this->action,
            'data' => $this->data,
            'user_id' => $this->userId,
            'timestamp' => now()->toISOString()
        ];
    }
}