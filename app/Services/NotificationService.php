<?php
// app/Services/NotificationService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Phattarachai\LineNotify\Facade\Line;

class NotificationService
{
    public function sendTelegram(string $message): bool
    {
        try {
            $token = config('services.telegram.token');
            $chatId = config('services.telegram.chat_id');
            
            if (!$token || !$chatId) {
                Log::warning('Telegram credentials not configured');
                return false;
            }
            
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMorProm(string $message): bool
    {
        try {
            $clientId = config('services.morprom.client_id');
            $secretKey = config('services.morprom.secret_key');
            
            if (!$clientId || !$secretKey) {
                Log::warning('MorProm credentials not configured');
                return false;
            }
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'client-key' => $clientId,
                'secret-key' => $secretKey,
            ])
            ->withOptions(['verify' => false])
            ->post('https://morpromt2f.moph.go.th/api/notify/send', [
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message
                    ]
                ]
            ]);
            
            Log::info('MorProm response: ' . $response->body());
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('MorProm notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendLine(string $message): bool
    {
        try {
           /*  Line::send($message); */
            return true;
        } catch (\Exception $e) {
            /* Log::error('Line notification failed: ' . $e->getMessage()); */
            return false;
        }
    }

    public function sendNewRequestNotification($stretcher): void
    {
        $message = $this->formatNewRequestMessage($stretcher);
        
        $this->sendTelegram($message);
        $this->sendMorProm($message);
        // $this->sendLine($message); // uncomment if needed
    }

    public function sendAcceptedNotification($stretcher, string $teamName): void
    {
        $message = $this->formatAcceptedMessage($stretcher, $teamName);
        
        $this->sendTelegram($message);
        $this->sendMorProm($message);
    }

    private function formatNewRequestMessage($stretcher): string
    {
        return "🔔 *มีรายการขอเปลใหม่*
-----------------------------------------
HN: {$stretcher->hn}
ชื่อ-นามสกุล: {$stretcher->pname}{$stretcher->fname} {$stretcher->lname}
ประเภทเปล: {$stretcher->stretcher_type_name}
ประเภทออกซิเจน: {$stretcher->stretcher_o2tube_type_name}
ความเร่งด่วน: {$stretcher->stretcher_priority_name}
ผู้ขอเปล: {$stretcher->dname}
จากแผนก: {$stretcher->department}
ไปแผนก: {$stretcher->department2}
หมายเหตุ (1): {$stretcher->from_note}
หมายเหตุ (2): {$stretcher->send_note}";
    }

    private function formatAcceptedMessage($stretcher, string $teamName): string
    {
        return "✅ รับแล้ว {$teamName}
-----------------------------------------
HN: {$stretcher->hn}
ชื่อ-นามสกุล: {$stretcher->pname}{$stretcher->fname} {$stretcher->lname}
ผู้ขอเปล: {$stretcher->dname}
-----------------------------------------";
    }
}