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
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                broadcast(new StretcherUpdated(
                    action: 'new',
                    stretcher: $stretcherData->toArray(),
                    metadata: ['created_at' => now()]
                ));
                
                Log::info('New stretcher request broadcasted', [
                    'id' => $stretcher->stretcher_register_id,
                    'hn' => $stretcherData->hn
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast new stretcher: ' . $e->getMessage());
        }
    }

    public function updated(StretcherRegister $stretcher)
    {
        try {
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcher->stretcher_register_id)
                ->first();
                
            if ($stretcherData) {
                $action = $this->determineAction($stretcher);
                
                if ($action) {
                    broadcast(new StretcherUpdated(
                        action: $action,
                        stretcher: $stretcherData->toArray(),
                        teamName: $stretcherData->name,
                        metadata: $this->getActionMetadata($stretcher, $action)
                    ));
                    
                    Log::info("Stretcher {$action} broadcasted", [
                        'id' => $stretcher->stretcher_register_id,
                        'team' => $stretcherData->name
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast stretcher update: ' . $e->getMessage());
        }
    }

    private function determineAction(StretcherRegister $stretcher): ?string
    {
        if ($stretcher->isDirty('stretcher_team_list_id') && $stretcher->stretcher_team_list_id) {
            return 'accepted';
        }
        
        if ($stretcher->isDirty('stretcher_work_status_id')) {
            switch ($stretcher->stretcher_work_status_id) {
                case 3: return 'sent';      // ไปรับผู้ป่วย
                case 4: return 'completed'; // งานสำเร็จ
                case 5: return 'cancelled'; // ยกเลิก
            }
        }
        
        return null;
    }

    private function getActionMetadata(StretcherRegister $stretcher, string $action): array
    {
        $metadata = ['action_time' => now()];
        
        switch ($action) {
            case 'accepted':
                $metadata['accepted_date'] = $stretcher->stretcher_register_accept_date;
                $metadata['accepted_time'] = $stretcher->stretcher_register_accept_time;
                break;
            case 'sent':
                $metadata['sent_time'] = $stretcher->stretcher_register_send_time;
                break;
            case 'completed':
                $metadata['return_time'] = $stretcher->stretcher_register_return_time;
                break;
        }
        
        return $metadata;
    }
}