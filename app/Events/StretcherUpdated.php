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
        public string $action,        // 'new', 'accepted', 'sent', 'completed', 'cancelled'
        public array $stretcher,      // ข้อมูล stretcher
        public ?string $teamName = null,  // ชื่อทีมที่รับงาน
        public ?array $metadata = null    // ข้อมูลเพิ่มเติม
    ) {
        // Log when event is created
        Log::info('StretcherUpdated event created', [
            'action' => $action,
            'stretcher_id' => $stretcher['stretcher_register_id'] ?? 'N/A',
            'team_name' => $teamName,
            'metadata' => $metadata
        ]);
    }

    public function broadcastOn(): array
    {
        // ใช้ channel เดียวกับ Livewire component
        $channels = [
            new Channel('stretcher-updates'),
        ];
        
        Log::info('StretcherUpdated broadcasting on channels', [
            'channels' => ['test-channel'],
            'action' => $this->action,
            'channel_type' => 'public'
        ]);
        
        return $channels;
    }

    public function broadcastAs(): string
    {
        $eventName = 'StretcherUpdated';
        
        Log::info('StretcherUpdated event name', [
            'event_name' => $eventName,
            'action' => $this->action
        ]);
        
        return $eventName;
    }

    public function broadcastWith(): array
    {
        $data = [
            'action' => $this->action,
            'stretcher' => $this->stretcher,
            'team_name' => $this->teamName,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
            'debug_info' => [
                'event_name' => 'StretcherUpdated',
                'channel' => 'stretcher-updates',
                'broadcast_time' => now()->format('Y-m-d H:i:s'),
                'server_time' => microtime(true),
                'laravel_version' => app()->version(),
                'broadcast_driver' => config('broadcasting.default')
            ]
        ];
        
        Log::info('StretcherUpdated broadcasting data', [
            'data_size' => strlen(json_encode($data)),
            'action' => $this->action,
            'debug_info' => $data['debug_info']
        ]);
        
        return $data;
    }
    
    public function broadcastWhen(): bool
    {
        $shouldBroadcast = true;
        
        Log::info('StretcherUpdated broadcast condition', [
            'should_broadcast' => $shouldBroadcast,
            'action' => $this->action
        ]);
        
        return $shouldBroadcast;
    }
}