<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RefreshAiDiscovery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $limit = 200)
    {
        $this->limit = max(25, min(1000, $this->limit));
    }

    public function handle(): void
    {
        Setting::set('ai_discovery_refresh_status', 'running');
        Setting::set('ai_discovery_refresh_started_at', now()->toDateTimeString());
        Setting::set('ai_discovery_refresh_message', 'Refreshing monitored RSS sources and discovery candidates.');

        try {
            $exit = Artisan::call('news:pipeline', [
                '--limit' => $this->limit,
                '--skip-ai' => true,
            ]);

            $output = trim(Artisan::output());

            Artisan::call('discovery:health-check');

            if ($exit !== 0) {
                throw new \RuntimeException($output !== '' ? $output : 'News/discovery pipeline returned a non-zero exit code.');
            }

            Setting::set('ai_discovery_refresh_status', 'success');
            Setting::set('ai_discovery_refresh_finished_at', now()->toDateTimeString());
            Setting::set(
                'ai_discovery_refresh_message',
                mb_substr($output !== '' ? $output : 'Market refresh completed successfully.', 0, 1000)
            );
        } catch (\Throwable $e) {
            Setting::set('ai_discovery_refresh_status', 'failed');
            Setting::set('ai_discovery_refresh_finished_at', now()->toDateTimeString());
            Setting::set('ai_discovery_refresh_message', mb_substr($e->getMessage(), 0, 1000));

            Log::error('AI Discovery market refresh failed.', ['error' => $e->getMessage()]);

            AppNotification::broadcast(
                'triangle-alert',
                'warning',
                'AI Discovery refresh failed',
                mb_substr($e->getMessage(), 0, 220),
                url('/admin/discovery'),
                'ai_discovery_health'
            );

            throw $e;
        }
    }
}
