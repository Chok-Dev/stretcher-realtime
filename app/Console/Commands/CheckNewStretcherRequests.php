<?php
// app/Console/Commands/CheckNewStretcherRequests.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\NewStretcherRequest;
use Carbon\Carbon;

class CheckNewStretcherRequests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stretcher:check-new {--interval=30 : Check interval in seconds}';

    /**
     * The console command description.
     */
    protected $description = 'Check for new stretcher requests and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');

        $this->info("🔍 Starting stretcher request monitoring (checking every {$interval} seconds)...");

        while (true) {
            try {
                $this->checkForNewRequests();
                sleep($interval);
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
                Log::error('Stretcher monitoring error: ' . $e->getMessage());
                sleep($interval);
            }
        }
    }

    /**
     * Check for new stretcher requests
     */
    private function checkForNewRequests()
    {
        try {
            // Get the last checked ID from cache
            $lastCheckedId = Cache::get('stretcher_last_checked_id', 0);

            // Query for new requests
            $newRequests = DB::connection('pgsql')
                ->table('my_stretcher')
                ->where('stretcher_register_id', '>', $lastCheckedId)
                ->where('stretcher_register_date', Carbon::now()->format('Y-m-d'))
                ->where('stretcher_work_status_id', 1) // only new requests
                ->whereNull('stretcher_team_list_id') // not assigned yet
                ->orderBy('stretcher_register_id')
                ->get();

            if ($newRequests->count() > 0) {
                $this->info("🆕 Found {$newRequests->count()} new request(s)");

                foreach ($newRequests as $request) {
                    $this->processNewRequest($request);

                    // Update last checked ID
                    Cache::put('stretcher_last_checked_id', $request->stretcher_register_id, now()->addDays(7));
                }

                $this->info("✅ Processed all new requests");
            } else {
                $this->line("📊 No new requests found (last ID: {$lastCheckedId})");
            }
        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
            Log::error('Database check error: ' . $e->getMessage());
        }
    }

    /**
     * Process a new request
     */
    private function processNewRequest($request)
    {
        try {
            $this->info("📋 Processing request ID: {$request->stretcher_register_id}");

            // Convert to array
            $requestData = (array) $request;

            // Fire Laravel Event
            broadcast(new NewStretcherRequest($requestData));

            // Send notifications directly
            $this->sendNotifications($requestData);

            // Log the process
            Log::info('✅ New stretcher request processed', [
                'request_id' => $request->stretcher_register_id,
                'hn' => $request->hn,
                'priority' => $request->stretcher_priority_name
            ]);

            $this->info("✅ Request {$request->stretcher_register_id} processed successfully");
        } catch (\Exception $e) {
            $this->error("❌ Failed to process request {$request->stretcher_register_id}: " . $e->getMessage());
            Log::error('Failed to process new request', [
                'request_id' => $request->stretcher_register_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notifications
     */
    private function sendNotifications($requestData)
    {
        try {
            // Build message
            $message = $this->buildNotificationMessage($requestData);



            // If urgent, send multiple times
            if (in_array($requestData['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน'])) {
                $this->sendUrgentNotifications($requestData, $message);
            } else {
                // Send to Telegram
                $this->sendTelegram($message);

                // Send to MoPH
                $this->sendMoPH($message);
            }
        } catch (\Exception $e) {
            $this->error("❌ Notification error: " . $e->getMessage());
            Log::error('Notification sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Build notification message
     */
    private function buildNotificationMessage($request)
    {
        $urgencyIcon = match ($request['stretcher_priority_name']) {
            'ด่วนที่สุด' => '🔴⚡⚡⚡',
            'ด่วน' => '🟠⚡⚡',
            'ปกติ' => '🟡',
            'ไม่ด่วน' => '🟢',
            default => '⭕'
        };

        $message = "🚨 มีการขอเปลใหม่!\n";
        $message .= "=================================\n";
        $message .= "🆔 ID: {$request['stretcher_register_id']}\n";
        $message .= "🏥 HN: {$request['hn']}\n";
        $message .= "👤 ชื่อ-นามสกุล: {$request['pname']}{$request['fname']} {$request['lname']}\n";
        $message .= "{$urgencyIcon} ความเร่งด่วน: {$request['stretcher_priority_name']}\n";
        $message .= "🛏️ ประเภทเปล: {$request['stretcher_type_name']}\n";

        if (!empty($request['stretcher_o2tube_type_name'])) {
            $message .= "🫁 ออกซิเจน: {$request['stretcher_o2tube_type_name']}\n";
        }

        if (!empty($request['stretcher_emergency_name'])) {
            $message .= "🚨 ฉุกเฉิน: {$request['stretcher_emergency_name']}\n";
        }

        $message .= "🏠 จากแผนก: {$request['department']}\n";
        $message .= "🎯 ไปแผนก: {$request['department2']}\n";
        $message .= "👨‍⚕️ ผู้ขอเปล: {$request['dname']}\n";

        if (!empty($request['from_note'])) {
            $message .= "📝 หมายเหตุ: {$request['from_note']}\n";
        }

        $message .= "🕐 เวลา: " . Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])->format('d/m/Y H:i:s') . "\n";
        $message .= "=================================\n";
        $message .= "กรุณารับงานด่วน!";

        return $message;
    }

    /**
     * Send urgent notifications (multiple times)
     */
    private function sendUrgentNotifications($requestData, $message)
    {
        $urgentMessage = "🚨🚨🚨 URGENT ALERT 🚨🚨🚨\n";
        $urgentMessage .= $message;
        $urgentMessage .= "\n⚠️⚠️ กรุณารับงานทันที! ⚠️⚠️";

        $this->warn("🚨 Sending urgent notifications for request {$requestData['stretcher_register_id']}");
        $this->sendTelegram($urgentMessage);
        $this->sendMoPH($urgentMessage);
        // Send 3 times with delay
        for ($i = 0; $i < 3; $i++) {
            $this->sendTelegram($urgentMessage);
            $this->sendMoPH($urgentMessage);

            if ($i < 2) {
                $this->info("⏳ Waiting 3 seconds before next urgent notification...");
                sleep(3);
            }
        }
    }

    /**
     * Send Telegram notification
     */
    private function sendTelegram($message)
    {
        try {
            $token = env('TELEGRAM_TOKEN');
            $chatId = env('TELEGRAM_CHATID');

            if (!$token || !$chatId) {
                $this->warn('⚠️ Telegram credentials not configured');
                return false;
            }

            $url = "https://api.telegram.org/bot{$token}/sendMessage";

            $response = Http::timeout(10)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                $this->info('✅ Telegram notification sent');
                return true;
            } else {
                $this->error('❌ Telegram API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            $this->error('❌ Telegram failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send MoPH notification
     */
    private function sendMoPH($message)
    {
        try {
            $client_id = env('MOPH_CLIENT');
            $secret_key = env('MOPH_SECRET');

            if (!$client_id || !$secret_key) {
                $this->warn('⚠️ MoPH credentials not configured');
                return false;
            }

            $url = 'https://morpromt2f.moph.go.th/api/notify/send';
            $data = [
                "messages" => [
                    [
                        "type" => "text",
                        "text" => $message
                    ]
                ]
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'client-key' => $client_id,
                    'secret-key' => $secret_key,
                ])
                ->withOptions(['verify' => false])
                ->post($url, $data);

            if ($response->successful()) {
                $this->info('✅ MoPH notification sent');
                return true;
            } else {
                $this->error('❌ MoPH API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            $this->error('❌ MoPH failed: ' . $e->getMessage());
            return false;
        }
    }
}
