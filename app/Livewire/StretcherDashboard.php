<?php
// app/Livewire/StretcherDashboard.php

namespace App\Livewire;

use App\Models\MyStretcher;
use App\Models\StretcherRegister;
use App\Services\NotificationService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class StretcherDashboard extends Component
{
    public $hideCompleted = false;
    public $showMyTasks = false;
    public $stretcherRequests;

    protected $listeners = [
        'echo:stretcher-updates,StretcherUpdated' => 'handleStretcherUpdate',
        'refreshData' => 'loadData',
        'stretcher-data-updated' => 'loadData'
    ];

    public function mount()
    {
        $this->loadData();
        Log::info('StretcherDashboard mounted with user', ['user_id' => Session::get('userid')]);
    }

    public function loadData()
    {
        $query = MyStretcher::today()->orderBy('stretcher_register_id', 'DESC');

        if ($this->hideCompleted) {
            $query->where('stretcher_work_status_id', '!=', 4);
        }

        if ($this->showMyTasks && Session::has('userid')) {
            $query->where('stretcher_team_list_id', Session::get('userid'));
        }

        $this->stretcherRequests = $query->get();
        
        Log::info('Dashboard data loaded', [
            'count' => $this->stretcherRequests->count(),
            'hideCompleted' => $this->hideCompleted,
            'showMyTasks' => $this->showMyTasks
        ]);
    }

    public function handleStretcherUpdate($event)
    {
        Log::info('Dashboard received Echo event', [
            'event' => $event,
            'action' => $event['action'] ?? 'unknown'
        ]);
        
        // Refresh data
        $this->loadData();
        
        // Dispatch frontend events
        $this->dispatch('stretcher-data-updated', $event);
        
        // Dispatch specific action events
        if (isset($event['action'])) {
            switch($event['action']) {
                case 'new':
                    $this->dispatch('new-stretcher-request', $event);
                    break;
                case 'accepted':
                    $this->dispatch('stretcher-accepted', $event);
                    break;
                case 'sent':
                    $this->dispatch('stretcher-sent', $event);
                    break;
                case 'completed':
                    $this->dispatch('stretcher-completed', $event);
                    break;
            }
        }
    }

    #[On('refreshData')]
    public function refreshData()
    {
        Log::info('Dashboard manual refresh triggered');
        $this->loadData();
    }

    public function accept($stretcherId)
    {
        try {
            $userId = Session::get('userid');
            $userName = Session::get('name');
            
            if (!$userId || !$userName) {
                $this->dispatch('al-error', message: 'ไม่พบข้อมูลผู้ใช้');
                return;
            }

            // Check pending tasks
           /*  $pendingTasks = StretcherRegister::forTeam($userId)
                ->whereNotIn('stretcher_work_status_id', [4, 5])
                ->count();

            if ($pendingTasks > 0) {
                $this->dispatch('al-error', message: 'คุณมีงานค้างอยู่');
                return;
            } */

            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->whereNull('stretcher_team_list_id')
                ->first();

            if (!$stretcher) {
                $this->dispatch('al-error', message: 'ไม่พบรายการหรือมีคนรับแล้ว');
                return;
            }

            $stretcher->update([
                'stretcher_register_accept_date' => now()->format('Y-m-d'),
                'stretcher_register_accept_time' => now()->format('H:i:s'),
                'stretcher_service_id' => 3,
                'stretcher_work_status_id' => 2,
                'lastupdate' => now(),
                'stretcher_team_list_id' => $userId
            ]);

            // Send notification
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcherId)->first();
            if ($stretcherData) {
                app(NotificationService::class)->sendAcceptedNotification($stretcherData, $userName);
            }

            $this->dispatch('al-success', message: 'รับงานสำเร็จ');
            
            Log::info('Stretcher accepted', [
                'stretcher_id' => $stretcherId,
                'user_id' => $userId,
                'user_name' => $userName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Accept stretcher failed', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid'),
                'error' => $e->getMessage()
            ]);
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function send($stretcherId)
    {
        try {
            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->where('stretcher_team_list_id', Session::get('userid'))
                ->first();

            if (!$stretcher) {
                $this->dispatch('al-error', message: 'ไม่พบรายการ');
                return;
            }

            $stretcher->update([
                'stretcher_register_send_time' => now()->format('H:i:s'),
                'stretcher_work_status_id' => 3,
                'lastupdate' => now(),
            ]);

            $this->dispatch('al-success', message: 'บันทึกสำเร็จ');
            
            Log::info('Stretcher sent', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Send stretcher failed', [
                'stretcher_id' => $stretcherId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function complete($stretcherId)
    {
        try {
            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->where('stretcher_team_list_id', Session::get('userid'))
                ->first();

            if (!$stretcher) {
                $this->dispatch('al-error', message: 'ไม่พบรายการ');
                return;
            }

            $stretcher->update([
                'stretcher_register_return_time' => now()->format('H:i:s'),
                'stretcher_work_status_id' => 4,
                'stretcher_work_result_id' => 2,
                'lastupdate' => now(),
            ]);

            $this->dispatch('al-success', message: 'งานสำเร็จ');
            
            Log::info('Stretcher completed', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Complete stretcher failed', [
                'stretcher_id' => $stretcherId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }

    public function updatedHideCompleted()
    {
        $this->loadData();
    }

    public function updatedShowMyTasks()
    {
        $this->loadData();
    }

    // Property accessors
    public function getTotalRequestsProperty()
    {
        return MyStretcher::today()->count();
    }

    public function getMyRequestsProperty()
    {
        return MyStretcher::today()
            ->where('stretcher_team_list_id', Session::get('userid'))
            ->count();
    }

    public function getShiftStatsProperty()
    {
        $allRequests = MyStretcher::today();
        
        return [
            'night' => $allRequests->clone()->whereBetween('stretcher_register_time', ['00:00:00', '07:59:59'])->count(),
            'morning' => $allRequests->clone()->whereBetween('stretcher_register_time', ['08:00:00', '16:00:00'])->count(),
            'evening' => $allRequests->clone()->whereBetween('stretcher_register_time', ['16:00:00', '23:59:59'])->count(),
        ];
    }

    public function getUserPendingTasksProperty()
    {
        return $this->stretcherRequests
            ->whereNotIn('stretcher_work_status_id', [4, 5])
            ->where('stretcher_team_list_id', Session::get('userid'))
            ->count();
    }

    public function render()
    {
        return view('livewire.stretcher-dashboard');
    }
}