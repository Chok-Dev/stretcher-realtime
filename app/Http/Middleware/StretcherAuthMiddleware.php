<?php
// app/Http/Middleware/StretcherAuthMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StretcherAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ตรวจสอบว่ามี session ของผู้ใช้หรือไม่
        if (!Session::has('userid') || !Session::has('name')) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'redirect' => route('login')
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        return $next($request);
    }
}