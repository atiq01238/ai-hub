<?php

namespace App\Services\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    public function snapshot(): array
    {
        $checks = [
            $this->database(),
            $this->cache(),
            $this->storage(),
            $this->queue(),
            $this->mail(),
            $this->debugMode(),
            $this->scheduler(),
            $this->runtime(),
        ];

        $weights = ['pos' => 1, 'warn' => 0.5, 'neg' => 0];
        $score = collect($checks)->sum(fn ($check) => $weights[$check['status']] ?? 0);
        $overallPercent = (int) round(($score / max(count($checks), 1)) * 100);

        return [
            'checks' => $checks,
            'overallPercent' => $overallPercent,
            'healthy' => collect($checks)->where('status', 'pos')->count(),
            'warnings' => collect($checks)->where('status', 'warn')->count(),
            'critical' => collect($checks)->where('status', 'neg')->count(),
            'generatedAt' => now(),
        ];
    }

    private function database(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $ms = (int) round((microtime(true) - $start) * 1000);
            return $this->check('Database', 'database', $ms > 500 ? 'warn' : 'pos', $ms > 500 ? 'Slow' : 'Connected', "Responded in {$ms}ms", ['latency_ms' => $ms]);
        } catch (\Throwable $e) {
            return $this->check('Database', 'database', 'neg', 'Down', 'Connection failed');
        }
    }

    private function cache(): array
    {
        $start = microtime(true);
        try {
            $key = 'system_health_' . uniqid();
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            $ms = (int) round((microtime(true) - $start) * 1000);
            return $this->check('Cache', 'zap', $ok ? 'pos' : 'warn', $ok ? 'Working' : 'Read Failed', 'Driver: ' . config('cache.default') . " · {$ms}ms", ['latency_ms' => $ms]);
        } catch (\Throwable $e) {
            return $this->check('Cache', 'zap', 'neg', 'Down', 'Driver: ' . config('cache.default'));
        }
    }

    private function storage(): array
    {
        $path = storage_path('app');
        if (! is_dir($path) || ! is_writable($path)) {
            return $this->check('Storage', 'hard-drive', 'neg', 'Not Writable', $path);
        }

        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        $used = ($free !== false && $total) ? (int) round((($total - $free) / $total) * 100) : null;
        $status = $used !== null && $used >= 95 ? 'neg' : (($used !== null && $used >= 85) ? 'warn' : 'pos');
        $detail = $free !== false ? $this->bytes($free) . ' free' . ($used !== null ? " · {$used}% used" : '') : 'Writable';

        return $this->check('Storage', 'hard-drive', $status, $status === 'pos' ? 'Healthy' : 'Low Space', $detail, ['used_percent' => $used]);
    }

    private function queue(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return $this->check('Queue', 'list-ordered', 'warn', 'Limited', 'failed_jobs table not found');
        }

        $failed = DB::table('failed_jobs')->count();
        return $this->check('Queue', 'list-ordered', $failed > 20 ? 'neg' : ($failed > 0 ? 'warn' : 'pos'), $failed ? "{$failed} Failed" : 'Clean', 'Driver: ' . config('queue.default'), ['failed_jobs' => $failed]);
    }

    private function mail(): array
    {
        $mailer = config('mail.default');
        $configured = $mailer !== 'log' && ($mailer !== 'smtp' || filled(config('mail.mailers.smtp.host')));
        return $this->check('Email', 'mail', $configured ? 'pos' : 'warn', $configured ? 'Configured' : 'Development Mode', 'Mailer: ' . $mailer);
    }

    private function debugMode(): array
    {
        $debug = (bool) config('app.debug');
        $production = app()->environment('production');
        $status = $debug && $production ? 'neg' : ($debug ? 'warn' : 'pos');
        return $this->check('Debug Mode', 'bug', $status, $debug ? 'Enabled' : 'Disabled', $debug ? 'Disable APP_DEBUG before production launch' : 'Error details are not exposed');
    }

    private function scheduler(): array
    {
        if (! Schema::hasTable('settings')) {
            return $this->check('Automation', 'timer-reset', 'warn', 'Unknown', 'Settings table is unavailable');
        }

        $enabled = Setting::get('ai_news_automation_enabled', '1') === '1';
        $last = Setting::get('ai_news_last_run_finished_at');
        $lastStatus = Setting::get('ai_news_last_run_status', 'never');

        if (! $enabled) {
            return $this->check('Automation', 'timer-reset', 'warn', 'Paused', 'News automation is disabled');
        }

        if (! $last) {
            return $this->check('Automation', 'timer-reset', 'warn', 'No Heartbeat', 'No completed automation run recorded yet');
        }

        $ageHours = now()->diffInHours(\Illuminate\Support\Carbon::parse($last));
        $status = $lastStatus === 'failed' ? 'neg' : ($ageHours > 12 ? 'warn' : 'pos');
        return $this->check('Automation', 'timer-reset', $status, ucfirst($lastStatus), 'Last run ' . \Illuminate\Support\Carbon::parse($last)->diffForHumans());
    }

    private function runtime(): array
    {
        $supported = version_compare(PHP_VERSION, '8.2.0', '>=');
        return $this->check('Runtime', 'cpu', $supported ? 'pos' : 'warn', 'PHP ' . PHP_VERSION, 'Laravel ' . app()->version() . ' · ' . app()->environment());
    }

    private function check(string $name, string $icon, string $status, string $label, string $detail, array $meta = []): array
    {
        return compact('name', 'icon', 'status', 'label', 'detail', 'meta');
    }

    private function bytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return number_format($bytes, $i >= 3 ? 1 : 0) . ' ' . $units[$i];
    }
}
