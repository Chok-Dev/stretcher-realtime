<?php
// app/Console/Commands/WatchNewStretcherRequests.php

namespace App\Console\Commands;

use App\Models\MyStretcher;
use App\Events\StretcherUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Broadcast;

class WatchNewStretcherRequests extends Command
{
    protected $signature = 'stretcher:watch-new {--debug : Show debug info} {--test-broadcast : Test broadcast only}';
    protected $description = 'Watch for new stretcher requests';

    public function handle()
    {
        $this->info('🔍 Starting stretcher watcher...');
        
        // Test broadcast if requested
        if ($this->option('test-broadcast')) {
            return $this->testBroadcast();
        }
        
        // Check broadcast configuration
        $this->checkBroadcastConfig();
        
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

    private function checkBroadcastConfig(): void
    {
        $driver = config('broadcasting.default');
        $this->info("📡 Broadcast driver: {$driver}");
        
        if ($driver === 'reverb') {
            $config = config('broadcasting.connections.reverb');
            $this->info("🔧 Reverb config:");
            $this->info("   - Host: {$config['options']['host']}");
            $this->info("   - Port: {$config['options']['port']}");
            $this->info("   - Scheme: {$config['options']['scheme']}");
            $this->info("   - App ID: {$config['app_id']}");
        }
    }

    private function testBroadcast(): int
    {
        $this->info('🧪 Testing broadcast...');
        
        try {
            $testData = [
                'hn' => 'TEST001',
                'pname' => 'ทดสอบ',
                'fname' => 'ระบบ',
                'lname' => 'แจ้งเตือน',
                'department' => 'IT',
                'department2' => 'Testing',
                'stretcher_priority_name' => 'ปกติ',
                'stretcher_type_name' => 'เปลธรรมดา',
                'stretcher_o2tube_type_name' => 'ไม่มี',
                'stretcher_register_id' => 99999,
                'stretcher_register_time' => now()->format('H:i:s'),
                'stretcher_register_date' => now()->format('Y-m-d')
            ];

            $event = new StretcherUpdated(
                action: 'new',
                stretcher: $testData,
                metadata: ['source' => 'test', 'timestamp' => now()]
            );

            $this->info('📤 Broadcasting test event...');
            broadcast($event);
            
            $this->info('✅ Test broadcast sent successfully');
            $this->info('🔍 Check your browser console for the event');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Test broadcast failed: ' . $e->getMessage());
            Log::error('Test broadcast error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
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
                // ข้อมูลที่ส่ง
                $eventData = [
                    'action' => 'new',
                    'stretcher' => (array) $request,
                    'metadata' => ['source' => 'watcher', 'timestamp' => now()]
                ];
                
                if ($this->option('debug')) {
                    $this->info("📊 Event data structure:");
                    $this->info("   - Action: {$eventData['action']}");
                    $this->info("   - HN: {$request->hn}");
                    $this->info("   - Name: {$request->pname}{$request->fname} {$request->lname}");
                }
                
                // Log before broadcast
                Log::info('Broadcasting new stretcher request', [
                    'id' => $request->stretcher_register_id,
                    'hn' => $request->hn,
                    'name' => "{$request->pname}{$request->fname} {$request->lname}",
                    'event_data' => $eventData
                ]);
                
                // สร้าง Event และ Broadcast
                $event = new StretcherUpdated(
                    action: 'new',
                    stretcher: (array) $request,
                    metadata: ['source' => 'watcher', 'timestamp' => now()]
                );
                
                $this->info("📤 Broadcasting event...");
                broadcast($event);
                
                // Log after broadcast
                Log::info('Broadcast completed successfully', [
                    'id' => $request->stretcher_register_id,
                    'broadcast_channel' => 'stretcher-updates',
                    'broadcast_event' => 'StretcherUpdated'
                ]);
                
                $this->info("📡 Broadcasted: ID {$request->stretcher_register_id} - {$request->fname} {$request->lname}");
                
                // Test channel subscription
                if ($this->option('debug')) {
                    $this->testChannelSubscription();
                }
                
                // อัปเดต cache
                Cache::put('stretcher_last_checked_id', $request->stretcher_register_id, now()->addDay());
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to broadcast ID {$request->stretcher_register_id}: " . $e->getMessage());
                Log::error('Broadcast failed', [
                    'id' => $request->stretcher_register_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        if ($this->option('debug') && $newRequests->count() === 0) {
            $this->line('⏳ No new requests found');
        }
    }

    private function testChannelSubscription(): void
    {
        try {
            $this->info("🔍 Testing channel subscription...");
            
            // ตรวจสอบว่า Reverb server รับ connection หรือไม่
            $reverbHost = config('broadcasting.connections.reverb.options.host', '127.0.0.1');
            $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
            
            $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 1);
            
            if ($connection) {
                $this->info("✅ Reverb server is reachable at {$reverbHost}:{$reverbPort}");
                fclose($connection);
            } else {
                $this->error("❌ Cannot reach Reverb server at {$reverbHost}:{$reverbPort} - Error: {$errstr}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Channel test failed: " . $e->getMessage());
        }
    }
}