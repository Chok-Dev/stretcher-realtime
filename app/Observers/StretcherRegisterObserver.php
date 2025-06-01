<?php
// app/Observers/StretcherRegisterObserver.php

namespace App\Observers;

use App\Models\StretcherRegister;
use App\Models\MyStretcher;
use App\Events\StretcherUpdated;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StretcherRegisterObserver
{
    /**
     * Handle the StretcherRegister "created" event.
     */
    public function created(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister created', [
                'id' => $stretcher->stretcher_register_id,
                'hn' => $stretcher->hn ?? 'N/A'
            ]);
            
            // Check if this broadcast should be skipped (from controller actions)
            if ($this->shouldSkipBroadcast($stretcher, 'created')) {
                Log::info('Skipping observer broadcast for created stretcher (controller already broadcasted)', [
                    'id' => $stretcher->stretcher_register_id
                ]);
                return;
            }
            
            // หน่วงเวลาเล็กน้อยเพื่อให้ database sync เสร็จ
            usleep(150000); // 0.15 วินาที
            
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $this->broadcastStretcherUpdate('new', $stretcherData, null, [
                    'created_at' => now()->toISOString(),
                    'source' => 'observer_created',
                    'observer_version' => '2.0'
                ]);
                
                // Send notification for new requests
                app(NotificationService::class)->sendNewRequestNotification($stretcherData);
                
                Log::info('New stretcher request broadcasted', [
                    'id' => $stretcher->stretcher_register_id,
                    'hn' => $stretcherData->hn,
                    'priority' => $stretcherData->stretcher_priority_name
                ]);
            } else {
                Log::warning('Could not find MyStretcher data for new request', [
                    'id' => $stretcher->stretcher_register_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast new stretcher', [
                'id' => $stretcher->stretcher_register_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the StretcherRegister "updated" event.
     */
    public function updated(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister updated', [
                'id' => $stretcher->stretcher_register_id,
                'dirty_fields' => array_keys($stretcher->getDirty())
            ]);
            
            // Check if this broadcast should be skipped (from controller actions)
            if ($this->shouldSkipBroadcast($stretcher, 'updated')) {
                Log::info('Skipping observer broadcast for updated stretcher (controller already broadcasted)', [
                    'id' => $stretcher->stretcher_register_id,
                    'dirty_fields' => array_keys($stretcher->getDirty())
                ]);
                return;
            }
            
            // หน่วงเวลาเล็กน้อยเพื่อให้ database sync เสร็จ
            usleep(150000); // 0.15 วินาที
            
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $action = $this->determineAction($stretcher);
                
                if ($action) {
                    $this->broadcastStretcherUpdate($action, $stretcherData, $stretcherData->name, 
                        $this->getActionMetadata($stretcher, $action)
                    );
                    
                    Log::info("Stretcher {$action} broadcasted from observer", [
                        'id' => $stretcher->stretcher_register_id,
                        'team' => $stretcherData->name,
                        'hn' => $stretcherData->hn
                    ]);
                } else {
                    Log::info('No action determined for stretcher update', [
                        'id' => $stretcher->stretcher_register_id,
                        'dirty_fields' => array_keys($stretcher->getDirty())
                    ]);
                }
            } else {
                Log::warning('Could not find MyStretcher data for update', [
                    'id' => $stretcher->stretcher_register_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast stretcher update', [
                'id' => $stretcher->stretcher_register_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if broadcast should be skipped to prevent duplicate broadcasts
     */
    private function shouldSkipBroadcast(StretcherRegister $stretcher, string $event): bool
    {
        // Check for controller broadcast flag (set in the request)
        $requestId = request()->id ?? request()->header('X-Request-ID');
        if ($requestId) {
            $cacheKey = "broadcast_skip_{$stretcher->stretcher_register_id}_{$event}_{$requestId}";
            
            if (Cache::get($cacheKey)) {
                return true;
            }
            
            // Set flag for a short time to prevent duplicate broadcasts
            Cache::put($cacheKey, true, 10); // 10 seconds
        }
        
        // Check for recent controller broadcasts
        $recentBroadcastKey = "recent_broadcast_{$stretcher->stretcher_register_id}_{$event}";
        if (Cache::get($recentBroadcastKey)) {
            Log::info('Skipping observer broadcast due to recent controller broadcast', [
                'id' => $stretcher->stretcher_register_id,
                'event' => $event
            ]);
            return true;
        }
        
        // Set flag to indicate observer will broadcast
        Cache::put($recentBroadcastKey, true, 5); // 5 seconds
        
        return false;
    }

    /**
     * Broadcast stretcher update
     */
    private function broadcastStretcherUpdate(string $action, MyStretcher $stretcherData, ?string $teamName = null, array $metadata = [])
    {
        try {
            $event = new StretcherUpdated(
                action: $action,
                stretcher: $stretcherData->toArray(),
                teamName: $teamName,
                metadata: array_merge([
                    'observer_source' => true,
                    'broadcast_time' => now()->toISOString(),
                    'observer_version' => '2.0'
                ], $metadata)
            );

            broadcast($event);
            
            Log::info('StretcherUpdated event broadcasted from observer', [
                'action' => $action,
                'stretcher_id' => $stretcherData->stretcher_register_id,
                'team_name' => $teamName,
                'channel' => 'stretcher-updates'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast StretcherUpdated event from observer', [
                'action' => $action,
                'stretcher_id' => $stretcherData->stretcher_register_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Determine action based on field changes
     */
    private function determineAction(StretcherRegister $stretcher): ?string
    {
        $dirtyFields = $stretcher->getDirty();
        
        // ตรวจสอบการรับงาน (accept)
        if (array_key_exists('stretcher_team_list_id', $dirtyFields) && $stretcher->stretcher_team_list_id) {
            return 'accepted';
        }
        
        // ตรวจสอบการเปลี่ยนสถานะ
        if (array_key_exists('stretcher_work_status_id', $dirtyFields)) {
            switch ($stretcher->stretcher_work_status_id) {
                case 2:
                    // Only if team was assigned (to avoid duplicate with accept)
                    if (!array_key_exists('stretcher_team_list_id', $dirtyFields)) {
                        return 'accepted';
                    }
                    break;
                case 3: 
                    return 'sent';      // ไปรับผู้ป่วย
                case 4: 
                    return 'completed'; // งานสำเร็จ
                case 5: 
                    return 'cancelled'; // ยกเลิก
            }
        }
        
        // ตรวจสอบการอัพเดทเวลาต่างๆ
        if (array_key_exists('stretcher_register_send_time', $dirtyFields) && 
            $stretcher->stretcher_register_send_time) {
            return 'sent';
        }
        
        if (array_key_exists('stretcher_register_return_time', $dirtyFields) && 
            $stretcher->stretcher_register_return_time) {
            return 'completed';
        }
        
        // ตรวจสอบการยกเลิก
        if (array_key_exists('stretcher_work_result_id', $dirtyFields) && 
            $stretcher->stretcher_work_result_id == 3) { // Assuming 3 is cancelled
            return 'cancelled';
        }
        
        return null;
    }

    /**
     * Get metadata for specific actions
     */
    private function getActionMetadata(StretcherRegister $stretcher, string $action): array
    {
        $metadata = [
            'action_time' => now()->toISOString(),
            'dirty_fields' => array_keys($stretcher->getDirty()),
            'source' => 'observer_updated'
        ];
        
        switch ($action) {
            case 'accepted':
                $metadata['accepted_date'] = $stretcher->stretcher_register_accept_date;
                $metadata['accepted_time'] = $stretcher->stretcher_register_accept_time;
                $metadata['team_id'] = $stretcher->stretcher_team_list_id;
                break;
                
            case 'sent':
                $metadata['sent_time'] = $stretcher->stretcher_register_send_time;
                break;
                
            case 'completed':
                $metadata['return_time'] = $stretcher->stretcher_register_return_time;
                $metadata['work_result_id'] = $stretcher->stretcher_work_result_id;
                
                // Calculate duration if possible
                if ($stretcher->stretcher_register_time && $stretcher->stretcher_register_return_time) {
                    try {
                        $start = \Carbon\Carbon::parse($stretcher->stretcher_register_date . ' ' . $stretcher->stretcher_register_time);
                        $end = \Carbon\Carbon::parse($stretcher->stretcher_register_date . ' ' . $stretcher->stretcher_register_return_time);
                        $metadata['duration_minutes'] = $end->diffInMinutes($start);
                    } catch (\Exception $e) {
                        Log::warning('Failed to calculate duration in observer', [
                            'stretcher_id' => $stretcher->stretcher_register_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                break;
                
            case 'cancelled':
                $metadata['cancelled_at'] = now()->toISOString();
                $metadata['work_result_id'] = $stretcher->stretcher_work_result_id;
                break;
        }
        
        return $metadata;
    }

    /**
     * Handle the StretcherRegister "deleted" event.
     */
    public function deleted(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister deleted', [
                'id' => $stretcher->stretcher_register_id
            ]);
            
            // Broadcast deletion event
            $this->broadcastStretcherUpdate('deleted', $stretcher, null, [
                'deleted_at' => now()->toISOString(),
                'source' => 'observer_deleted'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to broadcast stretcher deletion', [
                'id' => $stretcher->stretcher_register_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the StretcherRegister "restored" event.
     */
    public function restored(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister restored', [
                'id' => $stretcher->stretcher_register_id
            ]);
            
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $this->broadcastStretcherUpdate('restored', $stretcherData, $stretcherData->name, [
                    'restored_at' => now()->toISOString(),
                    'source' => 'observer_restored'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to broadcast stretcher restoration', [
                'id' => $stretcher->stretcher_register_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}