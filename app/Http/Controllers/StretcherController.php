<?php
// app/Http/Controllers/StretcherController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StretcherController extends Controller
{
    public function dashboard()
    {
        return view('stretcher.dashboard');
    }

    public function publicView()
    {
        return view('stretcher.public-view');
    }
}