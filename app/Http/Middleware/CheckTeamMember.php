<?php
// app/Http/Middleware/CheckTeamMember.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckTeamMember
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('userid')) {
            return $next($request);
        }

        return redirect()->route('dashboard')->with('info', 'คุณสามารถดูข้อมูลได้แต่ไม่สามารถรับงานได้');
    }
}