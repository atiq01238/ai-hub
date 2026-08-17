<?php

namespace App\Services\System;

use App\Models\ApiRequestLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ApiMonitoringService
{
    public function providers(): array
    {
        return [
            'newsapi' => ['name' => 'News API', 'icon' => 'newspaper', 'configured' => filled(env('NEWS_API_KEY')), 'endpoint' => 'https://newsapi.org/v2/top-headlines', 'key_name' => 'NEWS_API_KEY'],
            'openai' => ['name' => 'OpenAI API', 'icon' => 'sparkles', 'configured' => filled(env('OPENAI_API_KEY')), 'endpoint' => 'https://api.openai.com/v1/models', 'key_name' => 'OPENAI_API_KEY'],
            'anthropic' => ['name' => 'Anthropic API', 'icon' => 'brain-circuit', 'configured' => filled(env('ANTHROPIC_API_KEY')), 'endpoint' => 'https://api.anthropic.com/v1/models', 'key_name' => 'ANTHROPIC_API_KEY'],
            'gemini' => ['name' => 'Gemini API', 'icon' => 'bot', 'configured' => filled(env('GEMINI_API_KEY')), 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models', 'key_name' => 'GEMINI_API_KEY'],
        ];
    }

    public function dashboard(): array
    {
        $hasLogs = Schema::hasTable('api_request_logs');
        $providers = collect($this->providers())->map(function ($provider, $key) use ($hasLogs) {
            $today = $hasLogs ? ApiRequestLog::where('provider', $key)->whereDate('created_at', today()) : null;
            $requests = $today ? (clone $today)->count() : 0;
            $failures = $today ? (clone $today)->where('successful', false)->count() : 0;
            $avgLatency = $today ? (int) round((clone $today)->whereNotNull('duration_ms')->avg('duration_ms') ?? 0) : 0;
            $last = $hasLogs ? ApiRequestLog::where('provider', $key)->latest()->first() : null;

            $status = ! $provider['configured'] ? 'unconfigured' : ($last ? ($last->successful ? 'connected' : 'error') : 'ready');
            return array_merge($provider, compact('key', 'requests', 'failures', 'avgLatency', 'last', 'status'));
        })->values();

        $trend = collect(range(6, 0))->map(function ($daysAgo) use ($hasLogs) {
            $date = now()->subDays($daysAgo);
            if (! $hasLogs) return ['label' => $date->format('D'), 'success' => 0, 'failed' => 0];
            return [
                'label' => $date->format('D'),
                'success' => ApiRequestLog::whereDate('created_at', $date->toDateString())->where('successful', true)->count(),
                'failed' => ApiRequestLog::whereDate('created_at', $date->toDateString())->where('successful', false)->count(),
            ];
        });

        $todayRequests = $hasLogs ? ApiRequestLog::whereDate('created_at', today())->count() : 0;
        $todayFailures = $hasLogs ? ApiRequestLog::whereDate('created_at', today())->where('successful', false)->count() : 0;

        return [
            'providers' => $providers,
            'hasLogs' => $hasLogs,
            'todayRequests' => $todayRequests,
            'todayFailures' => $todayFailures,
            'errorRate' => $todayRequests ? round(($todayFailures / $todayRequests) * 100, 1) : 0,
            'avgLatency' => $hasLogs ? (int) round(ApiRequestLog::whereDate('created_at', today())->avg('duration_ms') ?? 0) : 0,
            'trend' => $trend,
            'recent' => $hasLogs ? ApiRequestLog::latest()->limit(20)->get() : collect(),
        ];
    }

    public function test(string $key): ApiRequestLog
    {
        $providers = $this->providers();
        abort_unless(isset($providers[$key]), 404);
        $provider = $providers[$key];
        if (! $provider['configured']) {
            throw new \RuntimeException("{$provider['key_name']} is not configured.");
        }

        $start = microtime(true);
        $status = null; $success = false; $error = null;
        try {
            $request = Http::timeout(12)->acceptJson();
            if ($key === 'openai') $request = $request->withToken(env('OPENAI_API_KEY'));
            if ($key === 'anthropic') $request = $request->withHeaders(['x-api-key' => env('ANTHROPIC_API_KEY'), 'anthropic-version' => '2023-06-01']);

            $url = $provider['endpoint'];
            $query = [];
            if ($key === 'newsapi') $query = ['country' => 'us', 'pageSize' => 1, 'apiKey' => env('NEWS_API_KEY')];
            if ($key === 'gemini') $query = ['key' => env('GEMINI_API_KEY')];

            $response = $request->get($url, $query);
            $status = $response->status();
            $success = $response->successful();
            if (! $success) $error = 'HTTP ' . $status;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $duration = (int) round((microtime(true) - $start) * 1000);
        if (! Schema::hasTable('api_request_logs')) {
            throw new \RuntimeException('Run php artisan migrate to enable API monitoring logs.');
        }

        return ApiRequestLog::create([
            'provider' => $key,
            'endpoint' => $provider['endpoint'],
            'method' => 'GET',
            'status_code' => $status,
            'duration_ms' => $duration,
            'successful' => $success,
            'error_message' => $error,
        ]);
    }
}
