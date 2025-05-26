<?php
// app/Http/Middleware/StretcherAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class StretcherAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('name') || !Session::has('user_type')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}