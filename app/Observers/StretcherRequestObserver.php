<?php
// app/Observers/StretcherRequestObserver.php

namespace App\Observers;

use App\Models\StretcherRegister;
use App\Events\NewStretcherRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StretcherRequestObserver
{
    /**
     * Handle the StretcherRegister "created" event.
     */
    public function created(StretcherRegister $stretcherRequest)
    {
        try {
            Log::info('🆕 New stretcher request detected', [
                'request_id' => $stretcherRequest->stretcher_register_id,
                'hn' => $stretcherRequest->hn,
                'priority' => $stretcherRequest->stretcher_priority_name
            ]);

            // แปลงข้อมูลเป็น array สำหรับ event
            $requestData = $stretcherRequest->notification_data;

            // Fire NewStretcherRequest event
            broadcast(new NewStretcherRequest($requestData));

            // ส่งแจ้งเตือนทันที
            $this->sendNotifications($requestData);

            Log::info('✅ New request event fired and notifications sent', [
                'request_id' => $stretcherRequest->stretcher_register_id
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to handle new stretcher request', [
                'request_id' => $stretcherRequest->stretcher_register_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * ส่งแจ้งเตือนสำหรับรายการใหม่
     */
    private function sendNotifications($requestData)
    {
        try {
            // สร้างข้อความแจ้งเตือน
            $message = $this->buildNewRequestMessage($requestData);

            // ส่งไป Telegram
            $this->sendTelegramNotification($message);

            // ส่งไป MoPH
            $this->sendMorphromNotification($message);

            // ถ้าเป็นเรื่องด่วน ส่งแจ้งเตือนเพิ่ม
            if (in_array($requestData['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน'])) {
                $this->sendUrgentNotifications($requestData, $message);
            }

        } catch (\Exception $e) {
            Log::error('❌ Failed to send notifications', [
                'error' => $e->getMessage(),
                'request_data' => $requestData
            ]);
        }
    }

    /**
     * สร้างข้อความแจ้งเตือน
     */
    private function buildNewRequestMessage($request)
    {
        $urgencyIcon = match($request['stretcher_priority_name']) {
            'ด่วนที่สุด' => '🔴⚡⚡',
            'ด่วน' => '🟠⚡',
            'ปกติ' => '🟡',
            'ไม่ด่วน' => '🟢',
            default => '⭕'
        };

        $message = "🚨 มีการขอเปลใหม่!\n";
        $message .= "=================================\n";
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
        
        $message .= "🕐 เวลา: " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= "=================================\n";
        $message .= "กรุณารับงานด่วน!";

        return $message;
    }

    /**
     * ส่งแจ้งเตือนฉุกเฉิน
     */
    private function sendUrgentNotifications($requestData, $message)
    {
        $urgentMessage = "🚨🚨 URGENT ALERT 🚨🚨r\n";
        $urgentMessage .= $message;
        $urgentMessage .= "\n⚠️ กรุณารับงานทันที!";

        // ส่งแจ้งเตือนฉุกเฉินหลายครั้ง
        for ($i = 0; $i < 3; $i++) {
            $this->sendTelegramNotification($urgentMessage);
            $this->sendMorphromNotification($urgentMessage);
            
            if ($i < 2) sleep(2); // รอ 2 วินาทีก่อนส่งครั้งต่อไป
        }
    }

    /**
     * ส่ง Telegram Notification
     */
    private function sendTelegramNotification($message)
    {
        try {
            $token = env('TELEGRAM_TOKEN');
            $chatId = env('TELEGRAM_CHATID');
            
            if (!$token || !$chatId) {
                Log::warning('⚠️ Telegram credentials not configured');
                return;
            }

            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            
            $response = \Illuminate\Support\Facades\Http::timeout(10)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                Log::info('✅ Telegram notification sent successfully');
            } else {
                Log::error('❌ Telegram API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Telegram notification failed: ' . $e->getMessage());
        }
    }

    /**
     * ส่ง MoPH Notification
     */
    private function sendMorphromNotification($message)
    {
        try {
            $client_id = env('MOPH_CLIENT');
            $secret_key = env('MOPH_SECRET');
            
            if (!$client_id || !$secret_key) {
                Log::warning('⚠️ MoPH credentials not configured');
                return;
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

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'client-key' => $client_id,
                    'secret-key' => $secret_key,
                ])
                ->withOptions(['verify' => false])
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('✅ MoPH notification sent successfully');
            } else {
                Log::error('❌ MoPH API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('❌ MorProm notification failed: ' . $e->getMessage());
        }
    }
}