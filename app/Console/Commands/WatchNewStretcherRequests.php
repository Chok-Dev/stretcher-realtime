<?php
// app/Console/Commands/WatchNewStretcherRequests.php

namespace App\Console\Commands;

use App\Models\MyStretcher;
use App\Events\StretcherUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WatchNewStretcherRequests extends Command
{
    protected $signature = 'stretcher:watch-new {--debug : Show debug info}';
    protected $description = 'Watch for new stretcher requests';

    public function handle()
    {
        $this->info('🔍 Starting stretcher watcher...');
        
        while (true) {
            try {
                $this->checkForNewRequests();
                sleep(5);
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                Log::error('Watcher error: ' . $e->getMessage());
                sleep(10);
            }
        }
    }

    private function checkForNewRequests(): void
    {
        $lastCheckedId = Cache::get('stretcher_last_checked_id', 0);
        
        if ($this->option('debug')) {
            $this->info("🔍 Checking for requests after ID: {$lastCheckedId}");
        }
        
        // ใช้ DB แทน Model เพื่อหลีกเลี่ยงปัญหา
        $newRequests = DB::connection('pgsql')->table('my_stretcher')
            ->where('stretcher_register_date', now()->format('Y-m-d'))
            ->where('stretcher_work_status_id', 1) // รายการใหม่
            ->where('stretcher_register_id', '>', $lastCheckedId)
            ->orderBy('stretcher_register_id', 'ASC')
            ->get();

        if ($newRequests->count() > 0) {
            $this->info("📨 Found {$newRequests->count()} new requests");
        }

        foreach ($newRequests as $request) {
            try {
                // ส่ง Event
                $eventData = [
                    'action' => 'new',
                    'stretcher' => (array) $request,
                    'metadata' => ['source' => 'watcher']
                ];
                
                broadcast(new StretcherUpdated(
                    action: 'new',
                    stretcher: (array) $request,
                    metadata: ['source' => 'watcher']
                ));
                
                $this->info("📡 Broadcasted: ID {$request->stretcher_register_id} - {$request->fname} {$request->lname}");
                Log::info('Stretcher broadcast sent', ['id' => $request->stretcher_register_id]);
                
                // อัปเดต cache
                Cache::put('stretcher_last_checked_id', $request->stretcher_register_id, now()->addDay());
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to broadcast ID {$request->stretcher_register_id}: " . $e->getMessage());
                Log::error('Broadcast failed', ['id' => $request->stretcher_register_id, 'error' => $e->getMessage()]);
            }
        }
        
        if ($this->option('debug') && $newRequests->count() === 0) {
            $this->line('⏳ No new requests found');
        }
    }
}