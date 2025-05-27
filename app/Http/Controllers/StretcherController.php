<?php
// app/Http/Controllers/StretcherController.php

namespace App\Http\Controllers;

use App\Models\MyStretcher;
use App\Models\StretcherRegister;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class StretcherController extends Controller
{
    public function dashboard()
    {
        if (!Session::has('userid') || !Session::has('name')) {
            return redirect()->route('login');
        }

        $data = $this->getDashboardData();
        return view('stretcher.dashboard', $data);
    }

    public function publicView()
    {
        $data = $this->getPublicViewData();
        return view('stretcher.public-view', $data);
    }

    // API Methods for AJAX calls
    public function getDashboardData(Request $request = null)
    {
        $hideCompleted = $request ? $request->get('hideCompleted', false) : false;
        $showMyTasks = $request ? $request->get('showMyTasks', false) : false;

        $query = MyStretcher::today()->orderBy('stretcher_register_id', 'DESC');

        if ($hideCompleted) {
            $query->where('stretcher_work_status_id', '!=', 4);
        }

        if ($showMyTasks && Session::has('userid')) {
            $query->where('stretcher_team_list_id', Session::get('userid'));
        }

        $stretcherRequests = $query->get();
        
        // Statistics
        $totalRequests = MyStretcher::today()->count();
        $myRequests = MyStretcher::today()
            ->where('stretcher_team_list_id', Session::get('userid'))
            ->count();

        $allRequests = MyStretcher::today();
        $shiftStats = [
            'night' => $allRequests->clone()->whereBetween('stretcher_register_time', ['00:00:00', '07:59:59'])->count(),
            'morning' => $allRequests->clone()->whereBetween('stretcher_register_time', ['08:00:00', '16:00:00'])->count(),
            'evening' => $allRequests->clone()->whereBetween('stretcher_register_time', ['16:00:00', '23:59:59'])->count(),
        ];

        $userPendingTasks = $stretcherRequests
            ->whereNotIn('stretcher_work_status_id', [4, 5])
            ->where('stretcher_team_list_id', Session::get('userid'))
            ->count();

        Log::info('Dashboard data loaded', [
            'count' => $stretcherRequests->count(),
            'hideCompleted' => $hideCompleted,
            'showMyTasks' => $showMyTasks
        ]);

        $data = compact(
            'stretcherRequests', 
            'totalRequests', 
            'myRequests', 
            'shiftStats', 
            'userPendingTasks'
        );

        // Return JSON for AJAX requests, array for regular requests
        return $request && $request->ajax() ? response()->json($data) : $data;
    }

    public function getPublicViewData(Request $request = null)
    {
        $stretcherRequests = MyStretcher::today()
            ->orderBy('stretcher_register_id', 'DESC')
            ->get()
            ->groupBy('stretcher_work_status_name');

        $totalRequests = MyStretcher::today()->count();
        $completedRequests = MyStretcher::today()->where('stretcher_work_status_id', 4)->count();
        $pendingRequests = MyStretcher::today()->whereIn('stretcher_work_status_id', [1, 2, 3])->count();

        $data = compact(
            'stretcherRequests',
            'totalRequests',
            'completedRequests', 
            'pendingRequests'
        );

        return $request && $request->ajax() ? response()->json($data) : $data;
    }

    public function acceptRequest(Request $request, $stretcherId)
    {
        try {
            $userId = Session::get('userid');
            $userName = Session::get('name');
            
            if (!$userId || !$userName) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้'
                ], 400);
            }

            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->whereNull('stretcher_team_list_id')
                ->first();

            if (!$stretcher) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายการหรือมีคนรับแล้ว'
                ], 404);
            }

            // Update with metadata to prevent Observer from broadcasting
            $stretcher->update([
                'stretcher_register_accept_date' => now()->format('Y-m-d'),
                'stretcher_register_accept_time' => now()->format('H:i:s'),
                'stretcher_service_id' => 3,
                'stretcher_work_status_id' => 2,
                'lastupdate' => now(),
                'stretcher_team_list_id' => $userId,
                '_skip_observer_broadcast' => true, // Flag to skip Observer broadcasting
            ]);

            // Get updated stretcher data for broadcasting
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcherId)->first();
            
            if ($stretcherData) {
                // Broadcast real-time update manually
                broadcast(new \App\Events\StretcherUpdated(
                    action: 'accepted',
                    stretcher: $stretcherData->toArray(),
                    teamName: $userName,
                    metadata: [
                        'accepted_by' => $userName,
                        'accepted_at' => now()->toISOString(),
                        'user_id' => $userId,
                        'source' => 'user_action'
                    ]
                ));

                // Send notification
                app(NotificationService::class)->sendAcceptedNotification($stretcherData, $userName);
            }

            Log::info('Stretcher accepted', [
                'stretcher_id' => $stretcherId,
                'user_id' => $userId,
                'user_name' => $userName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'รับงานสำเร็จ',
                'data' => $stretcherData ? $stretcherData->toArray() : null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Accept stretcher failed', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendRequest(Request $request, $stretcherId)
    {
        try {
            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->where('stretcher_team_list_id', Session::get('userid'))
                ->first();

            if (!$stretcher) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายการ'
                ], 404);
            }

            // Update with flag to prevent Observer from broadcasting
            $stretcher->update([
                'stretcher_register_send_time' => now()->format('H:i:s'),
                'stretcher_work_status_id' => 3,
                'lastupdate' => now(),
                '_skip_observer_broadcast' => true,
            ]);

            // Get updated stretcher data for broadcasting
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcherId)->first();
            
            if ($stretcherData) {
                // Broadcast real-time update manually
                broadcast(new \App\Events\StretcherUpdated(
                    action: 'sent',
                    stretcher: $stretcherData->toArray(),
                    teamName: $stretcherData->name,
                    metadata: [
                        'sent_at' => now()->toISOString(),
                        'user_id' => Session::get('userid'),
                        'send_time' => $stretcher->stretcher_register_send_time,
                        'source' => 'user_action'
                    ]
                ));
            }

            Log::info('Stretcher sent', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกสำเร็จ',
                'data' => $stretcherData ? $stretcherData->toArray() : null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Send stretcher failed', [
                'stretcher_id' => $stretcherId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    public function completeRequest(Request $request, $stretcherId)
    {
        try {
            $stretcher = StretcherRegister::where('stretcher_register_id', $stretcherId)
                ->where('stretcher_team_list_id', Session::get('userid'))
                ->first();

            if (!$stretcher) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายการ'
                ], 404);
            }

            $stretcher->update([
                'stretcher_register_return_time' => now()->format('H:i:s'),
                'stretcher_work_status_id' => 4,
                'stretcher_work_result_id' => 2,
                'lastupdate' => now(),
            ]);

            // Get updated stretcher data for broadcasting
            $stretcherData = MyStretcher::where('stretcher_register_id', $stretcherId)->first();
            
            if ($stretcherData) {
                // Broadcast real-time update
                broadcast(new \App\Events\StretcherUpdated(
                    action: 'completed',
                    stretcher: $stretcherData->toArray(),
                    teamName: $stretcherData->name,
                    metadata: [
                        'completed_at' => now()->toISOString(),
                        'user_id' => Session::get('userid'),
                        'return_time' => $stretcher->stretcher_register_return_time,
                        'total_duration' => $this->calculateDuration($stretcher)
                    ]
                ));
            }

            Log::info('Stretcher completed', [
                'stretcher_id' => $stretcherId,
                'user_id' => Session::get('userid')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'งานสำเร็จ',
                'data' => $stretcherData ? $stretcherData->toArray() : null
            ]);
            
        } catch (\Exception $e) {
            Log::error('Complete stretcher failed', [
                'stretcher_id' => $stretcherId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateDuration($stretcher)
    {
        if (!$stretcher->stretcher_register_time || !$stretcher->stretcher_register_return_time) {
            return null;
        }

        $start = \Carbon\Carbon::parse($stretcher->stretcher_register_date . ' ' . $stretcher->stretcher_register_time);
        $end = \Carbon\Carbon::parse($stretcher->stretcher_register_date . ' ' . $stretcher->stretcher_register_return_time);
        
        return $end->diffInMinutes($start);
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }
}