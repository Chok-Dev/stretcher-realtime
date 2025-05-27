<?php
// app/Observers/StretcherRegisterObserver.php

namespace App\Observers;

use App\Models\StretcherRegister;
use App\Models\MyStretcher;
use App\Events\StretcherUpdated;
use Illuminate\Support\Facades\Log;

class StretcherRegisterObserver
{
    public function created(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister created', ['id' => $stretcher->stretcher_register_id]);
            
            // หน่วงเวลาเล็กน้อยเพื่อให้ database sync เสร็จ
            usleep(100000); // 0.1 วินาที
            
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $this->broadcastStretcherUpdate('new', $stretcherData, null, [
                    'created_at' => now()->toISOString(),
                    'source' => 'observer_created'
                ]);
                
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

    public function updated(StretcherRegister $stretcher)
    {
        try {
            Log::info('StretcherRegister updated', [
                'id' => $stretcher->stretcher_register_id,
                'dirty_fields' => array_keys($stretcher->getDirty())
            ]);
            
            // หน่วงเวลาเล็กน้อยเพื่อให้ database sync เสร็จ
            usleep(100000); // 0.1 วินาที
            
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $action = $this->determineAction($stretcher);
                
                if ($action) {
                    $this->broadcastStretcherUpdate($action, $stretcherData, $stretcherData->name, 
                        $this->getActionMetadata($stretcher, $action)
                    );
                    
                    Log::info("Stretcher {$action} broadcasted", [
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

    private function broadcastStretcherUpdate(string $action, MyStretcher $stretcherData, ?string $teamName = null, array $metadata = [])
    {
        try {
            $event = new StretcherUpdated(
                action: $action,
                stretcher: $stretcherData->toArray(),
                teamName: $teamName,
                metadata: array_merge([
                    'observer_source' => true,
                    'broadcast_time' => now()->toISOString()
                ], $metadata)
            );

            broadcast($event);
            
            Log::info('StretcherUpdated event broadcasted', [
                'action' => $action,
                'stretcher_id' => $stretcherData->stretcher_register_id,
                'team_name' => $teamName,
                'channel' => 'stretcher-updates'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast StretcherUpdated event', [
                'action' => $action,
                'stretcher_id' => $stretcherData->stretcher_register_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function determineAction(StretcherRegister $stretcher): ?string
    {
        // ตรวจสอบการรับงาน (accept)
        if ($stretcher->isDirty('stretcher_team_list_id') && $stretcher->stretcher_team_list_id) {
            return 'accepted';
        }
        
        // ตรวจสอบการเปลี่ยนสถานะ
        if ($stretcher->isDirty('stretcher_work_status_id')) {
            switch ($stretcher->stretcher_work_status_id) {
                case 3: 
                    return 'sent';      // ไปรับผู้ป่วย
                case 4: 
                    return 'completed'; // งานสำเร็จ
                case 5: 
                    return 'cancelled'; // ยกเลิก
            }
        }
        
        // ตรวจสอบการอัพเดทเวลาต่างๆ
        if ($stretcher->isDirty('stretcher_register_send_time') && $stretcher->stretcher_register_send_time) {
            return 'sent';
        }
        
        if ($stretcher->isDirty('stretcher_register_return_time') && $stretcher->stretcher_register_return_time) {
            return 'completed';
        }
        
        return null;
    }

    private function getActionMetadata(StretcherRegister $stretcher, string $action): array
    {
        $metadata = [
            'action_time' => now()->toISOString(),
            'dirty_fields' => array_keys($stretcher->getDirty())
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
                break;
                
            case 'cancelled':
                $metadata['cancelled_at'] = now()->toISOString();
                break;
        }
        
        return $metadata;
    }
}