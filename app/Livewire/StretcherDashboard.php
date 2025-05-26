<?php
// app/Livewire/StretcherDashboard.php

namespace App\Livewire;

use App\Models\MyStretcher;
use App\Models\StretcherRegister;
use App\Events\StretcherUpdated;
use App\Services\NotificationService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class StretcherDashboard extends Component
{
    public $hideCompleted = false;
    public $showMyTasks = false;
    public $stretcherRequests;

    protected $listeners = [
        'refreshData' => 'loadData',
        'echo:stretcher-updates,StretcherUpdated' => 'handleStretcherUpdate'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $query = MyStretcher::today()
            ->orderBy('stretcher_register_id', 'DESC');

        if ($this->hideCompleted) {
            $query->where('stretcher_work_status_id', '!=', 4);
        }

        if ($this->showMyTasks && Session::has('userid')) {
            $query->where('stretcher_team_list_id', Session::get('userid'));
        }

        $this->stretcherRequests = $query->get();
    }

    public function handleStretcherUpdate($event)
    {
        Log::info('Received stretcher update via WebSocket', $event);
        
        // Refresh data
        $this->loadData();
        
        // Emit browser events based on action
        switch($event['action']) {
            case 'new':
                $this->dispatch('new-stretcher-request', stretcher: $event['stretcher']);
                break;
            case 'accepted':
                $this->dispatch('stretcher-accepted', 
                    stretcher: $event['stretcher'], 
                    teamName: $event['team_name']
                );
                break;
            case 'sent':
                $this->dispatch('stretcher-sent', stretcher: $event['stretcher']);
                break;
            case 'completed':
                $this->dispatch('stretcher-completed', stretcher: $event['stretcher']);
                break;
        }
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

            // Check if user has pending tasks
            $pendingTasks = StretcherRegister::forTeam($userId)
                ->whereNotIn('stretcher_work_status_id', [4, 5])
                ->count();

            if ($pendingTasks > 0) {
                $this->dispatch('al-error', message: 'คุณมีงานค้างอยู่');
                return;
            }

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
            
        } catch (\Exception $e) {
            Log::error('Accept stretcher failed: ' . $e->getMessage());
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด');
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
            
        } catch (\Exception $e) {
            Log::error('Send stretcher failed: ' . $e->getMessage());
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด');
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
            
        } catch (\Exception $e) {
            Log::error('Complete stretcher failed: ' . $e->getMessage());
            $this->dispatch('al-error', message: 'เกิดข้อผิดพลาด');
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