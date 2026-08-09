<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function index()
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkStorage(),
            $this->checkQueue(),
            $this->checkMail(),
            $this->checkDebugMode(),
        ];

        $passing = collect($checks)->where('status', 'pos')->count();
        $overallPercent = round(($passing / count($checks)) * 100);

        return view('system.health', compact('checks', 'overallPercent'));
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->getPdo();
            $ms = round((microtime(true) - $start) * 1000);

            return ['name' => 'Database', 'icon' => 'database', 'status' => 'pos', 'label' => 'Connected', 'detail' => "Responded in {$ms}ms"];
        } catch (\Exception $e) {
            return ['name' => 'Database', 'icon' => 'database', 'status' => 'neg', 'label' => 'Down', 'detail' => 'Could not connect'];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 5);
            $ok = Cache::get('health_check') === 'ok';

            return $ok
                ? ['name' => 'Cache', 'icon' => 'zap', 'status' => 'pos', 'label' => 'Working', 'detail' => 'Driver: ' . config('cache.default')]
                : ['name' => 'Cache', 'icon' => 'zap', 'status' => 'warn', 'label' => 'Unreliable', 'detail' => 'Write succeeded but read failed'];
        } catch (\Exception $e) {
            return ['name' => 'Cache', 'icon' => 'zap', 'status' => 'neg', 'label' => 'Down', 'detail' => 'Driver: ' . config('cache.default')];
        }
    }

    private function checkStorage(): array
    {
        $path = storage_path('app/public');

        if (! is_dir($path) || ! is_writable($path)) {
            return ['name' => 'Storage', 'icon' => 'hard-drive', 'status' => 'neg', 'label' => 'Not Writable', 'detail' => $path];
        }

        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usedPercent = $total ? round((($total - $free) / $total) * 100) : 0;

        return [
            'name' => 'Storage', 'icon' => 'hard-drive',
            'status' => $usedPercent > 90 ? 'warn' : 'pos',
            'label' => $usedPercent > 90 ? 'Low Space' : 'Writable',
            'detail' => round($free / 1073741824, 1) . ' GB free (' . $usedPercent . '% used)',
        ];
    }

    private function checkQueue(): array
    {
        $failedCount = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;

        if ($failedCount === null) {
            return ['name' => 'Queue', 'icon' => 'list-ordered', 'status' => 'warn', 'label' => 'Unknown', 'detail' => 'failed_jobs table not found'];
        }

        return [
            'name' => 'Queue', 'icon' => 'list-ordered',
            'status' => $failedCount > 0 ? 'warn' : 'pos',
            'label' => $failedCount > 0 ? "{$failedCount} Failed Jobs" : 'Clean',
            'detail' => 'Driver: ' . config('queue.default'),
        ];
    }

    private function checkMail(): array
    {
        $configured = config('mail.default') !== 'log' && filled(config('mail.mailers.smtp.username'));

        return [
            'name' => 'Email', 'icon' => 'mail',
            'status' => $configured ? 'pos' : 'warn',
            'label' => $configured ? 'Configured' : 'Using Log Driver',
            'detail' => 'Mailer: ' . config('mail.default'),
        ];
    }

    private function checkDebugMode(): array
    {
        $debug = config('app.debug');

        return [
            'name' => 'Debug Mode', 'icon' => 'bug',
            'status' => $debug ? 'neg' : 'pos',
            'label' => $debug ? 'ON (risky!)' : 'OFF',
            'detail' => $debug ? 'APP_DEBUG=true exposes error details publicly — turn off before going live' : 'Safe for production',
        ];
    }
}
