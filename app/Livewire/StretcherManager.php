<?php

namespace App\Livewire;

use Carbon\Carbon;
use Exception;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Events\StretcherUpdated;
use App\Events\NewStretcherRequest;
use App\Events\StretcherStatusChanged;

class StretcherManager extends Component
{
    public $hideCompleted = false;
    public $showMyOnly = false;
    public $data = [];
    public $previousCount = 0;
    public $currentUserId;
    public $currentUserName;
    public $userType;
    public $showNotification = false;
    public $notificationMessage = '';
    public $notificationType = 'info';
    public $lastUpdate; // เพิ่ม property สำหรับ track การ update

    protected $listeners = [

        'refreshData' => 'loadData', // เพิ่ม listener สำหรับ refresh
    ];

    public function mount()
    {
        try {
            $this->currentUserId = Session::get('userid');
            $this->currentUserName = Session::get('name');
            $this->userType = Session::get('user_type', 'admin');
            $this->lastUpdate = now()->format('H:i:s');

            Log::info('StretcherManager Mount', [
                'user_id' => $this->currentUserId,
                'user_name' => $this->currentUserName,
                'user_type' => $this->userType
            ]);

            $this->loadData();
            $this->previousCount = count($this->data);
        } catch (Exception $e) {
            Log::error('StretcherManager Mount Error: ' . $e->getMessage());
            $this->showError('ไม่สามารถโหลดข้อมูลได้: ' . $e->getMessage());
        }
    }

    // ===================================================================
    // 🎯 Enhanced Helper Methods for UI
    // ===================================================================

    /**
     * Get priority CSS class based on priority name
     */
    public function getPriorityClass($priorityName)
    {
        $priorityClasses = [
            'ด่วนที่สุด' => 'priority-critical',
            'ด่วน' => 'priority-urgent',
            'ปกติ' => 'priority-normal',
            'ไม่ด่วน' => 'priority-low'
        ];

        return $priorityClasses[$priorityName] ?? 'priority-normal';
    }

    /**
     * Get urgency CSS class for special cases
     */
    public function getUrgencyClass($request)
    {
        $isEmergency = !empty($request['stretcher_emergency_name']);
        $isUrgent = in_array($request['stretcher_priority_name'], ['ด่วนที่สุด', 'ด่วน']);

        if ($isEmergency || $isUrgent) {
            return 'urgent-request';
        }

        return '';
    }

    /**
     * Get status badge configuration
     */
    public function getStatusConfig($statusId)
    {
        $statusConfig = [
            1 => [
                'class' => 'status-waiting',
                'icon' => 'fas fa-clock',
                'text' => 'รอรับงาน',
                'color' => '#6b7280'
            ],
            2 => [
                'class' => 'status-accepted',
                'icon' => 'fas fa-hand-paper',
                'text' => 'รับงานแล้ว',
                'color' => '#f59e0b'
            ],
            3 => [
                'class' => 'status-progress',
                'icon' => 'fas fa-running',
                'text' => 'กำลังดำเนินการ',
                'color' => '#06b6d4'
            ],
            4 => [
                'class' => 'status-completed',
                'icon' => 'fas fa-check-circle',
                'text' => 'สำเร็จ',
                'color' => '#10b981'
            ],
            5 => [
                'class' => 'status-cancelled',
                'icon' => 'fas fa-times-circle',
                'text' => 'อื่นๆ',
                'color' => '#ef4444'
            ]
        ];

        return $statusConfig[$statusId] ?? $statusConfig[1];
    }

    /**
     * Format time difference in Thai
     */
    public function formatTimeDifference($dateTime)
    {
        $carbon = Carbon::parse($dateTime);
        $now = Carbon::now();

        $diffInMinutes = $carbon->diffInMinutes($now);
        $diffInHours = $carbon->diffInHours($now);
        $diffInDays = $carbon->diffInDays($now);

        if ($diffInMinutes < 1) {
            return 'เมื่อสักครู่';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' นาทีที่แล้ว';
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ชั่วโมงที่แล้ว';
        } else {
            return $diffInDays . ' วันที่แล้ว';
        }
    }

