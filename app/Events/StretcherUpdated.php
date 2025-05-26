<?php
// app/Events/StretcherUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StretcherUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public array $stretcher,
        public ?string $teamName = null,
        public ?array $metadata = null
    ) {
        Log::info('StretcherUpdated event created', [
            'action' => $action,
            'stretcher_id' => $stretcher['stretcher_register_id'] ?? 'N/A',
            'team_name' => $teamName,
            'metadata' => $metadata
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('stretcher-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'StretcherUpdated';
    }

    public function broadcastWith(): array
    {
        $data = [
            'action' => $this->action,
            'stretcher' => $this->stretcher,
            'team_name' => $this->teamName,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString()
        ];
        
        Log::info('Broadcasting StretcherUpdated', [
            'channel' => 'stretcher-updates',
            'event' => 'StretcherUpdated',
            'action' => $this->action,
            'data_size' => strlen(json_encode($data))
        ]);
        
        return $data;
    }
}