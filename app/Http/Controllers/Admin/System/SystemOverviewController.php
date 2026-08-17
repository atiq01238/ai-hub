<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AppError;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\System\ApiMonitoringService;
use App\Services\System\BackupService;
use App\Services\System\SystemHealthService;
use Illuminate\Support\Facades\Schema;

class SystemOverviewController extends Controller
{
    public function index(SystemHealthService $health, BackupService $backups, ApiMonitoringService $api)
    {
        $healthData = $health->snapshot();
        $backupItems = $backups->list();
        $apiData = $api->dashboard();

        $security = [
            'failed_24h' => Schema::hasTable('login_attempts') ? LoginAttempt::where('successful', false)->where('created_at', '>=', now()->subDay())->count() : 0,
            'admins_without_2fa' => Schema::hasTable('users') && Schema::hasColumn('users', 'two_factor_enabled')
                ? User::where('role', 'admin')->where('two_factor_enabled', false)->count() : 0,
            'open_errors' => Schema::hasTable('app_errors') ? AppError::where('status', '!=', 'resolved')->count() : 0,
        ];

        return view('system.overview', compact('healthData', 'backupItems', 'apiData', 'security'));
    }
}