    /**
     * Get request urgency level (1-5, 5 being most urgent)
     */
    public function getUrgencyLevel($request)
    {
        $urgencyLevel = 1;

        // Base urgency on priority
        switch ($request['stretcher_priority_name']) {
            case 'ด่วนที่สุด':
                $urgencyLevel = 5;
                break;
            case 'ด่วน':
                $urgencyLevel = 4;
                break;
            case 'ปกติ':
                $urgencyLevel = 2;
                break;
            case 'ไม่ด่วน':
                $urgencyLevel = 1;
                break;
        }

        // Increase urgency if emergency
        if (!empty($request['stretcher_emergency_name'])) {
            $urgencyLevel = 5;
        }

        // Increase urgency based on waiting time
        $waitingMinutes = Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])
            ->diffInMinutes(Carbon::now());

        if ($waitingMinutes > 60) {
            $urgencyLevel = min(5, $urgencyLevel + 1);
        }

        return $urgencyLevel;
    }

    /**
     * Sort requests by urgency and time
     */
    public function sortRequestsByUrgency($data)
    {
        return collect($data)->sortBy(function ($request) {
            $urgencyLevel = $this->getUrgencyLevel($request);
            $timeStamp = Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])->timestamp;

            // Higher urgency first, then older requests first
            return [$urgencyLevel * -1, $timeStamp];
        })->values()->toArray();
    }

    /**
     * Get department color coding
     */
    public function getDepartmentColor($departmentName)
    {
        $departmentColors = [
            'อุบัติเหตุฉุกเฉิน' => '#ef4444',
            'ห้องผ่าตัด' => '#f59e0b',
            'ICU' => '#dc2626',
            'CCU' => '#dc2626',
            'หอผู้ป่วยใน' => '#06b6d4',
            'อายุรกรรม' => '#10b981',
            'ศัลยกรรม' => '#8b5cf6',
            'กุมารเวชกรรม' => '#ec4899',
            'สูติกรรม' => '#f97316',
            'ทันตกรรม' => '#84cc16',
            'จักษุกรรม' => '#06b6d4',
            'หู คอ จมูก' => '#6366f1',
            'กายภาพบำบัด' => '#10b981',
            'ห้องเอกซเรย์' => '#64748b',
            'ห้องตรวจหัวใจ' => '#dc2626',
            'ห้องไตเทียม' => '#0891b2'
        ];

        return $departmentColors[$departmentName] ?? '#6b7280';
    }

    /**
     * Get equipment icon based on type
     */
    public function getEquipmentIcon($equipmentType)
    {
        $equipmentIcons = [
            'เปลธรรมดา' => 'fas fa-bed',
            'เปล ICU' => 'fas fa-procedures',
            'เปลพิเศษ' => 'fas fa-wheelchair',
            'เปลฉุกเฉิน' => 'fas fa-ambulance',
            'เปลผ่าตัด' => 'fas fa-user-md',
            'O2 Mask' => 'fas fa-lungs',
            'O2 Cannula' => 'fas fa-wind',
            'Ventilator' => 'fas fa-heartbeat'
        ];

        return $equipmentIcons[$equipmentType] ?? 'fas fa-bed';
    }

    /**
     * Check if request needs immediate attention
     */
    public function needsImmediateAttention($request)
    {
        $urgencyLevel = $this->getUrgencyLevel($request);
        $waitingMinutes = Carbon::parse($request['stretcher_register_date'] . ' ' . $request['stretcher_register_time'])
            ->diffInMinutes(Carbon::now());

        return $urgencyLevel >= 4 || $waitingMinutes > 30;
    }

    /**
     * Get estimated completion time
     */
    public function getEstimatedCompletionTime($request)
    {
        $baseTime = 15; // Base time in minutes

        // Adjust based on priority
        switch ($request['stretcher_priority_name']) {
            case 'ด่วนที่สุด':
                $baseTime = 5;
                break;
            case 'ด่วน':
                $baseTime = 10;
                break;
            case 'ปกติ':
                $baseTime = 15;
                break;
            case 'ไม่ด่วน':
                $baseTime = 20;
                break;
        }

        // Adjust based on equipment type
        if (strpos($request['stretcher_type_name'], 'ICU') !== false) {
            $baseTime += 10;
        }

        if (!empty($request['stretcher_o2tube_type_name'])) {
            $baseTime += 5;
        }

        return $baseTime;
    }

    /**
     * Enhanced notification system
     */
    public function showEnhancedNotification($message, $type = 'info', $autoHide = true)
    {
        $this->showNotification = true;
        $this->notificationMessage = $message;
        $this->notificationType = $type;

        // Auto-hide after delay
        if ($autoHide) {
            $this->dispatch('auto-hide-notification', ['delay' => 5000]);
        }
    }

    /**
     * Enhanced error handling with user-friendly messages
     */
    public function handleError($exception, $userMessage = null)
    {
        Log::error('Stretcher Manager Error: ' . $exception->getMessage(), [
            'exception' => $exception,
            'user_id' => $this->currentUserId,
            'timestamp' => now()
        ]);

        $message = $userMessage ?: 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
        $this->showEnhancedNotification($message, 'error');
    }

    // ===================================================================
    // 🎯 Laravel Echo Event Listeners
    // ===================================================================

    #[On('echo:stretcher-updates,StretcherUpdated')]
    public function handleStretcherUpdated($event)
    {
        Log::info('🔄 Received StretcherUpdated event', $event);

        // แสดงการแจ้งเตือน
        $teamMember = $event['data']['team_member'] ?? 'ระบบ';
        $action = $event['action'] ?? 'อัปเดต';

        $actionText = [
            'accepted' => 'รับงาน',
            'sent' => 'ไปรับผู้ป่วย',
            'completed' => 'งานสำเร็จ'
        ];

        $this->showEnhancedNotification(
            "{$teamMember} {$actionText[$action]} รายการ ID: {$event['stretcher_id']}",
            'success'
        );

        // ส่ง event ไปยัง JavaScript
        $this->dispatch('stretcher-item-updated', [
            'stretcherId' => $event['stretcher_id'],
            'action' => $action,
            'teamMember' => $teamMember
        ]);

        // รีเฟรชข้อมูล - ใช้หลายวิธี
        $this->forceRefresh();
    }

    #[On('echo:stretcher-updates,NewStretcherRequest')]
    public function handleNewRequest($event)
    {
        Log::info('🔔 Received NewStretcherRequest event', $event);

        $request = $event['request'];

         $this->sendNewRequestNotification($request);

        $this->showEnhancedNotification(
            "มีรายการขอเปลใหม่: HN {$request['hn']} - {$request['pname']}{$request['fname']} {$request['lname']}",
            'info'
        );

        $this->dispatch('new-request-arrived', [
            'request' => $request
        ]);

        $this->forceRefresh();
    }

    private function sendNewRequestNotification($request)
    {
        try {
            // สร้างข้อความแจ้งเตือน
            $message = "🚨 มีการขอเปลใหม่!\n";
            $message .= "=================================\n";
            $message .= "🏥 HN: {$request['hn']}\n";
            $message .= "👤 ชื่อ-นามสกุล: {$request['pname']}{$request['fname']} {$request['lname']}\n";
            $message .= "⚡ ความเร่งด่วน: {$request['stretcher_priority_name']}\n";
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

            // ส่งไป Telegram
            $this->sendTelegramNotification($message);

            // ส่งไป MoPH Notify
            $this->sendMorphromNotification($message);

            Log::info('✅ New request notification sent successfully', [
                'request_id' => $request['stretcher_register_id'],
                'hn' => $request['hn']
            ]);
        } catch (Exception $e) {
            Log::error('❌ Send new request notification error: ' . $e->getMessage(), [
                'request' => $request,
                'error' => $e->getTraceAsString()
            ]);
        }
    }

    #[On('echo:stretcher-updates,StretcherStatusChanged')]
    public function handleStatusChanged($event)
    {
        Log::info('📊 Received StretcherStatusChanged event', $event);

        $statusNames = [
            1 => 'รอรับงาน',
            2 => 'รับงานแล้ว',
            3 => 'กำลังดำเนินการ',
            4 => 'สำเร็จ',
            5 => 'อื่นๆ'
        ];

        $newStatusName = $statusNames[$event['new_status']] ?? 'ไม่ทราบ';
        $teamMember = $event['team_member'] ?? 'ระบบ';

        $this->showEnhancedNotification(
            "{$teamMember} เปลี่ยนสถานะเป็น '{$newStatusName}'",
            'warning'
        );

        $this->dispatch('status-change-detected', [
            'stretcherId' => $event['stretcher_id'],
            'oldStatus' => $event['old_status'],
            'newStatus' => $event['new_status'],
            'teamMember' => $teamMember
        ]);

        $this->forceRefresh();
    }

    // ===================================================================
    // 🔧 Core Functions
    // ===================================================================

    #[On('loadData')]
    public function loadData()
    {
        try {
            Log::info('🔄 Loading data...');

            // ใช้ Raw Query เพื่อ debug และเสถียรภาพ
            $this->data = DB::connection('pgsql')
                ->select("
                    SELECT * FROM my_stretcher 
                    WHERE stretcher_register_date = ? 
                    ORDER BY stretcher_register_id DESC
                ", [Carbon::now()->format('Y-m-d')]);

            // Convert to array
            $this->data = collect($this->data)->map(function ($item) {
                return (array) $item;
            })->toArray();

            // Sort by urgency and time
           /*  $this->data = $this->sortRequestsByUrgency($this->data); */

            $this->lastUpdate = now()->format('H:i:s');
            Log::info('✅ Data loaded successfully', [
                'count' => count($this->data),
                'time' => $this->lastUpdate
            ]);
        } catch (Exception $e) {
            Log::error('❌ Load Data Error: ' . $e->getMessage());
            $this->data = [];
            $this->handleError($e, 'ไม่สามารถโหลดข้อมูลได้');
        }
    }

    // เพิ่ม method สำหรับ force refresh
    public function forceRefresh()
    {
        Log::info('🔄 Force refresh triggered');
        $this->loadData();

        // ส่งสัญญาณไปยัง frontend ให้ refresh
        $this->dispatch('data-refreshed', [
            'timestamp' => now()->toISOString(),
            'count' => count($this->data)
        ]);
    }

    public function acceptRequest($requestId)
    {
        try {
            Log::info('🎯 Accept Request Attempt', [
                'request_id' => $requestId,
                'user_id' => $this->currentUserId,
                'user_name' => $this->currentUserName
            ]);

            // ตรวจสอบ user ID
            if (!$this->currentUserId) {
                Log::warning('No user ID in session');
                $this->handleError(new Exception('ไม่พบข้อมูลผู้ใช้'), 'ไม่พบข้อมูลผู้ใช้ กรุณาเข้าสู่ระบบใหม่');
                return;
            }

            // ตรวจสอบว่า request ยังว่างอยู่หรือไม่
            $currentRequest = DB::connection('pgsql')
                ->table('stretcher_register')
                ->where('stretcher_register_id', $requestId)
                ->first();

            if (!$currentRequest) {
                $this->handleError(new Exception('ไม่พบรายการ'), 'ไม่พบรายการนี้ในระบบ');
                return;
            }

            if ($currentRequest->stretcher_team_list_id) {
                $this->handleError(new Exception('รายการถูกรับแล้ว'), 'รายการนี้มีคนรับไปแล้ว');
                $this->forceRefresh(); // Force refresh
                return;
            }

            Log::info('✅ Request validation passed', [
                'request_id' => $requestId,
                'current_team' => $currentRequest->stretcher_team_list_id,
                'status' => $currentRequest->stretcher_work_status_id
            ]);

            // ทำการ update ด้วย transaction และ explicit commit
            $updated = DB::connection('pgsql')->transaction(function () use ($requestId) {
                $updated = DB::connection('pgsql')
                    ->table('stretcher_register')
                    ->where('stretcher_register_id', $requestId)
                    ->whereNull('stretcher_team_list_id') // Double check
                    ->update([
                        'stretcher_register_accept_date' => date('Y-m-d'),
                        'stretcher_register_accept_time' => date('H:i:s'),
                        'stretcher_service_id' => 3,
                        'stretcher_work_status_id' => 2,
                        'lastupdate' => date('Y-m-d H:i:s'),
                        'stretcher_team_list_id' => $this->currentUserId
                    ]);

                if ($updated === 0) {
                    throw new Exception('ไม่สามารถอัปเดตได้ อาจมีคนรับไปแล้ว');
                }

                Log::info('✅ Database updated successfully', [
                    'request_id' => $requestId,
                    'updated_rows' => $updated
                ]);

                return $updated;
            });

            // ส่งการแจ้งเตือน
            $this->sendAcceptedNotification($requestId);

            // Broadcast events ให้ผู้ใช้คนอื่น
            try {
                broadcast(new StretcherUpdated($requestId, 'accepted', [
                    'team_member' => $this->currentUserName,
                    'team_id' => $this->currentUserId,
                    'status' => 'รับงานแล้ว',
                    'timestamp' => now()->toISOString()
                ], $this->currentUserId));

                broadcast(new StretcherStatusChanged($requestId, 1, 2, $this->currentUserName));

                Log::info('✅ Broadcasting events sent successfully', ['request_id' => $requestId]);
            } catch (Exception $e) {
                Log::warning('⚠️ Broadcasting failed: ' . $e->getMessage());
            }

            $this->showEnhancedNotification('รับงานสำเร็จ! ID: ' . $requestId, 'success');

            // Force refresh แทนการใช้ loadData() ธรรมดา
            $this->forceRefresh();

            // ส่ง event ไปยัง JavaScript พร้อม delay เล็กน้อย
            $this->dispatch('job-accepted-successfully', [
                'requestId' => $requestId,
                'teamMember' => $this->currentUserName,
                'timestamp' => now()->toISOString()
            ]);

            // เพิ่ม delay แล้ว refresh อีกครั้ง เพื่อให้แน่ใจ
            $this->dispatch('delayed-refresh', ['delay' => 1000]);
        } catch (Exception $e) {
            Log::error('❌ Accept Request Error', [
                'request_id' => $requestId,
                'user_id' => $this->currentUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleError($e, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->forceRefresh(); // Refresh หลังจาก error
        }
    }

    private function sendAcceptedNotification($requestId)
    {
        try {
            // หาข้อมูลจาก read database
            $data = DB::connection('pgsql')
                ->table('my_stretcher')
                ->where('stretcher_register_id', $requestId)
                ->first();

            if ($data) {
                $message = "✅ รับแล้ว {$this->currentUserName}\n";
                $message .= "-----------------------------------------\n";
                $message .= "HN: {$data->hn}\n";
                $message .= "ชื่อ-นามสกุล: {$data->pname}{$data->fname} {$data->lname}\n";
                $message .= "ผู้ขอเปล: {$data->dname}\n";
                $message .= "-----------------------------------------";

                $this->sendTelegramNotification($message);
                $this->sendMorphromNotification($message);

                Log::info('✅ Notification sent successfully', ['request_id' => $requestId]);
            }
        } catch (Exception $e) {
            Log::error('❌ Send notification error: ' . $e->getMessage());
        }
    }

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

            $response = Http::timeout(10)->post($url, [
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
        } catch (Exception $e) {
            Log::error('❌ Telegram notification failed: ' . $e->getMessage());
        }
    }

    /**
     * ส่งแจ้งเตือน MoPH Notify (ปรับปรุง)
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

            $response = Http::timeout(10)
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
        } catch (Exception $e) {
            Log::error('❌ MorProm notification failed: ' . $e->getMessage());
        }
    }

    public function sendToPatient($requestId)
    {
        try {
            $updated = DB::connection('pgsql')
                ->table('stretcher_register')
                ->where('stretcher_register_id', $requestId)
                ->where('stretcher_team_list_id', $this->currentUserId)
                ->update([
                    'stretcher_register_send_time' => date('H:i:s'),
                    'stretcher_work_status_id' => 3,
                    'lastupdate' => date('Y-m-d H:i:s'),
                ]);

            if ($updated) {
                try {
                    broadcast(new StretcherUpdated($requestId, 'sent', [
                        'team_member' => $this->currentUserName,
                        'status' => 'กำลังดำเนินการ'
                    ], $this->currentUserId));

                    broadcast(new StretcherStatusChanged($requestId, 2, 3, $this->currentUserName));
                } catch (Exception $e) {
                    Log::warning('Broadcasting failed: ' . $e->getMessage());
                }

                $this->showEnhancedNotification('อัปเดตสถานะสำเร็จ', 'success');
                $this->forceRefresh();
            } else {
                $this->handleError(new Exception('ไม่สามารถอัปเดตได้'), 'ไม่สามารถอัปเดตได้');
            }
        } catch (Exception $e) {
            Log::error('Send to patient failed: ' . $e->getMessage());
            $this->handleError($e, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function completeTask($requestId)
    {
        try {
            $updated = DB::connection('pgsql')
                ->table('stretcher_register')
                ->where('stretcher_register_id', $requestId)
                ->where('stretcher_team_list_id', $this->currentUserId)
                ->update([
                    'stretcher_register_return_time' => date('H:i:s'),
                    'stretcher_work_status_id' => 4,
                    'stretcher_work_result_id' => 2,
                    'lastupdate' => date('Y-m-d H:i:s'),
                ]);

            if ($updated) {
                try {
                    broadcast(new StretcherUpdated($requestId, 'completed', [
                        'team_member' => $this->currentUserName,
                        'status' => 'สำเร็จ'
                    ], $this->currentUserId));

                    broadcast(new StretcherStatusChanged($requestId, 3, 4, $this->currentUserName));
                } catch (Exception $e) {
                    Log::warning('Broadcasting failed: ' . $e->getMessage());
                }

                $this->showEnhancedNotification('งานสำเร็จ', 'success');
                $this->forceRefresh();
            } else {
                $this->handleError(new Exception('ไม่สามารถอัปเดตได้'), 'ไม่สามารถอัปเดตได้');
            }
        } catch (Exception $e) {
            Log::error('Complete task failed: ' . $e->getMessage());
            $this->handleError($e, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ===================================================================
    // 🔧 Utility Functions
    // ===================================================================

    private function showSuccess($message)
    {
        $this->showEnhancedNotification($message, 'success');
    }

    private function showError($message)
    {
        $this->showEnhancedNotification($message, 'error');
    }

    private function showNotification($title, $message, $type = 'info')
    {
        $this->showNotification = true;
        $this->notificationMessage = $title . ': ' . $message;
        $this->notificationType = $type;

        $this->dispatch('auto-hide-notification', ['delay' => 5000]);
    }

    public function hideNotification()
    {
        $this->showNotification = false;
        $this->notificationMessage = '';
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login.form');
    }

    // Legacy listeners for compatibility
    public function refreshComponent()
    {
        $this->forceRefresh();
    }

    public function handleStretcherUpdate($event)
    {
        $this->handleStretcherUpdated($event);
    }

    public function handleStatusChange($event)
    {
        $this->handleStatusChanged($event);
    }

    public function render()
    {
        // เรียก loadData() ก่อน render เพื่อให้แน่ใจว่าข้อมูลล่าสุด
     /*    $this->loadData(); */

        // คำนวณสถิติ
        $stats = [
            'total_today' => count($this->data),
            'my_accepted' => $this->currentUserId ? count(array_filter($this->data, function ($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId;
            })) : 0,
            'night_shift' => count(array_filter($this->data, function ($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '00:00:00' && $time <= '07:59:59';
            })),
            'morning_shift' => count(array_filter($this->data, function ($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '08:00:00' && $time <= '16:00:00';
            })),
            'afternoon_shift' => count(array_filter($this->data, function ($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '16:00:00' && $time <= '23:59:59';
            }))
        ];

        $pendingWorkCount = 0;
        if ($this->currentUserId) {
            $pendingWorkCount = count(array_filter($this->data, function ($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId &&
                    !in_array($item['stretcher_work_status_id'], [4, 5]);
            }));
        }

        // กรองข้อมูล
        $filteredData = $this->data;

        if ($this->showMyOnly && $this->currentUserId) {
            $filteredData = array_filter($filteredData, function ($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId;
            });
        }

        if ($this->hideCompleted) {
            $filteredData = array_filter($filteredData, function ($item) {
                return !in_array($item['stretcher_work_status_id'], [4, 5]);
            });
        }

        return view('livewire.stretcher-manager', [
            'data' => array_values($filteredData),
            'stats' => $stats,
            'pendingWorkCount' => $pendingWorkCount,
            'lastUpdate' => $this->lastUpdate
        ]);
    }
}
