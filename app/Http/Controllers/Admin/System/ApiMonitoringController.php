<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\System\ApiMonitoringService;

class ApiMonitoringController extends Controller
{
    public function index(ApiMonitoringService $monitor)
    {
        return view('system.api-monitoring', $monitor->dashboard());
    }

    public function test(string $provider, ApiMonitoringService $monitor)
    {
        try {
            $log = $monitor->test($provider);
            return back()->with($log->successful ? 'status' : 'error', $log->successful
                ? "Connection successful in {$log->duration_ms}ms."
                : "Connection failed: " . ($log->error_message ?: 'Unknown error'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
