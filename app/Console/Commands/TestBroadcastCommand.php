<?php
// app/Console/Commands/TestBroadcastCommand.php

namespace App\Console\Commands;

use App\Events\StretcherUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestBroadcastCommand extends Command
{
    protected $signature = 'test:broadcast {--count=1 : Number of test events to send}';
    protected $description = 'Send test broadcast events for stretcher system';

    public function handle()
    {
        $count = (int) $this->option('count');
        
        $this->info("🧪 Sending {$count} test broadcast(s)...");
        
        for ($i = 1; $i <= $count; $i++) {
            $testData = [
                'hn' => 'TEST' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'pname' => 'นาย',
                'fname' => 'ทดสอบ',
                'lname' => 'ระบบ',
                'department' => 'IT แผนก',
                'department2' => 'ห้องพักผู้ป่วย',
                'stretcher_priority_name' => $i % 2 === 0 ? 'ด่วน' : 'ปกติ',
                'stretcher_type_name' => 'เปลธรรมดา',
                'stretcher_o2tube_type_name' => 'ไม่มี',
                'stretcher_register_id' => 99900 + $i,
                'stretcher_register_time' => now()->format('H:i:s'),
                'stretcher_register_date' => now()->format('Y-m-d'),
                'stretcher_work_status_id' => 1,
                'stretcher_work_status_name' => 'รอรับงาน'
            ];

            try {
                $event = new StretcherUpdated(
                    action: 'new',
                    stretcher: $testData,
                    metadata: ['source' => 'test_command', 'test_number' => $i]
                );

                broadcast($event);
                
                $this->info("✅ Test broadcast {$i}/{$count} sent - HN: {$testData['hn']}");
                
                Log::info("Test broadcast sent", [
                    'test_number' => $i,
                    'hn' => $testData['hn'],
                    'action' => 'new'
                ]);
                
                if ($count > 1 && $i < $count) {
                    sleep(2); // เว้นระยะเวลาระหว่างการส่ง
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to send test broadcast {$i}: " . $e->getMessage());
                Log::error("Test broadcast failed", [
                    'test_number' => $i,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("🎉 Completed sending {$count} test broadcast(s)");
        $this->line('');
        $this->line('💡 Open your browser to /debug/websocket to monitor events');
        $this->line('💡 Or use browser console: debugWebSocket() to check connection');
        
        return 0;
    }
}