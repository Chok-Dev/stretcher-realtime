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
        'EditConfirm' => 'EditConfirm',
        'EditConfirm2' => 'EditConfirm2',
        'EditConfirm3' => 'EditConfirm3',
        'EditConfirm4' => 'EditConfirm4',
        'EditConfirmDischarge' => 'EditConfirmDischarge',
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
        
        $this->showNotification('อัปเดตสถานะ', 
            "{$teamMember} {$actionText[$action]} รายการ ID: {$event['stretcher_id']}", 
            'success');
        
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
        $this->showNotification('รายการใหม่!', 
            "มีรายการขอเปลใหม่: HN {$request['hn']} - {$request['pname']}{$request['fname']} {$request['lname']}", 
            'info');
        
        $this->dispatch('new-request-arrived', [
            'request' => $request
        ]);
        
        $this->forceRefresh();
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
            5 => 'ยกเลิก'
        ];
        
        $newStatusName = $statusNames[$event['new_status']] ?? 'ไม่ทราบ';
        $teamMember = $event['team_member'] ?? 'ระบบ';
        
        $this->showNotification('เปลี่ยนสถานะ', 
            "{$teamMember} เปลี่ยนสถานะเป็น '{$newStatusName}'", 
            'warning');
        
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
            $this->data = collect($this->data)->map(function($item) {
                return (array) $item;
            })->toArray();

            $this->lastUpdate = now()->format('H:i:s');
            Log::info('✅ Data loaded successfully', [
                'count' => count($this->data),
                'time' => $this->lastUpdate
            ]);

        } catch (Exception $e) {
            Log::error('❌ Load Data Error: ' . $e->getMessage());
            $this->data = [];
            $this->showError('ไม่สามารถโหลดข้อมูลได้: ' . $e->getMessage());
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
                $this->showError('ไม่พบข้อมูลผู้ใช้ กรุณาเข้าสู่ระบบใหม่');
                return;
            }

            // ตรวจสอบว่า request ยังว่างอยู่หรือไม่
            $currentRequest = DB::connection('pgsql')
                ->table('stretcher_register')
                ->where('stretcher_register_id', $requestId)
                ->first();

            if (!$currentRequest) {
                $this->showError('ไม่พบรายการนี้ในระบบ');
                return;
            }

            if ($currentRequest->stretcher_team_list_id) {
                $this->showError('รายการนี้มีคนรับไปแล้ว');
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

            $this->showSuccess('รับงานสำเร็จ! ID: ' . $requestId);
            
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
            
            $this->showError('เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            if (!$token) return;

            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            Http::post($url, [
                'chat_id' => env('TELEGRAM_CHATID'),
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (Exception $e) {
            Log::error('Telegram notification failed: ' . $e->getMessage());
        }
    }

    private function sendMorphromNotification($message)
    {
        try {
            $client_id = env('MOPH_CLIENT');
            $secret_key = env('MOPH_SECRET');
            
            if (!$client_id || !$secret_key) return;

            $url = 'https://morpromt2f.moph.go.th/api/notify/send';
            $data = [
                "messages" => [
                    [
                        "type" => "text",
                        "text" => $message
                    ]
                ]
            ];

            Http::withHeaders([
                'Content-Type' => 'application/json',
                'client-key' => $client_id,
                'secret-key' => $secret_key,
            ])
            ->withOptions(['verify' => false])
            ->post($url, $data);
        } catch (Exception $e) {
            Log::error('MorProm notification failed: ' . $e->getMessage());
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

                $this->showSuccess('อัปเดตสถานะสำเร็จ');
                $this->forceRefresh();
            } else {
                $this->showError('ไม่สามารถอัปเดตได้');
            }
        } catch (Exception $e) {
            Log::error('Send to patient failed: ' . $e->getMessage());
            $this->showError('เกิดข้อผิดพลาด: ' . $e->getMessage());
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

                $this->showSuccess('งานสำเร็จ');
                $this->forceRefresh();
            } else {
                $this->showError('ไม่สามารถอัปเดตได้');
            }
        } catch (Exception $e) {
            Log::error('Complete task failed: ' . $e->getMessage());
            $this->showError('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    // ===================================================================
    // 🔧 Utility Functions
    // ===================================================================

    private function showSuccess($message)
    {
        $this->showNotification('สำเร็จ', $message, 'success');
    }

    private function showError($message)
    {
        $this->showNotification('เกิดข้อผิดพลาด', $message, 'error');
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
        $this->loadData();
        
        // คำนวณสถิติ
        $stats = [
            'total_today' => count($this->data),
            'my_accepted' => $this->currentUserId ? count(array_filter($this->data, function($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId;
            })) : 0,
            'night_shift' => count(array_filter($this->data, function($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '00:00:00' && $time <= '07:59:59';
            })),
            'morning_shift' => count(array_filter($this->data, function($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '08:00:00' && $time <= '16:00:00';
            })),
            'afternoon_shift' => count(array_filter($this->data, function($item) {
                $time = $item['stretcher_register_time'];
                return $time >= '16:00:00' && $time <= '23:59:59';
            }))
        ];

        $pendingWorkCount = 0;
        if ($this->currentUserId) {
            $pendingWorkCount = count(array_filter($this->data, function($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId && 
                       !in_array($item['stretcher_work_status_id'], [4, 5]);
            }));
        }

        // กรองข้อมูล
        $filteredData = $this->data;

        if ($this->showMyOnly && $this->currentUserId) {
            $filteredData = array_filter($filteredData, function($item) {
                return $item['stretcher_team_list_id'] == $this->currentUserId;
            });
        }

        if ($this->hideCompleted) {
            $filteredData = array_filter($filteredData, function($item) {
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