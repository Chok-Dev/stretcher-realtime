<?php
// app/Console/Commands/StretcherWatchCommand.php

namespace App\Console\Commands;

use App\Models\MyStretcher;
use App\Models\StretcherRegister;
use App\Events\StretcherUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StretcherWatchCommand extends Command
{
    protected $signature = 'stretcher:watch-new 
                            {--debug : Enable debug output}
                            {--interval=5 : Check interval in seconds}
                            {--broadcast : Auto broadcast new requests}
                            {--simulate : Simulate new requests for testing}
                            {--simple : Use simple ID-based checking instead of date/time}
                            {--show-structure : Show table structure and exit}';
    
    protected $description = 'Watch for new stretcher requests and optionally broadcast them';

    private $lastCheckTime;
    private $processedIds = [];
    private $debug = false;
    private $interval = 5;
    private $broadcast = false;
    private $simulate = false;

    public function handle()
    {
        // แสดง table structure และออก ถ้าใช้ --show-structure
        if ($this->option('show-structure')) {
            $this->debugTableStructure();
            return 0;
        }
        
        $this->debug = $this->option('debug');
        $this->interval = (int) $this->option('interval');
        $this->broadcast = $this->option('broadcast');
        $this->simulate = $this->option('simulate');
        $simple = $this->option('simple');
        
        $this->lastCheckTime = now()->subMinutes(30); // เริ่มดูย้อนหลัง 30 นาที
        
        $this->info("🔍 Starting Stretcher Watch Service");
        $this->info("⚙️  Debug: " . ($this->debug ? 'ON' : 'OFF'));
        $this->info("⏱️  Interval: {$this->interval} seconds");
        $this->info("📡 Broadcast: " . ($this->broadcast ? 'ON' : 'OFF'));
        $this->info("🧪 Simulate: " . ($this->simulate ? 'ON' : 'OFF'));
        $this->info("🔧 Simple mode: " . ($simple ? 'ON' : 'OFF'));
        $this->info("🕐 Start time: " . $this->lastCheckTime->format('Y-m-d H:i:s'));
        $this->line("───────────────────────────────────────────────");
        
        if ($this->simulate) {
            $this->info("🧪 SIMULATION MODE - Will create fake data every {$this->interval} seconds");
        }
        
        // Initialize processed IDs
        $this->initializeProcessedIds();
        
        // แสดง table structure ถ้าเป็น debug mode
        if ($this->debug) {
            $this->debugTableStructure();
        }
        
        // เก็บ lastMaxId สำหรับ simple mode
        $lastMaxId = $simple ? $this->getMaxStretcherId() : 0;
        
        // Main watch loop
        while (true) {
            try {
                if ($this->simulate) {
                    $this->simulateNewRequest();
                } elseif ($simple) {
                    $lastMaxId = $this->checkForNewRequestsSimple($lastMaxId);
                } else {
                    $this->checkForNewRequests();
                }
                
                sleep($this->interval);
                
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
                if ($this->debug) {
                    $this->error("Stack trace: " . $e->getTraceAsString());
                }
                
                Log::error('StretcherWatch error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                sleep(5); // หยุดสักครู่แล้วลองใหม่
            }
        }
    }

    private function initializeProcessedIds()
    {
        try {
            // เก็บ IDs ของ requests ที่มีอยู่แล้ว (วันนี้)
            $existingIds = StretcherRegister::where('stretcher_register_date', '>=', now()->format('Y-m-d'))
                ->pluck('stretcher_register_id')
                ->toArray();
                
            $this->processedIds = array_flip($existingIds);
            
            $this->debug("📋 Initialized with " . count($existingIds) . " existing requests for today");
            
            if ($this->debug && count($existingIds) > 0) {
                $this->debug("📊 ID range: " . min($existingIds) . " - " . max($existingIds));
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Could not initialize processed IDs: " . $e->getMessage());
            $this->processedIds = [];
        }
    }

    private function checkForNewRequests()
    {
        try {
            // หา requests ใหม่โดยใช้ field ที่มีจริง
            $newRequests = MyStretcher::where('stretcher_register_date', '>=', $this->lastCheckTime->format('Y-m-d'))
                ->where(function($query) {
                    // ใช้ combination ของ date + time สำหรับการเปรียบเทียบ
                    $query->where('stretcher_register_date', '>', $this->lastCheckTime->format('Y-m-d'))
                          ->orWhere(function($q) {
                              $q->where('stretcher_register_date', '=', $this->lastCheckTime->format('Y-m-d'))
                                ->where('stretcher_register_time', '>', $this->lastCheckTime->format('H:i:s'));
                          });
                })
                ->orderBy('stretcher_register_id', 'ASC')
                ->get();

            $this->debug("🔍 Checking for new requests since: " . $this->lastCheckTime->format('Y-m-d H:i:s'));
            $this->debug("📊 Query found " . $newRequests->count() . " potential new requests");
            
            $newCount = 0;
            
            foreach ($newRequests as $request) {
                if (!isset($this->processedIds[$request->stretcher_register_id])) {
                    $this->processNewRequest($request);
                    $this->processedIds[$request->stretcher_register_id] = true;
                    $newCount++;
                }
            }
            
            if ($newCount > 0) {
                $this->info("✅ Found {$newCount} new requests");
            } else {
                $this->debug("📍 No new requests");
            }
            
            $this->lastCheckTime = now();
            
        } catch (\Exception $e) {
            $this->error("❌ Error checking for new requests: " . $e->getMessage());
            
            // Debug: แสดง field ที่มีจริงใน table
            if ($this->debug) {
                $this->debugTableStructure();
            }
            
            throw $e;
        }
    }

    private function processNewRequest($request)
    {
        $timestamp = now()->format('H:i:s');
        
        $this->line("");
        $this->info("🆕 NEW REQUEST [{$timestamp}]");
        $this->line("   ID: {$request->stretcher_register_id}");
        $this->line("   HN: {$request->hn}");
        $this->line("   Patient: {$request->pname}{$request->fname} {$request->lname}");
        $this->line("   From: {$request->department}");
        $this->line("   To: {$request->department2}");
        $this->line("   Priority: {$request->stretcher_priority_name}");
        $this->line("   Status: {$request->stretcher_work_status_name}");
        $this->line("   Time: {$request->stretcher_register_time}");
        
        if ($this->debug) {
            $this->line("   Debug Info:");
            $this->line("     - Type: {$request->stretcher_type_name}");
            $this->line("     - O2: {$request->stretcher_o2tube_type_name}");
            $this->line("     - Team: " . ($request->team_name ?: 'None'));
        }
        
        // Log to Laravel logs
        Log::info('New stretcher request detected', [
            'stretcher_register_id' => $request->stretcher_register_id,
            'hn' => $request->hn,
            'patient_name' => "{$request->pname}{$request->fname} {$request->lname}",
            'from' => $request->department,
            'to' => $request->department2,
            'priority' => $request->stretcher_priority_name,
            'time' => $request->stretcher_register_time
        ]);
        
        // Broadcast if enabled
        if ($this->broadcast) {
            $this->broadcastNewRequest($request);
        }
    }

    private function broadcastNewRequest($request)
    {
        try {
            $stretcherData = $request->toArray();
            
            $event = new StretcherUpdated(
                action: 'new',
                stretcher: $stretcherData,
                metadata: [
                    'source' => 'stretcher:watch-new',
                    'detected_at' => now()->toISOString(),
                    'auto_broadcast' => true
                ]
            );
            
            broadcast($event);
            
            $this->line("   📡 Broadcasted to WebSocket");
            
            Log::info('Auto-broadcasted new stretcher request', [
                'stretcher_register_id' => $request->stretcher_register_id,
                'channel' => 'stretcher-updates',
                'event' => 'StretcherUpdated'
            ]);
            
        } catch (\Exception $e) {
            $this->error("   ❌ Broadcast failed: " . $e->getMessage());
            Log::error('Failed to broadcast stretcher request', [
                'stretcher_register_id' => $request->stretcher_register_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function simulateNewRequest()
    {
        static $simulationCounter = 1;
        
        try {
            $fakeData = [
                'stretcher_register_id' => 90000 + $simulationCounter,
                'hn' => 'SIM' . str_pad($simulationCounter, 3, '0', STR_PAD_LEFT),
                'pname' => 'นาย',
                'fname' => 'จำลอง',
                'lname' => 'ระบบที่ ' . $simulationCounter,
                'department' => 'แผนกทดสอบ ' . (($simulationCounter % 3) + 1),
                'department2' => 'หอผู้ป่วย ' . chr(65 + ($simulationCounter % 5)),
                'stretcher_priority_name' => ($simulationCounter % 2 === 0) ? 'ด่วน' : 'ปกติ',
                'stretcher_type_name' => 'เปลธรรมดา',
                'stretcher_o2tube_type_name' => 'ไม่มี',
                'stretcher_work_status_id' => 1,
                'stretcher_work_status_name' => 'รอรับงาน',
                'stretcher_register_time' => now()->format('H:i:s'),
                'stretcher_register_date' => now()->format('Y-m-d'),
                'team_name' => null
            ];
            
            $timestamp = now()->format('H:i:s');
            
            $this->line("");
            $this->info("🧪 SIMULATED REQUEST #{$simulationCounter} [{$timestamp}]");
            $this->line("   ID: {$fakeData['stretcher_register_id']}");
            $this->line("   HN: {$fakeData['hn']}");
            $this->line("   Patient: {$fakeData['pname']}{$fakeData['fname']} {$fakeData['lname']}");
            $this->line("   From: {$fakeData['department']}");
            $this->line("   To: {$fakeData['department2']}");
            $this->line("   Priority: {$fakeData['stretcher_priority_name']}");
            
            // Always broadcast simulated data
            $event = new StretcherUpdated(
                action: 'new',
                stretcher: $fakeData,
                metadata: [
                    'source' => 'simulation',
                    'simulation_number' => $simulationCounter,
                    'timestamp' => now()->toISOString()
                ]
            );
            
            broadcast($event);
            $this->line("   📡 Broadcasted simulation data");
            
            Log::info('Simulated stretcher request', [
                'simulation_number' => $simulationCounter,
                'data' => $fakeData
            ]);
            
            $simulationCounter++;
            
        } catch (\Exception $e) {
            $this->error("❌ Simulation error: " . $e->getMessage());
            throw $e;
        }
    }

    private function debug($message)
    {
        if ($this->debug) {
            $this->line("🐛 " . $message);
        }
    }
    
    private function debugTableStructure()
    {
        try {
            $this->line("");
            $this->info("🔍 DEBUG: Checking table structure...");
            
            // ตรวจสอบ columns ที่มีจริงใน MyStretcher model
            $sample = MyStretcher::first();
            if ($sample) {
                $attributes = array_keys($sample->getAttributes());
                $this->line("📋 Available columns in MyStretcher:");
                foreach ($attributes as $attr) {
                    $this->line("   - {$attr}");
                }
                
                // แสดงตัวอย่างข้อมูล date/time fields
                $dateTimeFields = array_filter($attributes, function($field) {
                    return strpos($field, 'date') !== false || 
                           strpos($field, 'time') !== false || 
                           strpos($field, 'created') !== false ||
                           strpos($field, 'updated') !== false;
                });
                
                if (!empty($dateTimeFields)) {
                    $this->line("🕐 Date/Time fields:");
                    foreach ($dateTimeFields as $field) {
                        $value = $sample->getAttribute($field);
                        $this->line("   - {$field}: {$value}");
                    }
                }
            } else {
                $this->warn("⚠️  No data found in MyStretcher table");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Could not debug table structure: " . $e->getMessage());
        }
    }
    
    private function getMaxStretcherId()
    {
        try {
            $maxId = StretcherRegister::where('stretcher_register_date', now()->format('Y-m-d'))
                ->max('stretcher_register_id') ?? 0;
            
            $this->debug("📊 Current max stretcher ID: {$maxId}");
            return $maxId;
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Could not get max stretcher ID: " . $e->getMessage());
            return 0;
        }
    }
    
    private function checkForNewRequestsSimple($lastMaxId)
    {
        try {
            $this->debug("🔍 Simple check: Looking for IDs > {$lastMaxId}");
            
            $newRequests = MyStretcher::where('stretcher_register_id', '>', $lastMaxId)
                ->where('stretcher_register_date', now()->format('Y-m-d'))
                ->orderBy('stretcher_register_id', 'ASC')
                ->get();
            
            $newCount = $newRequests->count();
            
            if ($newCount > 0) {
                $this->info("✅ Found {$newCount} new requests (IDs: {$newRequests->first()->stretcher_register_id} - {$newRequests->last()->stretcher_register_id})");
                
                foreach ($newRequests as $request) {
                    $this->processNewRequest($request);
                    $lastMaxId = max($lastMaxId, $request->stretcher_register_id);
                }
            } else {
                $this->debug("📍 No new requests");
            }
            
            return $lastMaxId;
            
        } catch (\Exception $e) {
            $this->error("❌ Error in simple check: " . $e->getMessage());
            return $lastMaxId;
        }
    }
}