<?php
// app/Http/Middleware/CheckLogin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('name') && Session::has('doctorcode')) {
            return $next($request);
        }

        return redirect()->route('login.form')->with('error', 'กรุณาเข้าสู่ระบบก่อน');
    }
}