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

    public function __construct(
        public string $action,        // 'new', 'accepted', 'sent', 'completed', 'cancelled'
        public array $stretcher,      // ข้อมูล stretcher
        public ?string $teamName = null,  // ชื่อทีมที่รับงาน
        public ?array $metadata = null    // ข้อมูลเพิ่มเติม
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('stretcher-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stretcher.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'stretcher' => $this->stretcher,
            'team_name' => $this->teamName,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
        ];
    }
}