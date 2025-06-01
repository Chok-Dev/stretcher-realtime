<?php
// app/Events/StretcherStatusChanged.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StretcherStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stretcherId;
    public $oldStatus;
    public $newStatus;
    public $teamMember;

    public function __construct($stretcherId, $oldStatus, $newStatus, $teamMember = null)
    {
        $this->stretcherId = $stretcherId;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->teamMember = $teamMember;
    }

    public function broadcastOn()
    {
        return new Channel('stretcher-updates');
    }

    public function broadcastAs()
    {
        return 'status.changed';
    }

    public function broadcastWith()
    {
        return [
            'stretcher_id' => $this->stretcherId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'team_member' => $this->teamMember,
            'timestamp' => now()->toISOString()
        ];
    }
}