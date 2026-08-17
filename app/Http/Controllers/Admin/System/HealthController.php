<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\System\SystemHealthService;

class HealthController extends Controller
{
    public function index(SystemHealthService $health)
    {
        return view('system.health', $health->snapshot());
    }
}
